![alt text](image.png)
# Task Manager System

A complete task management system developed with PHP, MySQL and Laminas Framework, featuring secure authentication and modern UI design.

## 🚀 Features

### 🔐 Authentication System
- **Secure user login/registration** with password hashing
- **Session management** with automatic expiration (24 hours)
- **Brute force protection** - Account lockout after 5 failed attempts
- **Complete audit logs** for all authentication activities
- **User permission system** - Users can only access their own tasks

### ✅ Task Management
- Create, edit, view and delete tasks
- Task statuses: Pending, In Progress, Completed, Cancelled
- Priority levels: Low, Medium, High, Urgent
- Due dates with overdue task alerts
- Detailed descriptions for each task
- **User isolation** - Each user sees only their own tasks

### 🏷️ Category Management
- Create custom categories with colors
- Organize tasks by category
- Intuitive visual interface with real-time preview

### 📊 Dashboard and Statistics
- Visual counters by status
- Organized list with filters
- Highlight for overdue tasks
- Responsive and modern interface

## 🛠️ Technologies

- **Backend**: PHP 8.1+
- **Framework**: Laminas MVC Framework
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: Bootstrap 5, Font Awesome
- **Package Manager**: Composer
- **Security**: Password hashing, secure sessions, SQL injection prevention

## 📋 Prerequisites

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Composer
- Web server (Apache/Nginx with XAMPP/WAMP/LAMP) or PHP built-in server

## 🔧 Installation

### Step 1: Clone the Repository
```bash
git clone [REPOSITORY_URL]
cd task-manager
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Database Setup

#### Option A: Using XAMPP (Recommended for Windows)
1. Start Apache and MySQL in XAMPP Control Panel
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Create a new database named `task_manager`
4. Import the base structure from `database/setup.sql`

#### Option B: Using Command Line
```bash
# Create database and import structure
mysql -u root -p -e "CREATE DATABASE task_manager"
mysql -u root -p task_manager < database/setup.sql
```

### Step 4: Configure Database Connection
Copy and edit the database configuration file:
```bash
cp config/autoload/database.local.php.dist config/autoload/database.local.php
```

Edit `config/autoload/database.local.php` with your credentials:
```php
return [
    'db' => [
        'driver' => 'Pdo',
        'dsn' => 'mysql:dbname=task_manager;host=localhost;charset=utf8mb4',
        'username' => 'root',        // Your MySQL username
        'password' => '',            // Your MySQL password (empty for XAMPP default)
        'driver_options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ],
    ],
    // ...existing code...
];
```

### Step 5: Run Authentication Migration
The system includes a complete authentication system. Run the migration to set it up:

```bash
# Execute the authentication migration script
php migrate_auth.php
```

You should see output like:
```
=== Authentication System Migration ===
✓ Database connection established
✓ Authentication columns added to users table
✓ user_sessions table created
✓ password_reset_tokens table created
✓ email_verification_tokens table created
✓ auth_logs table created
✓ testuser created successfully
=== Migration Completed Successfully! ===
```

### Step 6: Verify Setup
Run the database verification script:
```bash
php check_database.php
```

### Step 7: Start the Server

#### Option A: Using XAMPP
- Place the project in `htdocs` folder
- Access: `http://localhost/task-manager/public`

#### Option B: Using PHP Built-in Server
```bash
composer serve
# Or manually:
php -S localhost:8080 -t public
```

### Step 8: Access the System
Open your browser and go to: `http://localhost:8080`

You'll be automatically redirected to the login page.

## 🔑 Default Login Credentials

After running the migration, you can login with:

```
Username: testuser
Password: password123
```

Or create a new account using the registration page.

## 📁 Project Structure

```
task-manager/
├── config/                     # Application configurations
│   ├── autoload/              # Auto-loaded configurations
│   │   ├── database.local.php # Database connection settings
│   │   └── global.php         # Global settings
│   ├── application.config.php # Main application config
│   └── modules.config.php     # Enabled modules
├── database/                   # Database scripts
│   ├── setup.sql              # Base structure and initial data
│   └── auth_migration.sql     # Authentication system migration
├── module/
│   ├── Application/           # Main application module
│   ├── Auth/                  # Authentication module
│   │   ├── src/
│   │   │   ├── Controller/    # Auth controllers (login, register, logout)
│   │   │   ├── Service/       # Authentication services
│   │   │   ├── Model/         # User model
│   │   │   └── Factory/       # Service factories
│   │   └── view/              # Auth templates (login/register pages)
│   └── TaskManager/           # Task management module
│       ├── src/
│       │   ├── Controller/    # Task controllers
│       │   ├── Service/       # Business logic
│       │   ├── Entity/        # Task entities
│       │   └── Form/          # Forms and validation
│       └── view/              # Task templates
├── public/                    # Public web files
│   ├── index.php             # Application entry point
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript files
│   └── img/                  # Images
├── migrate_auth.php          # Authentication setup script
├── check_database.php        # Database verification script
└── vendor/                   # Composer dependencies
```

