<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return [
    // ...
    'mail' => [
        'transport' => [
            'type' => 'smtp',
            'options' => [
                'host' => 'sandbox.smtp.mailtrap.io',
                'port' => 2525,
                'connection_class' => 'login',
                'connection_config' => [
                    'username' => 'your_mailtrap_username', // Replace with your Mailtrap username
                    'password' => 'your_mailtrap_password', // Replace with your Mailtrap password
                    'ssl' => 'tls',
                ],
            ],
        ],
        'from' => [
            'email' => 'noreply@taskmanager.local',
            'name' => 'Task Manager System',
        ],
        'notifications' => [
            'enabled' => true,
            'reminder_hours_before' => 24, // Send reminder 24 hours before due date
            'batch_size' => 50, // Maximum emails to send per batch
        ],
    ],
];
