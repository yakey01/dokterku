<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Services\TelegramService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Exception;

class ValidationWorkflowService
{
    protected TelegramService $telegramService;
    protected NotificationService $notificationService;
    
    protected array $validationStates = [
        'pending' => 'Menunggu Validasi',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'revision' => 'Perlu Revisi',
        'cancelled' => 'Dibatalkan',
    ];

    protected array $validationRules = [
        'Tindakan' => [
            'required_fields' => ['jenis_tindakan_id', 'pasien_id', 'tanggal_tindakan', 'tarif'],
            'approval_levels' => ['supervisor', 'manager'],
            'auto_approve_threshold' => 100000, // Auto approve below 100k
            'requires_reason' => ['rejected', 'revision'],
        ],
        'PendapatanHarian' => [
            'required_fields' => ['pendapatan_id', 'nominal', 'tanggal_input'],
            'approval_levels' => ['supervisor'],
            'auto_approve_threshold' => 500000, // Auto approve below 500k
            'requires_reason' => ['rejected'],
        ],
        'PengeluaranHarian' => [
            'required_fields' => ['pengeluaran_id', 'nominal', 'tanggal_input'],
            'approval_levels' => ['supervisor', 'manager'],
            'auto_approve_threshold' => 200000, // Auto approve below 200k
            'requires_reason' => ['rejected'],
        ],
    ];

    public function __construct(TelegramService $telegramService, NotificationService $notificationService = null)
    {
        $this->telegramService = $telegramService;
        $this->notificationService = $notificationService ?? new NotificationService($telegramService);
    }

