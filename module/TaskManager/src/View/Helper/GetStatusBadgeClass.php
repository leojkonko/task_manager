<?php

declare(strict_types=1);

namespace TaskManager\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View Helper para retornar classes CSS dos badges de status
 */
class GetStatusBadgeClass extends AbstractHelper
{
    public function __invoke(string $status): string
    {
        return match($status) {
            'pending' => 'bg-warning text-dark',
            'in_progress' => 'bg-primary',
            'completed' => 'bg-success',
            'cancelled' => 'bg-secondary',
            default => 'bg-light text-dark'
        };
    }
}