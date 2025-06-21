<?php

declare(strict_types=1);

/**
 * Script para enviar notificações por e-mail de tarefas pendentes
 * Execute via: php scripts/maintenance/send_email_notifications.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Laminas\Mvc\Application;
use TaskManager\Service\EmailNotificationService;

try {
    // Inicializar aplicação
    $appConfig = require __DIR__ . '/../../config/application.config.php';
    $application = Application::init($appConfig);
    $serviceManager = $application->getServiceManager();

    // Obter serviço de notificações
    $emailService = $serviceManager->get(EmailNotificationService::class);

    echo "=== Task Manager Email Notifications ===\n";
    echo "Starting email notification process...\n\n";

    // Enviar lembretes de tarefas
    echo "1. Sending task reminders...\n";
    $reminderResult = $emailService->sendTaskReminders();
    echo "Status: {$reminderResult['status']}\n";
    echo "Message: {$reminderResult['message']}\n";
    if (isset($reminderResult['sent'])) {
        echo "Emails sent: {$reminderResult['sent']}\n";
    }
    if (!empty($reminderResult['errors'])) {
        echo "Errors:\n";
        foreach ($reminderResult['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
    echo "\n";

    // Enviar notificações de tarefas atrasadas
    echo "2. Sending overdue notifications...\n";
    $overdueResult = $emailService->sendOverdueNotifications();
    echo "Status: {$overdueResult['status']}\n";
    echo "Message: {$overdueResult['message']}\n";
    if (isset($overdueResult['sent'])) {
        echo "Emails sent: {$overdueResult['sent']}\n";
    }
    if (!empty($overdueResult['errors'])) {
        echo "Errors:\n";
        foreach ($overdueResult['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
    echo "\n";

    echo "=== Email notification process completed ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}