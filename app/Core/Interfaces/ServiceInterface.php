<?php

namespace App\Core\Interfaces;

/**
 * Base Service Interface following SOLID principles
 * All service implementations should extend this interface
 */
interface ServiceInterface
{
    /**
     * Set the repository for the service
     */
    public function setRepository(RepositoryInterface $repository): self;

    /**
     * Get the repository instance
     */
    public function getRepository(): RepositoryInterface;
}