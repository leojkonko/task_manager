<?php

declare(strict_types=1);

namespace TaskManager\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View Helper para retornar classes CSS dos badges de prioridade
 */
class GetPriorityBadgeClass extends AbstractHelper
{
    public function __invoke(string $priority): string
    {
        return match($priority) {
            'low' => 'bg-info',
            'medium' => 'bg-secondary',
            'high' => 'bg-warning text-dark',
            'urgent' => 'bg-danger',
            default => 'bg-light text-dark'
        };
    }
}