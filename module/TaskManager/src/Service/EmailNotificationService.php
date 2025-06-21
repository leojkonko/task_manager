<?php

declare(strict_types=1);

namespace TaskManager\Service;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use TaskManager\Entity\Task;
use TaskManager\Service\TaskService;

class EmailNotificationService
{
    private Swift_Mailer $mailer;
    private TaskService $taskService;
    private array $config;

    public function __construct(TaskService $taskService, array $config)
    {
        $this->taskService = $taskService;
        $this->config = $config;
        $this->initializeMailer();
    }

    private function initializeMailer(): void
    {
        $transportConfig = $this->config['mail']['transport']['options'];
        
        $transport = (new Swift_SmtpTransport($transportConfig['host'], $transportConfig['port']))
            ->setUsername($transportConfig['connection_config']['username'])
            ->setPassword($transportConfig['connection_config']['password'])
            ->setEncryption($transportConfig['connection_config']['ssl']);

        $this->mailer = new Swift_Mailer($transport);
    }

    /**
     * Send task reminder notifications
     */
    public function sendTaskReminders(): array
    {
        if (!$this->config['mail']['notifications']['enabled']) {
            return ['status' => 'disabled', 'message' => 'Email notifications are disabled'];
        }

        $hoursBeforeDue = $this->config['mail']['notifications']['reminder_hours_before'];
        $batchSize = $this->config['mail']['notifications']['batch_size'];
        
        // Get tasks that need reminders
        $tasks = $this->getTasksNeedingReminders($hoursBeforeDue);
        
        if (empty($tasks)) {
            return ['status' => 'success', 'message' => 'No tasks requiring reminders found', 'sent' => 0];
        }

        $sentCount = 0;
        $errors = [];

        foreach (array_slice($tasks, 0, $batchSize) as $task) {
            try {
                $this->sendTaskReminderEmail($task);
                $this->markReminderSent($task);
                $sentCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to send reminder for task {$task->getId()}: " . $e->getMessage();
            }
        }

        return [
            'status' => 'success',
            'message' => "Sent {$sentCount} reminder emails",
            'sent' => $sentCount,
            'errors' => $errors
        ];
    }

    /**
     * Send overdue task notifications
     */
    public function sendOverdueNotifications(): array
    {
        if (!$this->config['mail']['notifications']['enabled']) {
            return ['status' => 'disabled', 'message' => 'Email notifications are disabled'];
        }

        $overdueTasks = $this->getOverdueTasks();
        
        if (empty($overdueTasks)) {
            return ['status' => 'success', 'message' => 'No overdue tasks found', 'sent' => 0];
        }

        $sentCount = 0;
        $errors = [];

        foreach ($overdueTasks as $task) {
            try {
                $this->sendOverdueTaskEmail($task);
                $sentCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to send overdue notification for task {$task->getId()}: " . $e->getMessage();
            }
        }

        return [
            'status' => 'success',
            'message' => "Sent {$sentCount} overdue notifications",
            'sent' => $sentCount,
            'errors' => $errors
        ];
    }

    private function sendTaskReminderEmail(Task $task): void
    {
        $user = $task->getUser();
        $dueDate = $task->getDueDate();
        $hoursUntilDue = $dueDate ? ceil(($dueDate->getTimestamp() - time()) / 3600) : null;

        $subject = "Task Reminder: {$task->getTitle()}";
        $body = $this->generateReminderEmailBody($task, $hoursUntilDue);

        $message = (new Swift_Message($subject))
            ->setFrom([$this->config['mail']['from']['email'] => $this->config['mail']['from']['name']])
            ->setTo([$user->getEmail() => $user->getFullName()])
            ->setBody($body, 'text/html');

        $this->mailer->send($message);
    }

    private function sendOverdueTaskEmail(Task $task): void
    {
        $user = $task->getUser();
        $dueDate = $task->getDueDate();
        $hoursOverdue = $dueDate ? ceil((time() - $dueDate->getTimestamp()) / 3600) : null;

        $subject = "⚠️ Overdue Task: {$task->getTitle()}";
        $body = $this->generateOverdueEmailBody($task, $hoursOverdue);

        $message = (new Swift_Message($subject))
            ->setFrom([$this->config['mail']['from']['email'] => $this->config['mail']['from']['name']])
            ->setTo([$user->getEmail() => $user->getFullName()])
            ->setBody($body, 'text/html');

        $this->mailer->send($message);
    }

