# Quick Installation Guide

## Prerequisites

- **XAMPP** (recommended) or any PHP 8.1+ with MySQL
- **Composer** (PHP package manager)

## 🚀 Quick Setup (5 minutes)

### 1. Download & Install XAMPP
- Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
- Install and start **Apache** and **MySQL** in XAMPP Control Panel

### 2. Clone Project
```bash
git clone [YOUR_REPOSITORY_URL]
cd task-manager
```

### 3. Install Dependencies
```bash
composer install
```

### 4. Create Database
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Create new database: `task_manager`
- Import SQL file: `database/setup.sql`

### 5. Configure Database
Edit `config/autoload/database.local.php`:
```php
'username' => 'root',
'password' => '',        // Empty for XAMPP default
```

### 6. Setup Authentication
```bash
composer setup
```

### 7. Start Server
```bash
composer serve
```

### 8. Login
Open `http://localhost:8080` and login with:
- **Username:** `testuser`
- **Password:** `password123`

## That's it! 🎉

Your Task Manager is now running with complete authentication system.

---

## Alternative Commands

```bash
# Setup authentication only
composer setup-auth

# Verify database setup
composer check-db

# Start development server
composer serve
```

## Troubleshooting

**Database connection error?**
- Check XAMPP MySQL is running
- Verify database name is `task_manager`
- Check credentials in `config/autoload/database.local.php`

**Migration failed?**
- Run: `composer check-db`
- Try: `composer setup-auth`
- Manual SQL in phpMyAdmin (see main README.md)

**Cannot login?**
- Run: `composer check-db`
- Verify testuser exists
- Password is `password123`

For detailed documentation, see [README.md](README.md) and [AUTHENTICATION.md](AUTHENTICATION.md).