## 🎯 How to Use

### Authentication
1. **Registration**: Create a new account at `/auth/register`
2. **Login**: Sign in at `/auth/login` 
3. **Logout**: Use the logout button in the navigation
4. **Security**: Accounts are locked after 5 failed login attempts for 30 minutes

### Managing Tasks
1. **Create New Task**: Click "New Task" in the navigation
2. **View Tasks**: The dashboard shows all your tasks with statistics
3. **Edit Task**: Click the edit icon in the task list
4. **Mark as Complete**: Use the check button to toggle status
5. **Delete Task**: Click the trash icon (with confirmation)

### Managing Categories
1. **Create Category**: Go to "Categories" > "New Category"
2. **Choose Color**: Use the color picker for easy identification
3. **Live Preview**: See how the category will look in real-time
4. **Organize**: Associate tasks with categories during creation/editing

## 🔐 Security Features

- **Password Security**: Bcrypt hashing with salt
- **Session Security**: HttpOnly cookies, IP validation, User-Agent validation
- **Brute Force Protection**: Account lockout after failed attempts
- **SQL Injection Prevention**: Prepared statements
- **Access Control**: Users can only access their own data
- **Audit Trail**: Complete logging of authentication events

## 🗃️ Database Tables

### Core Tables
- `users` - User accounts and authentication data
- `tasks` - Task information and user associations
- `categories` - Task categories with colors

### Authentication Tables
- `user_sessions` - Active user sessions
- `auth_logs` - Authentication attempt logs
- `password_reset_tokens` - Password reset functionality (ready for future)
- `email_verification_tokens` - Email verification (ready for future)

## 🎨 UI Features

- **Responsive Design**: Works on desktop, tablet and mobile
- **Modern Theme**: Clean and professional interface
- **Visual Feedback**: Success/error messages
- **Intuitive Navigation**: Organized and accessible menu
- **Visual Indicators**: Colors for priorities and status
- **Alerts**: Highlighting for overdue tasks
- **English Interface**: Full English localization

## 🔧 Troubleshooting

### Common Issues

**"Module (Auth) could not be initialized"**
- Run: `composer dump-autoload`
- Ensure all Auth module files are present

**"Failed to create session"**
- Run: `php check_database.php` to verify database
- Run: `php migrate_auth.php` if tables are missing

**Login shows "Invalid username or password"**
- Verify user exists: `php check_database.php`
- Check database credentials in `config/autoload/database.local.php`

**Database connection errors**
- Verify MySQL is running (XAMPP Control Panel)
- Check database name, username, password in config file
- Ensure database `task_manager` exists

### Migration Issues
If migration fails, manually run these commands in phpMyAdmin:

```sql
-- Create user_sessions table
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

-- Add authentication columns to users table
ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL;
```

## 🚀 Quick Start Guide

1. **Install XAMPP** and start Apache + MySQL
2. **Clone project** to `htdocs/task-manager`
3. **Run**: `composer install`
4. **Create database** `task_manager` in phpMyAdmin
5. **Import** `database/setup.sql`
6. **Configure** database in `config/autoload/database.local.php`
7. **Run**: `php migrate_auth.php`
8. **Access**: `http://localhost/task-manager/public`
9. **Login** with `testuser` / `password123`

## 🤝 Contributing

1. Fork the project
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -m 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Open a Pull Request

## 📝 Future Features

- [ ] Password reset via email
- [ ] Email verification for new accounts
- [ ] Two-factor authentication (2FA)
- [ ] File attachments for tasks
- [ ] Task comments system
- [ ] Advanced filters and search
- [ ] Reports and analytics
- [ ] REST API
- [ ] Email notifications
- [ ] Custom themes

## 📄 License

This project is under the MIT License. See the [LICENSE.md](LICENSE.md) file for details.

## 👨‍💻 Developer

Developed with ❤️ following PHP best practices and Laminas Framework standards.

---

**Note**: This system was developed as a demonstration of PHP, MySQL and Laminas Framework competencies, following professional development standards with complete authentication and security features.