    private function generateReminderEmailBody(Task $task, ?int $hoursUntilDue): string
    {
        $user = $task->getUser();
        $dueDate = $task->getDueDate();
        $dueDateFormatted = $dueDate ? $dueDate->format('F j, Y \a\t g:i A') : 'No due date';
        $timeRemaining = $hoursUntilDue ? "{$hoursUntilDue} hours" : 'Unknown';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .task-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; }
                .priority { font-weight: bold; text-transform: uppercase; }
                .priority.high { color: #dc3545; }
                .priority.medium { color: #ffc107; }
                .priority.low { color: #28a745; }
                .priority.urgent { color: #6f42c1; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 Task Reminder</h1>
                </div>
                <div class='content'>
                    <p>Hello {$user->getFullName()},</p>
                    <p>This is a friendly reminder about your upcoming task:</p>
                    
                    <div class='task-details'>
                        <h3>{$task->getTitle()}</h3>
                        <p><strong>Description:</strong> {$task->getDescription()}</p>
                        <p><strong>Priority:</strong> <span class='priority {$task->getPriority()}'>{$task->getPriority()}</span></p>
                        <p><strong>Due Date:</strong> {$dueDateFormatted}</p>
                        <p><strong>Time Remaining:</strong> {$timeRemaining}</p>
                        <p><strong>Status:</strong> {$task->getStatus()}</p>
                    </div>
                    
                    <p>Don't forget to complete this task on time!</p>
                    <p><a href='http://localhost:8080/task-manager' class='btn'>View in Task Manager</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated reminder from Task Manager System</p>
                    <p>If you no longer wish to receive these emails, please contact your administrator</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function generateOverdueEmailBody(Task $task, ?int $hoursOverdue): string
    {
        $user = $task->getUser();
        $dueDate = $task->getDueDate();
        $dueDateFormatted = $dueDate ? $dueDate->format('F j, Y \a\t g:i A') : 'No due date';
        $timeOverdue = $hoursOverdue ? "{$hoursOverdue} hours" : 'Unknown';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .task-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #dc3545; }
                .priority { font-weight: bold; text-transform: uppercase; }
                .priority.high { color: #dc3545; }
                .priority.medium { color: #ffc107; }
                .priority.low { color: #28a745; }
                .priority.urgent { color: #6f42c1; }
                .overdue { color: #dc3545; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .btn { display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚠️ Overdue Task Alert</h1>
                </div>
                <div class='content'>
                    <p>Hello {$user->getFullName()},</p>
                    <p><strong class='overdue'>Your task is now overdue!</strong> Please take immediate action:</p>
                    
                    <div class='task-details'>
                        <h3>{$task->getTitle()}</h3>
                        <p><strong>Description:</strong> {$task->getDescription()}</p>
                        <p><strong>Priority:</strong> <span class='priority {$task->getPriority()}'>{$task->getPriority()}</span></p>
                        <p><strong>Was Due:</strong> {$dueDateFormatted}</p>
                        <p><strong class='overdue'>Overdue by:</strong> {$timeOverdue}</p>
                        <p><strong>Status:</strong> {$task->getStatus()}</p>
                    </div>
                    
                    <p>Please complete this task as soon as possible to avoid further delays.</p>
                    <p><a href='http://localhost:8080/task-manager' class='btn'>Complete Task Now</a></p>
                </div>
                <div class='footer'>
                    <p>This is an automated alert from Task Manager System</p>
                    <p>If you no longer wish to receive these emails, please contact your administrator</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getTasksNeedingReminders(int $hoursBeforeDue): array
    {
        // Get tasks that are due within the specified hours and haven't been reminded yet
        $targetTime = new \DateTime();
        $targetTime->modify("+{$hoursBeforeDue} hours");
        
        return $this->taskService->getTasksDueWithinHours($hoursBeforeDue, false); // false = not reminded yet
    }

    private function getOverdueTasks(): array
    {
        return $this->taskService->getOverdueTasks();
    }

    private function markReminderSent(Task $task): void
    {
        $this->taskService->markReminderSent($task->getId());
    }

    /**
     * Send test email to verify configuration
     */
    public function sendTestEmail(string $toEmail, string $toName = 'Test User'): bool
    {
        try {
            $subject = "Test Email from Task Manager";
            $body = "
            <h2>Email Configuration Test</h2>
            <p>If you're reading this, your email configuration is working correctly!</p>
            <p>Sent at: " . date('Y-m-d H:i:s') . "</p>
            ";

            $message = (new Swift_Message($subject))
                ->setFrom([$this->config['mail']['from']['email'] => $this->config['mail']['from']['name']])
                ->setTo([$toEmail => $toName])
                ->setBody($body, 'text/html');

            return $this->mailer->send($message) > 0;
        } catch (\Exception $e) {
            error_log("Test email failed: " . $e->getMessage());
            return false;
        }
    }
}