<?php

namespace App\Core\Base;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Base Controller implementing common HTTP operations
 * Following Single Responsibility Principle
 */
abstract class BaseController extends Controller
{
    /**
     * Return success response
     */
    protected function success(
        mixed $data = null,
        string $message = 'Operation successful',
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return error response
     */
    protected function error(
        string $message = 'Operation failed',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return not found response
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return validation error response
     */
    protected function validationError(array $errors): JsonResponse
    {
        return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Return created response
     */
    protected function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return no content response
     */
    protected function noContent(): Response
    {
        return response()->noContent();
    }

    /**
     * Return paginated response
     */
    protected function paginated($paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        ]);
    }

    /**
     * Get validated data from request
     */
    protected function getValidatedData(Request $request, array $rules): array
    {
        return $request->validate($rules);
    }

    /**
     * Check if user has permission
     */
    protected function checkPermission(string $permission): bool
    {
        return auth()->user()?->can($permission) ?? false;
    }

    /**
     * Authorize action or abort
     */
    protected function authorizeAction(string $permission, string $message = 'Unauthorized action'): void
    {
        if (!$this->checkPermission($permission)) {
            abort(403, $message);
        }
    }
}