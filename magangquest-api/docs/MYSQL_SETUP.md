# MySQL Setup Guide for Magang Quest - VPS Deployment

## Prerequisites
- Ubuntu 22.04+ (recommended) or similar Linux distribution
- Root or sudo access
- Domain/Subdomain pointed to server IP

## Step 1: Install MySQL Server

```bash
# Update package list
sudo apt-get update

# Install MySQL Server
sudo apt-get install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation
```

## Step 2: Configure MySQL

### Start MySQL Service
```bash
sudo systemctl enable mysql
sudo systemctl start mysql
sudo systemctl status mysql
```

### Create Database and User
```bash
sudo mysql
```

Inside MySQL shell:
```sql
-- Create database
CREATE DATABASE magangquest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace 'your_password' with a strong password)
CREATE USER 'magangquest'@'localhost' IDENTIFIED BY 'your_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON magangquest.* TO 'magangquest'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

## Step 3: Update .env Configuration

Edit `/path/to/magangquest-api/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magangquest
DB_USERNAME=magangquest
DB_PASSWORD=your_password
```

## Step 4: Install PHP MySQL Extension

```bash
# For PHP 8.x (adjust version as needed)
sudo apt-get install -y php-mysql

# Restart PHP-FPM if running
sudo systemctl restart php8.x-fpm  # adjust version
```

## Step 5: Run Migrations

```bash
cd /path/to/magangquest-api
php artisan migrate
```

## Step 6: Seed Database (Optional)

```bash
php artisan db:seed
```

## Step 7: Verify Installation

```bash
php artisan migrate:status
```

## Remote MySQL Access (Optional)

If you need to access MySQL remotely:

```bash
# Edit MySQL config
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Change bind-address from 127.0.0.1 to 0.0.0.0
bind-address = 0.0.0.0

# Restart MySQL
sudo systemctl restart mysql

# Grant remote access to user
sudo mysql
```

```sql
GRANT ALL PRIVILEGES ON magangquest.* TO 'magangquest'@'%' IDENTIFIED BY 'your_password';
FLUSH PRIVILEGES;
EXIT;
```

## Troubleshooting

### "Could not find driver"
```bash
# Install PDO MySQL extension
sudo apt-get install -y php-mysql
sudo systemctl restart php8.x-fpm
```

### "Access denied for user"
```sql
-- Reset password in MySQL
sudo mysql
ALTER USER 'magangquest'@'localhost' IDENTIFIED BY 'new_password';
FLUSH PRIVILEGES;
```

### Connection refused
```bash
# Check MySQL is running
sudo systemctl status mysql

# Check port is open
sudo lsof -i :3306
```

## Backup & Restore

### Backup
```bash
mysqldump -u magangquest -p magangquest > backup_$(date +%Y%m%d).sql
```

### Restore
```bash
mysql -u magangquest -p magangquest < backup_20260528.sql
```

## Security Recommendations

1. Use strong passwords for database users
2. Restrict remote access to trusted IPs only
3. Enable SSL/TLS for MySQL connections in production
4. Regular backups (automate with cron)
5. Keep MySQL updated: `sudo apt-get update && sudo apt-get upgrade mysql-server`
