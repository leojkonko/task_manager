<?php

declare(strict_types=1);

namespace TaskManager\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use TaskManager\Service\EmailNotificationService;
use TaskManager\Service\TaskService;

class EmailNotificationServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $taskService = $container->get(TaskService::class);
        $config = $container->get('config');

        return new EmailNotificationService($taskService, $config);
    }
}