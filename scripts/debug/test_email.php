<?php

declare(strict_types=1);

/**
 * Script para testar a configuração de e-mail
 * Execute via: php scripts/debug/test_email.php [email_address]
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Laminas\Mvc\Application;
use TaskManager\Service\EmailNotificationService;

$testEmail = $argv[1] ?? 'test@example.com';

try {
    // Inicializar aplicação
    $appConfig = require __DIR__ . '/../../config/application.config.php';
    $application = Application::init($appConfig);
    $serviceManager = $application->getServiceManager();

    // Obter serviço de notificações
    $emailService = $serviceManager->get(EmailNotificationService::class);

    echo "=== Email Configuration Test ===\n";
    echo "Testing email configuration with Mailtrap...\n";
    echo "Test email will be sent to: {$testEmail}\n\n";

    // Tentar enviar e-mail de teste
    $success = $emailService->sendTestEmail($testEmail, 'Test User');

    if ($success) {
        echo "✓ SUCCESS: Test email sent successfully!\n";
        echo "Check your Mailtrap inbox for the test email.\n";
    } else {
        echo "✗ FAILED: Could not send test email.\n";
        echo "Please check your Mailtrap configuration in config/autoload/global.php\n";
    }

    echo "\n=== Test completed ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}