<?php

namespace App\Core\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Core Exception for handling module-specific exceptions
 */
class CoreException extends Exception
{
    protected int $statusCode = 500;
    protected array $errors = [];

    public function __construct(
        string $message = 'An error occurred',
        int $statusCode = 500,
        array $errors = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the errors array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Render the exception as JSON response
     */
    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($this),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => $this->getTrace(),
            ];
        }

        return response()->json($response, $this->statusCode);
    }
}