    /**
     * Submit record for validation
     */
    public function submitForValidation(Model $record, array $options = []): array
    {
        try {
            DB::beginTransaction();

            $modelName = class_basename($record);
            $rules = $this->validationRules[$modelName] ?? [];

            // Validate required fields
            $this->validateRequiredFields($record, $rules['required_fields'] ?? []);

            // Check for auto-approval
            $autoApprove = $this->shouldAutoApprove($record, $rules);

            if ($autoApprove) {
                $result = $this->approve($record, [
                    'reason' => 'Auto-approved based on threshold',
                    'approved_by' => 'system',
                    'auto_approved' => true,
                ]);
            } else {
                // Update status to pending
                $record->update([
                    'status_validasi' => 'pending',
                    'submitted_at' => now(),
                    'submitted_by' => Auth::id(),
                ]);

                // Notify approvers
                $this->notifyApprovers($record, 'submitted');

                // Send real-time notification
                $this->sendRealTimeNotification($record, 'validation_submitted');

                $result = [
                    'success' => true,
                    'status' => 'pending',
                    'message' => 'Record submitted for validation',
                    'auto_approved' => false,
                ];
            }

            // Log submission
            $this->logValidationAction($record, 'submitted', $result);

            DB::commit();
            return $result;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Validation submission failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Approve record
     */
    public function approve(Model $record, array $options = []): array
    {
        try {
            DB::beginTransaction();

            $approvedBy = $options['approved_by'] ?? Auth::id();
            $reason = $options['reason'] ?? '';
            $autoApproved = $options['auto_approved'] ?? false;

            // Update record status
            $record->update([
                'status_validasi' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
                'approval_reason' => $reason,
                'auto_approved' => $autoApproved,
            ]);

            // Update related status if applicable
            if ($record->hasAttribute('status') && $record->status === 'pending') {
                $record->update(['status' => 'selesai']);
            }

            // Calculate Jaspel for approved Tindakan
            if ($record instanceof \App\Models\Tindakan) {
                try {
                    $jaspelService = app(\App\Services\JaspelCalculationService::class);
                    $jaspelRecords = $jaspelService->calculateJaspelFromTindakan($record);
                    
                    Log::info('Jaspel calculated for approved tindakan', [
                        'tindakan_id' => $record->id,
                        'jaspel_count' => is_array($jaspelRecords) ? count($jaspelRecords) : 0,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to calculate Jaspel for tindakan', [
                        'tindakan_id' => $record->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't throw - let the validation continue even if Jaspel fails
                }
            }

            // Notify stakeholders
            $this->notifyApprovers($record, 'approved');
            $this->notifySubmitter($record, 'approved');

            // Send real-time notification
            $this->sendRealTimeNotification($record, 'validation_approved');

            // Log approval
            $this->logValidationAction($record, 'approved', [
                'approved_by' => $approvedBy,
                'reason' => $reason,
                'auto_approved' => $autoApproved,
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'approved',
                'message' => 'Record approved successfully',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Approval failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject record
     */
    public function reject(Model $record, string $reason, array $options = []): array
    {
        try {
            DB::beginTransaction();

            $rejectedBy = $options['rejected_by'] ?? Auth::id();

            // Update record status
            $record->update([
                'status_validasi' => 'ditolak',
                'rejected_at' => now(),
                'rejected_by' => $rejectedBy,
                'rejection_reason' => $reason,
            ]);

            // Update related status if applicable
            if ($record->hasAttribute('status') && $record->status !== 'batal') {
                $record->update(['status' => 'batal']);
            }

            // Notify stakeholders
            $this->notifySubmitter($record, 'rejected', $reason);

            // Send real-time notification
            $this->sendRealTimeNotification($record, 'validation_rejected');

            // Log rejection
            $this->logValidationAction($record, 'rejected', [
                'rejected_by' => $rejectedBy,
                'reason' => $reason,
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'rejected',
                'message' => 'Record rejected',
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'reason' => $reason,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Rejection failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Request revision
     */
    public function requestRevision(Model $record, string $reason, array $options = []): array
    {
        try {
            DB::beginTransaction();

            $requestedBy = $options['requested_by'] ?? Auth::id();

            // Update record status
            $record->update([
                'status_validasi' => 'revision',
                'revision_requested_at' => now(),
                'revision_requested_by' => $requestedBy,
                'revision_reason' => $reason,
            ]);

            // Notify submitter
            $this->notifySubmitter($record, 'revision_requested', $reason);

            // Send real-time notification
            $this->sendRealTimeNotification($record, 'validation_revision');

            // Log revision request
            $this->logValidationAction($record, 'revision_requested', [
                'requested_by' => $requestedBy,
                'reason' => $reason,
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'revision',
                'message' => 'Revision requested',
                'requested_by' => $requestedBy,
                'requested_at' => now(),
                'reason' => $reason,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Revision request failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get validation statistics
     */
    public function getValidationStats(string $modelClass = null, int $days = 30): array
    {
        try {
            $query = AuditLog::where('action', 'LIKE', '%validation%')
                ->orWhere('action', 'LIKE', '%approved%')
                ->orWhere('action', 'LIKE', '%rejected%')
                ->where('created_at', '>=', now()->subDays($days));

            if ($modelClass) {
                $query->where('model_type', $modelClass);
            }

            $logs = $query->get();

            $stats = [
                'total_submissions' => 0,
                'approved' => 0,
                'rejected' => 0,
                'pending' => 0,
                'auto_approved' => 0,
                'approval_rate' => 0,
                'average_approval_time' => 0,
                'daily_breakdown' => [],
            ];

            foreach ($logs as $log) {
                $changes = json_decode($log->changes, true) ?? [];
                $date = $log->created_at->format('Y-m-d');

                if (!isset($stats['daily_breakdown'][$date])) {
                    $stats['daily_breakdown'][$date] = [
                        'submissions' => 0,
                        'approved' => 0,
                        'rejected' => 0,
                    ];
                }

                switch ($log->action) {
                    case 'submitted':
                        $stats['total_submissions']++;
                        $stats['daily_breakdown'][$date]['submissions']++;
                        break;
                    case 'approved':
                        $stats['approved']++;
                        $stats['daily_breakdown'][$date]['approved']++;
                        if ($changes['auto_approved'] ?? false) {
                            $stats['auto_approved']++;
                        }
                        break;
                    case 'rejected':
                        $stats['rejected']++;
                        $stats['daily_breakdown'][$date]['rejected']++;
                        break;
                }
            }

            // Calculate approval rate
            $total = $stats['approved'] + $stats['rejected'];
            $stats['approval_rate'] = $total > 0 ? round(($stats['approved'] / $total) * 100, 2) : 0;

            return [
                'success' => true,
                'data' => $stats,
            ];

        } catch (Exception $e) {
            Log::error('Failed to get validation statistics', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get pending validations for user
     */
    public function getPendingValidations(int $userId = null): array
    {
        try {
            $userId = $userId ?? Auth::id();
            $user = User::find($userId);

            if (!$user) {
                throw new Exception('User not found');
            }

            $pendingItems = [];

            // Check user roles and permissions
            $canApprove = $user->hasAnyRole(['supervisor', 'manager', 'admin']);

            if ($canApprove) {
                // Get pending items based on user role
                foreach (['Tindakan', 'PendapatanHarian', 'PengeluaranHarian'] as $model) {
                    $modelClass = "App\\Models\\$model";
                    if (class_exists($modelClass)) {
                        $items = $modelClass::where('status_validasi', 'pending')
                            ->with(['user', 'pasien', 'jenisTindakan', 'pendapatan', 'pengeluaran'])
                            ->orderBy('submitted_at', 'desc')
                            ->get();

                        foreach ($items as $item) {
                            $pendingItems[] = [
                                'id' => $item->id,
                                'model' => $model,
                                'title' => $this->getItemTitle($item),
                                'submitted_by' => $item->user->name ?? 'Unknown',
                                'submitted_at' => $item->submitted_at,
                                'amount' => $item->nominal ?? $item->tarif ?? 0,
                                'priority' => $this->calculatePriority($item),
                                'days_pending' => $item->submitted_at ? now()->diffInDays($item->submitted_at) : 0,
                            ];
                        }
                    }
                }
            }

            // Sort by priority and date
            usort($pendingItems, function ($a, $b) {
                if ($a['priority'] === $b['priority']) {
                    return $b['days_pending'] <=> $a['days_pending'];
                }
                return $b['priority'] <=> $a['priority'];
            });

            return [
                'success' => true,
                'data' => $pendingItems,
                'total' => count($pendingItems),
            ];

        } catch (Exception $e) {
            Log::error('Failed to get pending validations', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if record should be auto-approved
     */
    protected function shouldAutoApprove(Model $record, array $rules): bool
    {
        $threshold = $rules['auto_approve_threshold'] ?? 0;
        $amount = $record->nominal ?? $record->tarif ?? 0;

        return $amount <= $threshold;
    }

    /**
     * Validate required fields
     */
    protected function validateRequiredFields(Model $record, array $requiredFields): void
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!$record->$field) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missing));
        }
    }

    /**
     * Notify approvers
     */
    protected function notifyApprovers(Model $record, string $action): void
    {
        try {
            $modelName = class_basename($record);
            $title = $this->getItemTitle($record);
            $amount = $record->nominal ?? $record->tarif ?? 0;

            $message = match ($action) {
                'submitted' => "🔔 *Validasi Diperlukan*\n\n📝 {$modelName}: {$title}\n💰 Nominal: Rp " . number_format($amount, 0, ',', '.') . "\n👤 Diajukan oleh: " . ($record->user->name ?? 'Unknown') . "\n📅 Tanggal: " . now()->format('d/m/Y H:i'),
                'approved' => "✅ *Approved*\n\n📝 {$modelName}: {$title}\n💰 Nominal: Rp " . number_format($amount, 0, ',', '.') . "\n✅ Disetujui pada: " . now()->format('d/m/Y H:i'),
                default => "ℹ️ Update: {$modelName} - {$title}"
            };

            // Send to approvers (supervisors and managers)
            $approvers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['supervisor', 'manager']);
            })->get();

            foreach ($approvers as $approver) {
                $this->telegramService->sendMessage($approver->id, $message);
            }

        } catch (Exception $e) {
            Log::error('Failed to notify approvers', [
                'record_id' => $record->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify submitter
     */
    protected function notifySubmitter(Model $record, string $action, string $reason = ''): void
    {
        try {
            $modelName = class_basename($record);
            $title = $this->getItemTitle($record);
            $submitterId = $record->submitted_by ?? $record->user_id ?? $record->input_by;

            if (!$submitterId) {
                return;
            }

            $message = match ($action) {
                'approved' => "✅ *Disetujui*\n\n📝 {$modelName}: {$title}\n✅ Status: Disetujui\n📅 Tanggal: " . now()->format('d/m/Y H:i'),
                'rejected' => "❌ *Ditolak*\n\n📝 {$modelName}: {$title}\n❌ Status: Ditolak\n📝 Alasan: {$reason}\n📅 Tanggal: " . now()->format('d/m/Y H:i'),
                'revision_requested' => "🔄 *Revisi Diperlukan*\n\n📝 {$modelName}: {$title}\n🔄 Status: Perlu Revisi\n📝 Catatan: {$reason}\n📅 Tanggal: " . now()->format('d/m/Y H:i'),
                default => "ℹ️ Update: {$modelName} - {$title}"
            };

            $this->telegramService->sendMessage($submitterId, $message);

        } catch (Exception $e) {
            Log::error('Failed to notify submitter', [
                'record_id' => $record->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get item title for display
     */
    protected function getItemTitle(Model $record): string
    {
        $modelName = class_basename($record);

        return match ($modelName) {
            'Tindakan' => ($record->jenisTindakan->nama ?? 'Tindakan') . ' - ' . ($record->pasien->nama ?? 'Pasien'),
            'PendapatanHarian' => ($record->pendapatan->nama_pendapatan ?? 'Pendapatan') . ' - ' . $record->tanggal_input,
            'PengeluaranHarian' => ($record->pengeluaran->nama_pengeluaran ?? 'Pengeluaran') . ' - ' . $record->tanggal_input,
            default => $modelName . ' #' . $record->id,
        };
    }

    /**
     * Calculate priority based on amount and age
     */
    protected function calculatePriority(Model $record): int
    {
        $amount = $record->nominal ?? $record->tarif ?? 0;
        $daysPending = $record->submitted_at ? now()->diffInDays($record->submitted_at) : 0;

        $priority = 0;

        // Amount-based priority
        if ($amount >= 1000000) {
            $priority += 5;
        } elseif ($amount >= 500000) {
            $priority += 3;
        } elseif ($amount >= 100000) {
            $priority += 1;
        }

        // Age-based priority
        if ($daysPending >= 7) {
            $priority += 5;
        } elseif ($daysPending >= 3) {
            $priority += 3;
        } elseif ($daysPending >= 1) {
            $priority += 1;
        }

        return $priority;
    }

    /**
     * Send real-time notification
     */
    protected function sendRealTimeNotification(Model $record, string $action): void
    {
        try {
            $modelName = class_basename($record);
            $title = $this->getItemTitle($record);
            $amount = $record->nominal ?? $record->tarif ?? 0;

            $notificationData = match ($action) {
                'validation_submitted' => [
                    'title' => '📤 Validasi Diajukan',
                    'message' => "{$modelName}: {$title} - Menunggu validasi",
                    'priority' => $amount > 500000 ? 'high' : 'medium',
                ],
                'validation_approved' => [
                    'title' => '✅ Validasi Disetujui',
                    'message' => "{$modelName}: {$title} - Disetujui",
                    'priority' => 'medium',
                ],
                'validation_rejected' => [
                    'title' => '❌ Validasi Ditolak',
                    'message' => "{$modelName}: {$title} - Ditolak",
                    'priority' => 'high',
                ],
                'validation_revision' => [
                    'title' => '🔄 Revisi Diperlukan',
                    'message' => "{$modelName}: {$title} - Perlu revisi",
                    'priority' => 'medium',
                ],
                default => [
                    'title' => 'Notifikasi Validasi',
                    'message' => "{$modelName}: {$title}",
                    'priority' => 'medium',
                ],
            };

            // Send to submitter
            $submitterId = $record->submitted_by ?? $record->user_id ?? $record->input_by;
            if ($submitterId) {
                $this->notificationService->sendRealTimeNotification(
                    $submitterId,
                    $action,
                    $notificationData['title'],
                    $notificationData['message'],
                    ['record_id' => $record->id, 'model' => $modelName, 'amount' => $amount],
                    $notificationData['priority']
                );
            }

            // Send to approvers if needed
            if ($action === 'validation_submitted') {
                $approvers = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['supervisor', 'manager']);
                })->get();

                foreach ($approvers as $approver) {
                    $this->notificationService->sendRealTimeNotification(
                        $approver->id,
                        $action,
                        $notificationData['title'],
                        $notificationData['message'],
                        ['record_id' => $record->id, 'model' => $modelName, 'amount' => $amount],
                        $notificationData['priority']
                    );
                }
            }

        } catch (Exception $e) {
            Log::error('Failed to send real-time notification', [
                'record_id' => $record->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log validation action
     */
    protected function logValidationAction(Model $record, string $action, array $details): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => get_class($record),
                'model_id' => $record->id,
                'changes' => json_encode($details),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'risk_level' => 'medium',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log validation action', [
                'error' => $e->getMessage(),
                'action' => $action,
                'record_id' => $record->id,
            ]);
        }
    }

    /**
     * Bulk approve/reject operations
     */
    public function bulkAction(array $recordIds, string $action, array $options = []): array
    {
        try {
            DB::beginTransaction();
            
            $results = [];
            $successCount = 0;
            $failureCount = 0;
            
            foreach ($recordIds as $recordId) {
                try {
                    $modelClass = $options['model_class'] ?? null;
                    if (!$modelClass || !class_exists($modelClass)) {
                        throw new Exception('Invalid model class');
                    }
                    
                    $record = $modelClass::find($recordId);
                    if (!$record) {
                        $results[$recordId] = ['success' => false, 'message' => 'Record not found'];
                        $failureCount++;
                        continue;
                    }
                    
                    switch ($action) {
                        case 'approve':
                            $result = $this->approve($record, $options);
                            break;
                        case 'reject':
                            $reason = $options['reason'] ?? 'Bulk rejection';
                            $result = $this->reject($record, $reason, $options);
                            break;
                        case 'request_revision':
                            $reason = $options['reason'] ?? 'Bulk revision request';
                            $result = $this->requestRevision($record, $reason, $options);
                            break;
                        default:
                            throw new Exception('Invalid action');
                    }
                    
                    $results[$recordId] = $result;
                    $successCount++;
                    
                } catch (Exception $e) {
                    $results[$recordId] = ['success' => false, 'message' => $e->getMessage()];
                    $failureCount++;
                }
            }
            
            DB::commit();
            
            // Send bulk notification
            $this->sendBulkNotification($action, $successCount, $failureCount, $options);
            
            return [
                'success' => true,
                'total_processed' => count($recordIds),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results,
                'summary' => "Bulk {$action}: {$successCount} successful, {$failureCount} failed",
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Bulk validation action failed', [
                'action' => $action,
                'record_count' => count($recordIds),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send bulk notification
     */
    protected function sendBulkNotification(string $action, int $successCount, int $failureCount, array $options): void
    {
        try {
            $message = "🔄 *Bulk {$action} completed*\n\n";
            $message .= "✅ Successful: {$successCount}\n";
            $message .= "❌ Failed: {$failureCount}\n";
            $message .= "📅 " . now()->format('d/m/Y H:i');
            
            // Notify current user
            $this->telegramService->sendMessage(Auth::id(), $message);
            
            // Notify supervisors if there were failures
            if ($failureCount > 0) {
                $supervisors = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['supervisor', 'manager']);
                })->get();
                
                foreach ($supervisors as $supervisor) {
                    $this->telegramService->sendMessage($supervisor->id, $message);
                }
            }
            
        } catch (Exception $e) {
            Log::error('Failed to send bulk notification', [
                'action' => $action,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get validation workflow status for a record
     */
    public function getWorkflowStatus(Model $record): array
    {
        try {
            $modelName = class_basename($record);
            $rules = $this->validationRules[$modelName] ?? [];
            
            // Get validation history from audit logs
            $history = AuditLog::where('model_type', get_class($record))
                ->where('model_id', $record->id)
                ->whereIn('action', ['submitted', 'approved', 'rejected', 'revision_requested'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Calculate workflow metrics
            $submittedAt = $record->submitted_at ?? $record->created_at;
            $daysPending = $submittedAt ? now()->diffInDays($submittedAt) : 0;
            
            $priority = $this->calculatePriority($record);
            $canAutoApprove = $this->shouldAutoApprove($record, $rules);
            
            return [
                'current_status' => $record->status_validasi ?? 'draft',
                'status_display' => $this->validationStates[$record->status_validasi ?? 'pending'] ?? 'Unknown',
                'submitted_at' => $submittedAt,
                'days_pending' => $daysPending,
                'priority' => $priority,
                'priority_level' => $this->getPriorityLevel($priority),
                'can_auto_approve' => $canAutoApprove,
                'workflow_history' => $history->map(function ($log) {
                    return [
                        'action' => $log->action,
                        'user' => $log->user->name ?? 'System',
                        'timestamp' => $log->created_at,
                        'details' => json_decode($log->changes, true),
                    ];
                })->toArray(),
                'next_actions' => $this->getAvailableActions($record),
                'requirements' => [
                    'approval_levels' => $rules['approval_levels'] ?? [],
                    'required_fields' => $rules['required_fields'] ?? [],
                    'auto_approve_threshold' => $rules['auto_approve_threshold'] ?? 0,
                ],
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get workflow status', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            
            return [
                'current_status' => 'unknown',
                'error' => 'Unable to determine workflow status',
            ];
        }
    }

    /**
     * Get available actions for a record based on current status and user permissions
     */
    protected function getAvailableActions(Model $record): array
    {
        $user = Auth::user();
        $status = $record->status_validasi ?? 'draft';
        $actions = [];
        
        if (!$user) {
            return $actions;
        }
        
        // Check user permissions
        $canApprove = $user->hasAnyRole(['supervisor', 'manager', 'admin']);
        $isOwner = $record->input_by === $user->id || $record->user_id === $user->id;
        
        switch ($status) {
            case 'draft':
                if ($isOwner) {
                    $actions[] = 'submit';
                    $actions[] = 'edit';
                    $actions[] = 'delete';
                }
                break;
                
            case 'pending':
                if ($canApprove) {
                    $actions[] = 'approve';
                    $actions[] = 'reject';
                    $actions[] = 'request_revision';
                }
                if ($isOwner) {
                    $actions[] = 'cancel';
                }
                break;
                
            case 'revision':
                if ($isOwner) {
                    $actions[] = 'resubmit';
                    $actions[] = 'edit';
                    $actions[] = 'cancel';
                }
                break;
                
            case 'approved':
                if ($canApprove) {
                    $actions[] = 'view_details';
                }
                break;
                
            case 'rejected':
                if ($isOwner) {
                    $actions[] = 'resubmit';
                    $actions[] = 'view_rejection_reason';
                }
                if ($canApprove) {
                    $actions[] = 'reconsider';
                }
                break;
        }
        
        return $actions;
    }

    /**
     * Get priority level description
     */
    protected function getPriorityLevel(int $priority): string
    {
        if ($priority >= 8) {
            return 'critical';
        } elseif ($priority >= 5) {
            return 'high';
        } elseif ($priority >= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Cancel a pending validation
     */
    public function cancel(Model $record, string $reason = '', array $options = []): array
    {
        try {
            DB::beginTransaction();
            
            $cancelledBy = $options['cancelled_by'] ?? Auth::id();
            
            // Update record status
            $record->update([
                'status_validasi' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
                'cancellation_reason' => $reason,
            ]);
            
            // Update related status if applicable
            if ($record->hasAttribute('status') && $record->status !== 'batal') {
                $record->update(['status' => 'batal']);
            }
            
            // Notify relevant users
            $this->notifySubmitter($record, 'cancelled', $reason);
            
            // Send real-time notification
            $this->sendRealTimeNotification($record, 'validation_cancelled');
            
            // Log cancellation
            $this->logValidationAction($record, 'cancelled', [
                'cancelled_by' => $cancelledBy,
                'reason' => $reason,
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'status' => 'cancelled',
                'message' => 'Validation cancelled successfully',
                'cancelled_by' => $cancelledBy,
                'cancelled_at' => now(),
                'reason' => $reason,
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Cancellation failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resubmit a record after revision or rejection
     */
    public function resubmit(Model $record, array $options = []): array
    {
        try {
            DB::beginTransaction();
            
            $resubmittedBy = $options['resubmitted_by'] ?? Auth::id();
            $changes = $options['changes'] ?? [];
            
            // Validate current status allows resubmission
            if (!in_array($record->status_validasi, ['revision', 'rejected', 'cancelled'])) {
                throw new Exception('Record cannot be resubmitted from current status');
            }
            
            // Apply any changes
            if (!empty($changes)) {
                $record->update($changes);
            }
            
            // Reset validation status
            $record->update([
                'status_validasi' => 'pending',
                'resubmitted_at' => now(),
                'resubmitted_by' => $resubmittedBy,
                'revision_count' => ($record->revision_count ?? 0) + 1,
            ]);
            
            // Check for auto-approval
            $modelName = class_basename($record);
            $rules = $this->validationRules[$modelName] ?? [];
            $autoApprove = $this->shouldAutoApprove($record, $rules);
            
            if ($autoApprove) {
                $result = $this->approve($record, [
                    'reason' => 'Auto-approved on resubmission',
                    'approved_by' => 'system',
                    'auto_approved' => true,
                ]);
            } else {
                // Notify approvers
                $this->notifyApprovers($record, 'resubmitted');
                
                // Send real-time notification
                $this->sendRealTimeNotification($record, 'validation_resubmitted');
                
                $result = [
                    'success' => true,
                    'status' => 'pending',
                    'message' => 'Record resubmitted for validation',
                    'auto_approved' => false,
                ];
            }
            
            // Log resubmission
            $this->logValidationAction($record, 'resubmitted', [
                'resubmitted_by' => $resubmittedBy,
                'changes' => $changes,
                'revision_count' => $record->revision_count,
            ]);
            
            DB::commit();
            return $result;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Resubmission failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get validation dashboard metrics
     */
    public function getValidationDashboardMetrics(): array
    {
        try {
            return Cache::remember('validation_dashboard_metrics', 900, function () {
                $models = [
                    'Tindakan' => Tindakan::class,
                    'PendapatanHarian' => PendapatanHarian::class,
                    'PengeluaranHarian' => PengeluaranHarian::class,
                ];
                
                $metrics = [
                    'overview' => [],
                    'by_model' => [],
                    'performance' => [],
                    'alerts' => [],
                ];
                
                $totalPending = 0;
                $totalValue = 0;
                $oldestPending = null;
                
                foreach ($models as $modelName => $modelClass) {
                    $pending = $modelClass::where('status_validasi', 'pending')->count();
                    $approved = $modelClass::where('status_validasi', 'disetujui')
                        ->whereDate('approved_at', '>=', Carbon::now()->subDays(30))
                        ->count();
                    $rejected = $modelClass::where('status_validasi', 'ditolak')
                        ->whereDate('rejected_at', '>=', Carbon::now()->subDays(30))
                        ->count();
                    
                    $valueField = $modelClass === Tindakan::class ? 'tarif' : 'nominal';
                    $pendingValue = $modelClass::where('status_validasi', 'pending')->sum($valueField) ?? 0;
                    
                    // Find oldest pending item
                    $oldest = $modelClass::where('status_validasi', 'pending')
                        ->orderBy('submitted_at', 'asc')
                        ->first();
                    
                    if ($oldest && (!$oldestPending || $oldest->submitted_at < $oldestPending->submitted_at)) {
                        $oldestPending = $oldest;
                    }
                    
                    $metrics['by_model'][$modelName] = [
                        'pending' => $pending,
                        'approved_30d' => $approved,
                        'rejected_30d' => $rejected,
                        'pending_value' => $pendingValue,
                        'approval_rate' => ($approved + $rejected) > 0 ? 
                            round(($approved / ($approved + $rejected)) * 100, 2) : 0,
                    ];
                    
                    $totalPending += $pending;
                    $totalValue += $pendingValue;
                }
                
                // Calculate overall metrics
                $metrics['overview'] = [
                    'total_pending' => $totalPending,
                    'total_pending_value' => $totalValue,
                    'oldest_pending_days' => $oldestPending ? 
                        now()->diffInDays($oldestPending->submitted_at) : 0,
                    'avg_approval_time' => $this->calculateAverageApprovalTime(),
                ];
                
                // Performance metrics
                $metrics['performance'] = [
                    'daily_approvals' => $this->getDailyApprovalCount(),
                    'validation_velocity' => $this->calculateValidationVelocity(),
                    'backlog_trend' => $this->getBacklogTrend(),
                ];
                
                // Generate alerts
                $metrics['alerts'] = $this->generateValidationAlerts($metrics);
                
                return $metrics;
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get validation dashboard metrics', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'overview' => [],
                'by_model' => [],
                'performance' => [],
                'alerts' => [],
                'error' => 'Unable to load validation metrics',
            ];
        }
    }

    /**
     * Calculate average approval time in hours
     */
    protected function calculateAverageApprovalTime(): float
    {
        try {
            $approvals = AuditLog::where('action', 'approved')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->get();
            
            if ($approvals->isEmpty()) {
                return 0;
            }
            
            $totalHours = 0;
            $count = 0;
            
            foreach ($approvals as $approval) {
                $submission = AuditLog::where('model_type', $approval->model_type)
                    ->where('model_id', $approval->model_id)
                    ->where('action', 'submitted')
                    ->where('created_at', '<', $approval->created_at)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($submission) {
                    $hours = $submission->created_at->diffInHours($approval->created_at);
                    $totalHours += $hours;
                    $count++;
                }
            }
            
            return $count > 0 ? round($totalHours / $count, 2) : 0;
            
        } catch (Exception $e) {
            Log::error('Failed to calculate average approval time', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get daily approval count for the last 7 days
     */
    protected function getDailyApprovalCount(): array
    {
        try {
            $last7Days = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $count = AuditLog::where('action', 'approved')
                    ->whereDate('created_at', $date)
                    ->count();
                
                $last7Days->push([
                    'date' => $date->format('Y-m-d'),
                    'count' => $count,
                ]);
            }
            
            return $last7Days->toArray();
            
        } catch (Exception $e) {
            Log::error('Failed to get daily approval count', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Calculate validation velocity (items processed per day)
     */
    protected function calculateValidationVelocity(): float
    {
        try {
            $last30Days = AuditLog::whereIn('action', ['approved', 'rejected'])
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();
            
            return round($last30Days / 30, 2);
            
        } catch (Exception $e) {
            Log::error('Failed to calculate validation velocity', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get backlog trend (pending items over time)
     */
    protected function getBacklogTrend(): array
    {
        try {
            $trend = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                
                // This is an approximation - ideally we'd have historical snapshots
                $submitted = AuditLog::where('action', 'submitted')
                    ->whereDate('created_at', $date)
                    ->count();
                
                $processed = AuditLog::whereIn('action', ['approved', 'rejected'])
                    ->whereDate('created_at', $date)
                    ->count();
                
                $trend->push([
                    'date' => $date->format('Y-m-d'),
                    'submitted' => $submitted,
                    'processed' => $processed,
                    'net_change' => $submitted - $processed,
                ]);
            }
            
            return $trend->toArray();
            
        } catch (Exception $e) {
            Log::error('Failed to get backlog trend', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Generate validation alerts
     */
    protected function generateValidationAlerts(array $metrics): array
    {
        $alerts = [];
        
        // High backlog alert
        if ($metrics['overview']['total_pending'] > 50) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Validation Backlog',
                'message' => "You have {$metrics['overview']['total_pending']} items pending validation",
                'action' => 'Review and process pending validations',
                'priority' => 'high',
            ];
        }
        
        // Old pending items alert
        if ($metrics['overview']['oldest_pending_days'] > 7) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Overdue Validations',
                'message' => "Oldest pending item is {$metrics['overview']['oldest_pending_days']} days old",
                'action' => 'Process overdue validations immediately',
                'priority' => 'critical',
            ];
        }
        
        // High value pending alert
        if ($metrics['overview']['total_pending_value'] > 5000000) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Value Pending',
                'message' => 'Pending validations worth over Rp 5M',
                'action' => 'Review high-value pending items',
                'priority' => 'high',
            ];
        }
        
        return $alerts;
    }

    /**
     * Sync validated PendapatanHarian records to main Pendapatan table
     * This ensures bendahara dashboard shows complete financial data
     */
    public function syncPendapatanHarianToMainTable(PendapatanHarian $pendapatanHarian): bool
    {
        try {
            // Only sync if status is approved and not already synced
            if ($pendapatanHarian->status_validasi !== 'approved' && $pendapatanHarian->status_validasi !== 'disetujui') {
                Log::info('PendapatanHarian sync skipped - not approved', [
                    'id' => $pendapatanHarian->id,
                    'status' => $pendapatanHarian->status_validasi
                ]);
                return false;
            }

            // Check if already synced (avoid duplicates)
            $existingSync = \App\Models\Pendapatan::where('keterangan', 'LIKE', '%[SYNC-PH-' . $pendapatanHarian->id . ']%')->exists();
            if ($existingSync) {
                Log::info('PendapatanHarian already synced', ['id' => $pendapatanHarian->id]);
                return true;
            }

            // Prepare data for main Pendapatan table
            $pendapatanData = [
                'kode_pendapatan' => 'PH-' . $pendapatanHarian->id . '-' . date('Ymd'),
                'nama_pendapatan' => $pendapatanHarian->pendapatan?->nama_pendapatan ?? 'Pendapatan Harian',
                'sumber_pendapatan' => 'Petugas Input (Harian)',
                'tanggal' => $pendapatanHarian->tanggal_input,
                'nominal' => $pendapatanHarian->nominal,
                'kategori' => 'Harian',
                'keterangan' => ($pendapatanHarian->deskripsi ?? '') . ' [SYNC-PH-' . $pendapatanHarian->id . '] - Shift: ' . ($pendapatanHarian->shift ?? 'N/A'),
                'input_by' => $pendapatanHarian->user_id,
                'status_validasi' => 'disetujui', // Already validated by bendahara
                'validasi_by' => $pendapatanHarian->validasi_by ?? Auth::id(),
                'validasi_at' => $pendapatanHarian->validasi_at ?? now(),
                'catatan_validasi' => 'Auto-synced from validated daily input [PH-' . $pendapatanHarian->id . ']',
                'created_at' => $pendapatanHarian->created_at,
                'updated_at' => now(),
            ];

            // Create record in main Pendapatan table
            $pendapatan = \App\Models\Pendapatan::create($pendapatanData);

            // Log successful sync
            Log::info('PendapatanHarian synced successfully', [
                'pendapatan_harian_id' => $pendapatanHarian->id,
                'pendapatan_id' => $pendapatan->id,
                'nominal' => $pendapatanHarian->nominal,
                'date' => $pendapatanHarian->tanggal_input
            ]);

            // Update PendapatanHarian with sync info
            $pendapatanHarian->update([
                'catatan_validasi' => ($pendapatanHarian->catatan_validasi ?? '') . ' [SYNCED-' . $pendapatan->id . ']'
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to sync PendapatanHarian to main table', [
                'pendapatan_harian_id' => $pendapatanHarian->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Sync validated PengeluaranHarian records to main Pengeluaran table
     * This ensures bendahara dashboard shows complete expense data
     */
    public function syncPengeluaranHarianToMainTable(PengeluaranHarian $pengeluaranHarian): bool
    {
        try {
            // Only sync if status is approved and not already synced
            if ($pengeluaranHarian->status_validasi !== 'approved' && $pengeluaranHarian->status_validasi !== 'disetujui') {
                Log::info('PengeluaranHarian sync skipped - not approved', [
                    'id' => $pengeluaranHarian->id,
                    'status' => $pengeluaranHarian->status_validasi
                ]);
                return false;
            }

            // Check if already synced (avoid duplicates)
            $existingSync = \App\Models\Pengeluaran::where('keterangan', 'LIKE', '%[SYNC-EH-' . $pengeluaranHarian->id . ']%')->exists();
            if ($existingSync) {
                Log::info('PengeluaranHarian already synced', ['id' => $pengeluaranHarian->id]);
                return true;
            }

            // Prepare data for main Pengeluaran table
            $pengeluaranData = [
                'kode_pengeluaran' => 'EH-' . $pengeluaranHarian->id . '-' . date('Ymd'),
                'nama_pengeluaran' => $pengeluaranHarian->pengeluaran?->nama_pengeluaran ?? 'Pengeluaran Harian',
                'tanggal' => $pengeluaranHarian->tanggal_input,
                'nominal' => $pengeluaranHarian->nominal,
                'kategori' => 'Harian',
                'keterangan' => ($pengeluaranHarian->deskripsi ?? '') . ' [SYNC-EH-' . $pengeluaranHarian->id . '] - Shift: ' . ($pengeluaranHarian->shift ?? 'N/A'),
                'input_by' => $pengeluaranHarian->user_id,
                'status_validasi' => 'disetujui', // Already validated by bendahara
                'validasi_by' => $pengeluaranHarian->validasi_by ?? Auth::id(),
                'validasi_at' => $pengeluaranHarian->validasi_at ?? now(),
                'catatan_validasi' => 'Auto-synced from validated daily expense [EH-' . $pengeluaranHarian->id . ']',
                'created_at' => $pengeluaranHarian->created_at,
                'updated_at' => now(),
            ];

            // Create record in main Pengeluaran table
            $pengeluaran = \App\Models\Pengeluaran::create($pengeluaranData);

            // Log successful sync
            Log::info('PengeluaranHarian synced successfully', [
                'pengeluaran_harian_id' => $pengeluaranHarian->id,
                'pengeluaran_id' => $pengeluaran->id,
                'nominal' => $pengeluaranHarian->nominal,
                'date' => $pengeluaranHarian->tanggal_input
            ]);

            // Update PengeluaranHarian with sync info
            $pengeluaranHarian->update([
                'catatan_validasi' => ($pengeluaranHarian->catatan_validasi ?? '') . ' [SYNCED-' . $pengeluaran->id . ']'
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to sync PengeluaranHarian to main table', [
                'pengeluaran_harian_id' => $pengeluaranHarian->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Bulk sync all approved PengeluaranHarian records that haven't been synced yet
     */
    public function bulkSyncPengeluaranHarian(): array
    {
        try {
            // Get all approved PengeluaranHarian records that haven't been synced
            $unsynced = PengeluaranHarian::where(function($query) {
                $query->where('status_validasi', 'approved')
                      ->orWhere('status_validasi', 'disetujui');
            })
            ->where(function($query) {
                $query->whereNull('catatan_validasi')
                      ->orWhere('catatan_validasi', 'NOT LIKE', '%[SYNCED-%');
            })
            ->get();

            $results = [
                'total_found' => $unsynced->count(),
                'successful_syncs' => 0,
                'failed_syncs' => 0,
                'already_synced' => 0,
                'details' => []
            ];

            foreach ($unsynced as $pengeluaranHarian) {
                $syncResult = $this->syncPengeluaranHarianToMainTable($pengeluaranHarian);
                
                if ($syncResult) {
                    $results['successful_syncs']++;
                    $results['details'][] = [
                        'id' => $pengeluaranHarian->id,
                        'nominal' => $pengeluaranHarian->nominal,
                        'status' => 'synced'
                    ];
                } else {
                    $results['failed_syncs']++;
                    $results['details'][] = [
                        'id' => $pengeluaranHarian->id,
                        'nominal' => $pengeluaranHarian->nominal,
                        'status' => 'failed'
                    ];
                }
            }

            Log::info('Bulk PengeluaranHarian sync completed', $results);

            return $results;

        } catch (Exception $e) {
            Log::error('Bulk PengeluaranHarian sync failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_found' => 0,
                'successful_syncs' => 0,
                'failed_syncs' => 0,
                'already_synced' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk sync all approved PendapatanHarian records that haven't been synced yet
     */
    public function bulkSyncPendapatanHarian(): array
    {
        try {
            // Get all approved PendapatanHarian records that haven't been synced
            $unsynced = PendapatanHarian::where(function($query) {
                $query->where('status_validasi', 'approved')
                      ->orWhere('status_validasi', 'disetujui');
            })
            ->where(function($query) {
                $query->whereNull('catatan_validasi')
                      ->orWhere('catatan_validasi', 'NOT LIKE', '%[SYNCED-%');
            })
            ->get();

            $results = [
                'total_found' => $unsynced->count(),
                'successful_syncs' => 0,
                'failed_syncs' => 0,
                'already_synced' => 0,
                'details' => []
            ];

            foreach ($unsynced as $pendapatanHarian) {
                $syncResult = $this->syncPendapatanHarianToMainTable($pendapatanHarian);
                
                if ($syncResult) {
                    $results['successful_syncs']++;
                    $results['details'][] = [
                        'id' => $pendapatanHarian->id,
                        'nominal' => $pendapatanHarian->nominal,
                        'status' => 'synced'
                    ];
                } else {
                    $results['failed_syncs']++;
                    $results['details'][] = [
                        'id' => $pendapatanHarian->id,
                        'nominal' => $pendapatanHarian->nominal,
                        'status' => 'failed'
                    ];
                }
            }

            Log::info('Bulk sync completed', $results);

            return $results;

        } catch (Exception $e) {
            Log::error('Bulk sync failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_found' => 0,
                'successful_syncs' => 0,
                'failed_syncs' => 0,
                'already_synced' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
}