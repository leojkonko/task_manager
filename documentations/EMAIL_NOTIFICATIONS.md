# Email Notifications System Documentation

## Overview

The Task Manager includes a complete email notification system that sends automated reminders and alerts to users about their tasks. The system uses Mailtrap for development/testing and can be easily configured for production email services.

## Features

### 📧 Email Notifications
- **Task Reminders** - Automated reminders sent 24 hours before due date (configurable)
- **Overdue Alerts** - Notifications for tasks that have passed their due date
- **Professional Templates** - HTML email templates with responsive design
- **Batch Processing** - Efficient batch sending to handle multiple notifications

### 🔧 Configuration Options
- **Enable/Disable** - Turn notifications on/off globally
- **Reminder Timing** - Configure how many hours before due date to send reminders
- **Batch Size** - Control how many emails are sent per batch
- **SMTP Settings** - Full SMTP configuration for any email provider

### 🎨 Email Templates
- **Task Reminders** - Friendly reminders with task details and countdown
- **Overdue Alerts** - Urgent notifications with overdue time calculation
- **Professional Design** - Clean, responsive HTML templates
- **Branded** - Consistent with Task Manager branding

## Configuration

### 1. Mailtrap Setup (Development)

1. **Create Mailtrap Account**: Go to [https://mailtrap.io](https://mailtrap.io)
2. **Get Credentials**: In your inbox, find SMTP settings
3. **Configure Application**: Update `config/autoload/global.php`:

```php
'mail' => [
    'transport' => [
        'type' => 'smtp',
        'options' => [
            'host' => 'sandbox.smtp.mailtrap.io',
            'port' => 2525,
            'connection_class' => 'login',
            'connection_config' => [
                'username' => 'your_mailtrap_username',
                'password' => 'your_mailtrap_password',
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
        'reminder_hours_before' => 24,
        'batch_size' => 50,
    ],
],
```

### 2. Production Email Setup

For production, update the configuration with your email provider settings:

```php
// Example for Gmail SMTP
'mail' => [
    'transport' => [
        'type' => 'smtp',
        'options' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'connection_class' => 'login',
            'connection_config' => [
                'username' => 'your-email@gmail.com',
                'password' => 'your-app-password',
                'ssl' => 'tls',
            ],
        ],
    ],
    'from' => [
        'email' => 'notifications@yourcompany.com',
        'name' => 'Your Company Task Manager',
    ],
    // ...rest of config
],
```

## Installation & Setup

### 1. Install Dependencies
The SwiftMailer library is already included in the project:
```bash
composer install
```

### 2. Run Email Notifications Migration
```bash
composer setup-email
# OR manually:
php scripts/setup/migrate_email_notifications.php
```

This will:
- Add `reminder_sent` column to tasks table
- Create database indexes for better performance
- Prepare the system for email notifications

### 3. Test Email Configuration
```bash
composer test-email your-email@example.com
# OR manually:
php scripts/debug/test_email.php your-email@example.com
```

## Usage

### Manual Execution
Send notifications manually:
```bash
composer send-notifications
# OR manually:
php scripts/maintenance/send_email_notifications.php
```

### Automated Execution (Recommended)

#### Option 1: Cron Job (Linux/Mac)
Add to your crontab to run every hour:
```bash
# Edit crontab
crontab -e

# Add this line (runs every hour)
0 * * * * cd /path/to/task-manager && php scripts/maintenance/send_email_notifications.php
```

#### Option 2: Windows Task Scheduler
1. Open Task Scheduler
2. Create Basic Task
3. Set to run hourly
4. Action: Start a program
5. Program: `php`
6. Arguments: `scripts/maintenance/send_email_notifications.php`
7. Start in: `C:\path\to\task-manager`

#### Option 3: Manual Scheduling
Run the command periodically based on your needs:
```bash
# Every hour
php scripts/maintenance/send_email_notifications.php

# Or create a batch script for Windows
# Create send_notifications.bat:
@echo off
cd "C:\path\to\task-manager"
php scripts/maintenance/send_email_notifications.php
```

## Email Types

### 1. Task Reminder Emails
- **Trigger**: 24 hours before due date (configurable)
- **Recipients**: Task owner
- **Content**: Task details, due date, time remaining
- **Design**: Blue header, friendly tone
- **Action**: Link to view task in Task Manager

### 2. Overdue Task Alerts
- **Trigger**: When task passes due date
- **Recipients**: Task owner
- **Content**: Task details, overdue duration
- **Design**: Red header, urgent tone
- **Action**: Link to complete task immediately

## Database Schema

### Tasks Table Updates
```sql
-- New column added for tracking reminders
ALTER TABLE tasks ADD COLUMN reminder_sent BOOLEAN DEFAULT FALSE;

-- Indexes for performance
CREATE INDEX idx_tasks_due_date_reminder ON tasks(due_date, reminder_sent, status);
CREATE INDEX idx_tasks_status_due_date ON tasks(status, due_date);
```

## API Integration

The email notification system integrates seamlessly with the existing Task Manager API and doesn't require additional endpoints. Notifications are triggered automatically based on task due dates and status.

## Configuration Options

### Global Settings (`config/autoload/global.php`)

```php
'mail' => [
    'notifications' => [
        'enabled' => true,                    // Enable/disable all notifications
        'reminder_hours_before' => 24,        // Hours before due date to send reminder
        'batch_size' => 50,                   // Max emails per batch execution
    ],
],
```

### Email Provider Settings

#### Mailtrap (Development)
```php
'transport' => [
    'options' => [
        'host' => 'sandbox.smtp.mailtrap.io',
        'port' => 2525,
        'connection_config' => [
            'username' => 'your_username',
            'password' => 'your_password',
            'ssl' => 'tls',
        ],
    ],
],
```

#### Gmail (Production)
```php
'transport' => [
    'options' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'connection_config' => [
            'username' => 'your-email@gmail.com',
            'password' => 'your-app-password',
            'ssl' => 'tls',
        ],
    ],
],
```

#### SendGrid (Production)
```php
'transport' => [
    'options' => [
        'host' => 'smtp.sendgrid.net',
        'port' => 587,
        'connection_config' => [
            'username' => 'apikey',
            'password' => 'your-sendgrid-api-key',
            'ssl' => 'tls',
        ],
    ],
],
```

## Troubleshooting

### Common Issues

**1. "No tasks requiring reminders found"**
- This is normal if no tasks are due within the reminder timeframe
- Check that you have tasks with due dates set

**2. "Email notifications are disabled"**
- Check `config/autoload/global.php` and ensure `notifications.enabled` is `true`

**3. "Failed to send email"**
- Verify SMTP credentials in configuration
- Test with: `composer test-email your-email@example.com`
- Check Mailtrap inbox for test emails

**4. "Class not found" errors**
- Run: `composer dump-autoload`
- Ensure migration was run: `composer setup-email`

**5. Database errors**
- Run migration: `php scripts/setup/migrate_email_notifications.php`
- Check that `reminder_sent` column exists in tasks table

### Debug Mode

Enable detailed logging by setting error reporting in the notification script:
```php
// Add at top of send_email_notifications.php for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Manual Testing

1. **Create a test task with due date tomorrow**:
```sql
INSERT INTO tasks (title, description, due_date, user_id, status, priority, reminder_sent) 
VALUES ('Test Reminder', 'This is a test task', DATE_ADD(NOW(), INTERVAL 1 DAY), 1, 'pending', 'medium', 0);
```

2. **Run notification script**:
```bash
php scripts/maintenance/send_email_notifications.php
```

3. **Check Mailtrap inbox** for the reminder email

## Performance Considerations

### Batch Processing
- The system processes emails in batches (default: 50 emails per run)
- Adjust `batch_size` in configuration based on your email provider limits
- Run more frequently for larger batch sizes

### Database Optimization
- Indexes are automatically created for optimal query performance
- The `reminder_sent` flag prevents duplicate reminder emails
- Queries are optimized to handle large numbers of tasks efficiently

### Memory Usage
- The system is designed to handle large numbers of tasks efficiently
- Memory usage scales with batch size, not total number of tasks

## Security Features

### Email Security
- Uses secure SMTP with TLS encryption
- No sensitive data exposed in email content
- HTML email templates are sanitized

### Data Protection
- Only task owners receive notifications about their tasks
- User isolation is maintained at the database level
- No cross-user data leakage possible

### Rate Limiting
- Batch processing prevents email provider rate limit issues
- Configurable batch sizes for different providers
- Built-in error handling for failed sends

## Future Enhancements

### Planned Features
- [ ] Email templates customization interface
- [ ] Multiple notification preferences per user
- [ ] Digest emails (daily/weekly summaries)
- [ ] Mobile push notifications
- [ ] Slack/Teams integration
- [ ] Email unsubscribe functionality

### Configuration Expansion
- [ ] Per-user notification preferences
- [ ] Multiple reminder intervals
- [ ] Custom email templates
- [ ] Time zone aware notifications

## Support

For issues with email notifications:

1. **Check Configuration**: Verify all settings in `global.php`
2. **Test Email**: Run `composer test-email your-email@example.com`
3. **Check Logs**: Look for errors in PHP error logs
4. **Verify Database**: Ensure migration completed successfully
5. **Contact Support**: Include error messages and configuration details

---

**Note**: This email notification system is production-ready and follows industry best practices for automated email systems, security, and performance.