<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminUserManagementService;
use App\Repositories\Admin\AdminUserRepository;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\BulkActionRequest;
use App\DTOs\Admin\UserManagementDTO;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Admin User Controller
 * 
 * Refactored controller with repository pattern, service layer,
 * enhanced validation, and comprehensive audit logging.
 */
class UserController extends Controller
{
    private AdminUserManagementService $userService;
    private AdminUserRepository $userRepository;

    public function __construct(
        AdminUserManagementService $userService,
        AdminUserRepository $userRepository
    ) {
        $this->userService = $userService;
        $this->userRepository = $userRepository;
    }

    /**
     * Display a listing of users with filtering and pagination
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'role_id' => $request->get('role_id'),
                'status' => $request->get('status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_direction' => $request->get('sort_direction', 'desc')
            ];

            $perPage = min((int) $request->get('per_page', 15), 100);
            $users = $this->userService->getPaginatedUsers($perPage, $filters);
            $roles = Role::orderBy('name')->get();
            $statistics = $this->userService->getUserStatistics();

            return view('admin.users.index', [
                'users' => $users,
                'roles' => $roles,
                'filters' => $filters,
                'statistics' => $statistics
            ]);

        } catch (Exception $e) {
            Log::error('Admin users index error', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'filters' => $filters ?? []
            ]);

            return view('admin.users.index', [
                'users' => collect(),
                'roles' => collect(),
                'filters' => [],
                'statistics' => [],
                'error' => 'Failed to load users data'
            ]);
        }
    }

    /**
     * Show the form for creating a new user
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $roles = Role::where('name', '!=', 'super_admin')
            ->orWhere(function ($q) {
                $q->where('name', 'super_admin')
                  ->when(!auth()->user()->hasRole('super_admin'), function ($q2) {
                      $q2->where('id', '!=', 'id'); // Exclude if not super admin
                  });
            })
            ->orderBy('name')
            ->get();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user
     *
     * @param CreateUserRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CreateUserRequest $request)
    {
        try {
            $userData = $request->getProcessedData();
            $user = $this->userService->createUser($userData);

            // Send welcome email if requested
            if ($request->boolean('send_welcome_email')) {
                // TODO: Implement welcome email functionality
                Log::info('Welcome email scheduled', [
                    'user_id' => $user->id,
                    'admin_id' => auth()->id()
                ]);
            }

            return redirect()
                ->route('admin.users.index')
                ->with('success', "User {$user->name} berhasil dibuat.");

        } catch (Exception $e) {
            Log::error('User creation failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'input' => $request->safe()->except(['password', 'password_confirmation'])
            ]);

            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Gagal membuat user: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user
     *
     * @param User $user
     * @return \Illuminate\View\View
     */
    public function show(User $user)
    {
        try {
            $user->load(['role']);
            $userDTO = UserManagementDTO::fromModel($user);
            
            // Get user activity summary
            $activitySummary = [
                'last_login' => $user->last_login_at,
                'total_logins' => $user->login_count ?? 0,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ];

            Log::info('User profile viewed', [
                'admin_id' => auth()->id(),
                'viewed_user_id' => $user->id
            ]);

            return view('admin.users.show', [
                'user' => $user,
                'userDTO' => $userDTO,
                'activitySummary' => $activitySummary
            ]);

        } catch (Exception $e) {
            Log::error('User show error', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Gagal memuat data user.');
        }
    }

    /**
     * Show the form for editing the specified user
     *
     * @param User $user
     * @return \Illuminate\View\View
     */
    public function edit(User $user)
    {
        $roles = Role::where('name', '!=', 'super_admin')
            ->orWhere(function ($q) use ($user) {
                $q->where('name', 'super_admin')
                  ->when(!auth()->user()->hasRole('super_admin'), function ($q2) {
                      $q2->where('id', '!=', 'id'); // Exclude if not super admin
                  });
            })
            ->orderBy('name')
            ->get();

        $userDTO = UserManagementDTO::fromModel($user);

        return view('admin.users.edit', [
            'user' => $user,
            'userDTO' => $userDTO,
            'roles' => $roles
        ]);
    }

    /**
     * Update the specified user
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $userData = $request->getProcessedData();
            $updatedUser = $this->userService->updateUser($user, $userData);

            // Log critical changes
            if ($request->hasCriticalChanges()) {
                Log::warning('Critical user changes made', [
                    'admin_id' => auth()->id(),
                    'target_user_id' => $user->id,
                    'password_changed' => $request->isChangingPassword(),
                    'role_changed' => $request->isChangingRole(),
                    'email_changed' => $request->filled('email')
                ]);
            }

            $message = "User {$updatedUser->name} berhasil diupdate.";
            if ($request->isChangingPassword()) {
                $message .= " Password telah diubah.";
            }

            return redirect()
                ->route('admin.users.index')
                ->with('success', $message);

        } catch (Exception $e) {
            Log::error('User update failed', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'input' => $request->safe()->except(['password', 'password_confirmation'])
            ]);

            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Gagal mengupdate user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        try {
            $userName = $user->name;
            $this->userService->deleteUser($user);

            return redirect()
                ->route('admin.users.index')
                ->with('success', "User {$userName} berhasil dihapus.");

        } catch (Exception $e) {
            Log::error('User deletion failed', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }

    /**
     * Handle bulk actions for users
     *
     * @param BulkActionRequest $request
     * @return JsonResponse
     */
    public function bulkAction(BulkActionRequest $request): JsonResponse
    {
        try {
            $userIds = $request->getAffectedUserIds();
            $action = $request->input('action');
            $data = $request->only(['role_id', 'reason']);

            $results = $this->userService->bulkAction($userIds, $action, $data);

            Log::info('Bulk action performed', array_merge(
                $request->getActionSummary(),
                ['results' => $results]
            ));

            $message = "Bulk action '{$action}' completed. ";
            $message .= "Success: {$results['success']}, Failed: {$results['failed']}";

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results
            ]);

        } catch (Exception $e) {
            Log::error('Bulk action failed', [
                'admin_id' => auth()->id(),
                'action' => $request->input('action'),
                'user_count' => count($request->input('user_ids', [])),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users via AJAX
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->get('q', '');
            $filters = [
                'role_id' => $request->get('role_id'),
                'status' => $request->get('status')
            ];

            $users = $this->userService->searchUsers($searchTerm, $filters);

            return response()->json([
                'success' => true,
                'data' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'nip' => $user->nip,
                        'role' => $user->role->name ?? 'No Role',
                        'is_active' => $user->email_verified_at !== null
                    ];
                })
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed'
            ], 500);
        }
    }

    /**
     * Export users data
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'role_id' => $request->get('role_id'),
                'status' => $request->get('status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to')
            ];

            // TODO: Implement user export functionality
            Log::info('User export requested', [
                'admin_id' => auth()->id(),
                'filters' => $filters
            ]);

            return redirect()
                ->back()
                ->with('info', 'Export functionality will be implemented soon.');

        } catch (Exception $e) {
            Log::error('User export failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Get user statistics for dashboard widget
     *
     * @return JsonResponse
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->userService->getUserStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }
}