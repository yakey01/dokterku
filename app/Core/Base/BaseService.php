<?php

namespace App\Core\Base;

use App\Core\Interfaces\RepositoryInterface;
use App\Core\Interfaces\ServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Base Service implementing common business logic operations
 * Following Service Layer Pattern and SOLID principles
 */
abstract class BaseService implements ServiceInterface
{
    protected RepositoryInterface $repository;

    /**
     * Set the repository for the service
     */
    public function setRepository(RepositoryInterface $repository): self
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * Get the repository instance
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Execute operation within database transaction
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        DB::beginTransaction();
        
        try {
            $result = $callback();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Transaction failed: ' . $e->getMessage(), [
                'exception' => $e,
                'service' => static::class
            ]);
            throw $e;
        }
    }

    /**
     * Log service activity
     */
    protected function logActivity(string $action, array $data = []): void
    {
        Log::info('Service activity', [
            'service' => static::class,
            'action' => $action,
            'user_id' => auth()->id(),
            'data' => $data,
            'timestamp' => now()
        ]);
    }

    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (!$rule($data)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Cache service result
     */
    protected function cacheResult(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return cache()->remember($key, $ttl, $callback);
    }

    /**
     * Clear cache by pattern
     */
    protected function clearCache(string $pattern): void
    {
        $keys = cache()->getRedis()->keys($pattern);
        foreach ($keys as $key) {
            cache()->forget($key);
        }
    }

    /**
     * Dispatch domain event
     */
    protected function dispatchEvent(object $event): void
    {
        event($event);
    }

    /**
     * Handle service errors
     */
    protected function handleError(Exception $e, string $context = ''): void
    {
        Log::error('Service error', [
            'service' => static::class,
            'context' => $context,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}