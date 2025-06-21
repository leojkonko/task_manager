<?php

declare(strict_types=1);

/**
 * Script para migrar o banco de dados para suporte a notificações por e-mail
 * Execute via: php scripts/setup/migrate_email_notifications.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Laminas\Mvc\Application;

try {
    // Inicializar aplicação
    $appConfig = require __DIR__ . '/../../config/application.config.php';
    $application = Application::init($appConfig);
    $serviceManager = $application->getServiceManager();

    // Obter adaptador de banco de dados
    $dbAdapter = $serviceManager->get(\Laminas\Db\Adapter\AdapterInterface::class);

    echo "=== Email Notifications Migration ===\n";
    echo "Adding email notification support to tasks table...\n\n";

    // Verificar se a coluna reminder_sent já existe
    $checkColumnQuery = "SHOW COLUMNS FROM tasks LIKE 'reminder_sent'";
    $result = $dbAdapter->query($checkColumnQuery, $dbAdapter::QUERY_MODE_EXECUTE);

    if ($result->count() > 0) {
        echo "⚠️  Column 'reminder_sent' already exists. Skipping column creation.\n";
    } else {
        // Adicionar coluna reminder_sent
        $addColumnQuery = "ALTER TABLE tasks ADD COLUMN reminder_sent BOOLEAN DEFAULT FALSE AFTER due_date";
        $dbAdapter->query($addColumnQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        echo "✓ Added 'reminder_sent' column to tasks table\n";
    }

    // Criar índices se não existirem
    try {
        $dbAdapter->query("CREATE INDEX idx_tasks_due_date_reminder ON tasks(due_date, reminder_sent, status)", $dbAdapter::QUERY_MODE_EXECUTE);
        echo "✓ Created index 'idx_tasks_due_date_reminder'\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "⚠️  Index 'idx_tasks_due_date_reminder' already exists\n";
        } else {
            throw $e;
        }
    }

    try {
        $dbAdapter->query("CREATE INDEX idx_tasks_status_due_date ON tasks(status, due_date)", $dbAdapter::QUERY_MODE_EXECUTE);
        echo "✓ Created index 'idx_tasks_status_due_date'\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "⚠️  Index 'idx_tasks_status_due_date' already exists\n";
        } else {
            throw $e;
        }
    }

    echo "\n=== Migration completed successfully! ===\n";
    echo "Email notification system is now ready to use.\n\n";
    echo "Next steps:\n";
    echo "1. Configure Mailtrap credentials in config/autoload/global.php\n";
    echo "2. Test email configuration: php scripts/debug/test_email.php\n";
    echo "3. Send notifications: php scripts/maintenance/send_email_notifications.php\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}