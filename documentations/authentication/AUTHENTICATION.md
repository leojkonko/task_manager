# Authentication System Documentation

## Overview

The Task Manager includes a complete authentication system with secure login, registration, session management, and user access control. All authentication pages are in English and follow modern security practices.

## Features

### 🔐 Core Authentication
- **User Registration** - Create new accounts with validation
- **Secure Login** - Password-based authentication with bcrypt hashing
- **Session Management** - Secure sessions with 24-hour expiration
- **Logout** - Clean session termination

### 🛡️ Security Features
- **Password Security** - Bcrypt hashing with automatic salt generation
- **Brute Force Protection** - Account lockout after 5 failed login attempts (30 minutes)
- **Session Security** - HttpOnly cookies, IP validation, User-Agent validation
- **SQL Injection Prevention** - All queries use prepared statements
- **Access Control** - Users can only access their own data

### 📊 Audit & Monitoring
- **Authentication Logs** - Complete logging of all login attempts
- **Failed Attempt Tracking** - Monitor and prevent brute force attacks
- **Session Tracking** - Track active sessions with IP and User-Agent

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Sessions Table
```sql
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity DATETIME,
    created_at DATETIME,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Authentication Logs
```sql
CREATE TABLE auth_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50) NULL,
    action ENUM('login_success', 'login_failed', 'logout', 'password_reset', 'account_locked') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## Installation & Setup

### 1. Run Migration
Execute the authentication migration script:
```bash
php migrate_auth.php
```

This will:
- Add authentication columns to the users table
- Create session management tables
- Create audit logging tables
- Create a test user account

### 2. Verify Setup
Check that everything was created correctly:
```bash
php check_database.php
```

### 3. Test Login
Default test credentials:
- **Username**: `testuser`
- **Password**: `password123`

## Usage

### Registration Flow
1. User visits `/auth/register`
2. Fills out registration form (username, email, password, full name)
3. System validates input and checks for existing users
4. Password is hashed using bcrypt
5. User account is created with `active` status
6. Redirect to login page with success message

### Login Flow
1. User visits `/auth/login` (or is redirected from protected pages)
2. Enters username/email and password
3. System validates credentials and checks account status
4. If successful, creates secure session
5. Redirects to task dashboard
6. If failed, increments failed attempt counter

### Session Management
- Sessions expire after 24 hours
- Session IDs are cryptographically secure (128 characters)
- Sessions track IP address and User-Agent for security
- Automatic cleanup of expired sessions

### Logout Flow
1. User clicks logout button
2. Session is destroyed in database
3. Session cookie is cleared
4. Redirect to login page with success message

## Security Measures

### Password Security
- **Bcrypt Hashing** - Industry standard password hashing
- **Automatic Salt** - Each password gets unique salt
- **Cost Factor 10** - Balanced security and performance

### Brute Force Protection
- **Failed Attempt Tracking** - Count stored per user
- **Account Lockout** - 5 failed attempts = 30 minute lockout
- **Reset on Success** - Counter resets after successful login

### Session Security
- **HttpOnly Cookies** - Prevents JavaScript access
- **Secure Flag** - HTTPS-only when available
- **SameSite Strict** - CSRF protection
- **IP Validation** - Sessions tied to originating IP
- **User-Agent Validation** - Additional identity verification

### Access Control
- **Authentication Required** - All task pages require login
- **User Isolation** - Users see only their own data
- **Permission Checks** - Every operation validates ownership

## API Endpoints

### Authentication Routes
- `GET /auth/login` - Login page
- `POST /auth/login` - Process login
- `GET /auth/register` - Registration page
- `POST /auth/register` - Process registration
- `POST /auth/logout` - Logout and destroy session

### Protected Routes
All `/tasks/*` routes require authentication and will redirect to login if not authenticated.

## Error Handling

### Common Error Messages
- **"Please fill in all fields"** - Missing required form data
- **"Invalid username or password"** - Wrong credentials
- **"Username or email already exists"** - Registration conflict
- **"You need to be logged in to access this page"** - Auth required
- **"You do not have permission to view this task"** - Access denied

### Account Lockout
When an account is locked:
- Login attempts show "Invalid username or password" (no indication of lockout)
- Account unlocks automatically after 30 minutes
- Successful login immediately unlocks account

## Monitoring & Logs

### Authentication Events Logged
- `login_success` - Successful login
- `login_failed` - Failed login attempt
- `logout` - User logout
- `account_locked` - Account locked due to failed attempts

### Log Information Stored
- User ID (if known)
- Username attempted
- IP Address
- User Agent
- Timestamp
- Additional details (JSON format)

## Future Enhancements

The system is prepared for these additional features:

### Password Reset
- Tables: `password_reset_tokens`
- Email-based password reset flow
- Secure token generation and validation

### Email Verification
- Tables: `email_verification_tokens`
- Account verification via email
- Enhanced security for new registrations

### Two-Factor Authentication
- Additional table for 2FA secrets
- TOTP or SMS-based verification
- Backup codes for recovery

## Configuration

### Session Settings
```php
// Session cookie configuration
setcookie('auth_session', $sessionId, [
    'expires' => time() + 86400,    // 24 hours
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

### Security Constants
```php
private int $maxFailedAttempts = 5;      // Failed attempts before lockout
private int $lockoutDuration = 1800;     // 30 minutes lockout
```

## Troubleshooting

### Common Issues

**Cannot access auth pages**
- Check that Auth module is loaded in `config/modules.config.php`
- Run `composer dump-autoload`

**Session not working**
- Verify `user_sessions` table exists
- Check database connection
- Verify PHP session settings

**Authentication not working**
- Run `php check_database.php` to verify setup
- Check error logs for detailed messages
- Verify user exists and has correct status

**Account locked issues**
- Check `failed_login_attempts` and `locked_until` in users table
- Wait 30 minutes or reset manually in database
- Verify system time is correct

For additional help, see the main README.md troubleshooting section.