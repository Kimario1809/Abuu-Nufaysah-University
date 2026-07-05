# Production Deployment Guide
## Abuu Nufaysah University Management System

This guide provides step-by-step instructions for deploying the University Management System from XAMPP to a production server.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Environment Setup](#environment-setup)
4. [Database Configuration](#database-configuration)
5. [Application Deployment](#application-deployment)
6. [Web Server Configuration](#web-server-configuration)
7. [Security Hardening](#security-hardening)
8. [SSL/TLS Configuration](#ssltls-configuration)
9. [Monitoring & Maintenance](#monitoring--maintenance)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required Software
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.0+ or 8.1+
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Composer** (if using dependencies)
- **Git** (for version control)

### Required PHP Extensions
```bash
php-mysql
php-pdo
php-mbstring
php-json
php-curl
php-gd
php-zip
php-xml
php-bcmath
php-opcache
```

### System Requirements
- **RAM**: Minimum 2GB, Recommended 4GB+
- **Storage**: Minimum 20GB, Recommended 50GB+
- **CPU**: 2 cores minimum, 4 cores recommended

---

## Server Requirements

### Option 1: VPS/Cloud Server
Recommended providers:
- DigitalOcean (Ubuntu 20.04/22.04)
- Linode (Ubuntu 20.04/22.04)
- AWS EC2 (Ubuntu 20.04/22.04)
- Google Cloud Platform (Ubuntu 20.04/22.04)

### Option 2: Shared Hosting
Ensure the host supports:
- PHP 8.0+
- MySQL 5.7+
- SSH access
- .htaccess support (Apache)
- Cron job access

---

## Environment Setup

### 1. Update System Packages
```bash
# Ubuntu/Debian
sudo apt update
sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

### 2. Install Required Software

#### Ubuntu/Debian with Apache
```bash
sudo apt install apache2 mysql-server php8.0 php8.0-mysql php8.0-mbstring php8.0-json php8.0-curl php8.0-gd php8.0-zip php8.0-xml php8.0-bcmath php8.0-opcache -y
```

#### Ubuntu/Debian with Nginx
```bash
sudo apt install nginx mysql-server php8.0-fpm php8.0-mysql php8.0-mbstring php8.0-json php8.0-curl php8.0-gd php8.0-zip php8.0-xml php8.0-bcmath php8.0-opcache -y
```

### 3. Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 4. Configure PHP
Edit `/etc/php/8.0/apache2/php.ini` or `/etc/php/8.0/fpm/php.ini`:

```ini
; Security Settings
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; Session Security
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On
session.cookie_samesite = Strict

; Performance
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
upload_max_filesize = 10M
post_max_size = 10M

; OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
```

Restart PHP service:
```bash
sudo systemctl restart php8.0-fpm  # Nginx
sudo systemctl restart apache2    # Apache
```

---

## Database Configuration

### 1. Secure MySQL Installation
```bash
sudo mysql_secure_installation
```

### 2. Create Database and User
```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE abuu_nufaysah_university CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'university_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON abuu_nufaysah_university.* TO 'university_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Import Database Schema
```bash
mysql -u university_user -p abuu_nufaysah_university < database/schema.sql
```

### 4. Run Migrations (if applicable)
```bash
php bin/migrate
```

### 5. Run Seeders (if applicable)
```bash
php bin/seed
```

---

## Application Deployment

### 1. Deploy Application Files

#### Option A: Using Git
```bash
cd /var/www/
sudo git clone https://github.com/yourusername/abuu-nufaysah-university.git
cd abuu-nufaysah-university
```

#### Option B: Using SCP/SFTP
```bash
scp -r /local/path/to/project user@server:/var/www/abuu-nufaysah-university
```

### 2. Set File Permissions
```bash
cd /var/www/abuu-nufaysah-university

# Set ownership
sudo chown -R www-data:www-data .

# Set permissions
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;

# Make storage and logs writable
sudo chmod -R 775 storage
sudo chmod -R 775 logs
sudo chown -R www-data:www-data storage
sudo chown -R www-data:www-data logs

# Secure sensitive files
sudo chmod 600 .env
sudo chmod 750 config
sudo chmod 640 config/*.php
```

### 3. Configure Environment
```bash
cp .env.example .env
nano .env
```

Edit `.env` file:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=abuu_nufaysah_university
DB_USERNAME=university_user
DB_PASSWORD=strong_password_here
DB_CHARSET=utf8mb4

SESSION_LIFETIME=3600
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Strict

CSRF_TOKEN_LENGTH=32
RATE_LIMIT_MAX_ATTEMPTS=5
RATE_LIMIT_DECAY_MINUTES=15

LOG_LEVEL=error
LOG_PATH=/var/www/abuu-nufaysah-university/logs
```

### 4. Create Required Tables for Rate Limiting
```sql
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Web Server Configuration

### Apache Configuration

#### 1. Create Virtual Host
```bash
sudo nano /etc/apache2/sites-available/abuu-nufaysah.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/abuu-nufaysah-university/public

    <Directory /var/www/abuu-nufaysah-university/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/abuu-nufaysah_error.log
    CustomLog ${APACHE_LOG_DIR}/abuu-nufaysah_access.log combined
</VirtualHost>
```

#### 2. Enable Site and Modules
```bash
sudo a2ensite abuu-nufaysah.conf
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

### Nginx Configuration

#### 1. Create Server Block
```bash
sudo nano /etc/nginx/sites-available/abuu-nufaysah
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/abuu-nufaysah-university/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }

    location ~ /config/ {
        deny all;
    }

    location ~ /storage/ {
        deny all;
    }

    error_log /var/log/nginx/abuu-nufaysah_error.log;
    access_log /var/log/nginx/abuu-nufaysah_access.log;
}
```

#### 2. Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/abuu-nufaysah /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## Security Hardening

### 1. Configure Firewall
```bash
# Ubuntu/Debian (UFW)
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# CentOS/RHEL (firewalld)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 2. Install SSL Certificate (Let's Encrypt)

#### For Apache
```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

#### For Nginx
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 3. Auto-Renew SSL
```bash
sudo certbot renew --dry-run
```

Cron job for auto-renewal:
```bash
sudo crontab -e
```
Add:
```
0 0,12 * * * certbot renew --quiet
```

### 4. Update .htaccess for HTTPS
Uncomment HTTPS redirect in `public/.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 5. Enable HSTS (Optional)
Add to `.htaccess`:
```apache
Header set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

---

## SSL/TLS Configuration

### 1. Generate Strong SSL Configuration
Create `/etc/ssl/openssl.cnf`:
```ini
[system_default_sect]
MinProtocol = TLSv1.2
CipherString = DEFAULT@SECLEVEL=2
```

### 2. Test SSL Configuration
Use SSL Labs test: https://www.ssllabs.com/ssltest/

---

## Monitoring & Maintenance

### 1. Set Up Log Rotation
Create `/etc/logrotate.d/abuu-nufaysah`:
```
/var/www/abuu-nufaysah-university/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 2. Set Up Automated Backups
Add to crontab:
```bash
# Daily database backup at 2 AM
0 2 * * * /var/www/abuu-nufaysah-university/bin/backup-database

# Weekly log rotation
0 3 * * 0 /var/www/abuu-nufaysah-university/bin/rotate-logs
```

### 3. Monitor Server Resources
Install monitoring tools:
```bash
sudo apt install htop iotop nethogs -y
```

### 4. Set Up Uptime Monitoring
Use services like:
- UptimeRobot
- Pingdom
- StatusCake

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error
**Check**: Apache/Nginx error logs
```bash
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/nginx/error.log
```

**Common causes**:
- File permissions issue
- PHP syntax error
- Missing PHP extensions

#### 2. Database Connection Failed
**Check**: `.env` configuration
```bash
sudo mysql -u university_user -p abuu_nufaysah_university
```

**Common causes**:
- Wrong credentials
- Database not created
- MySQL not running

#### 3. Session Not Working
**Check**: Storage directory permissions
```bash
ls -la storage/framework/sessions
```

**Fix**:
```bash
sudo chmod 775 storage/framework/sessions
sudo chown www-data:www-data storage/framework/sessions
```

#### 4. File Upload Fails
**Check**: Upload directory permissions and PHP upload settings
```bash
php -i | grep upload
```

#### 5. CSRF Token Errors
**Check**: Session configuration and CSRF token generation

---

## Performance Optimization

### 1. Enable OPcache
Already configured in PHP settings above.

### 2. Enable MySQL Query Cache
Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
query_cache_type = 1
query_cache_size = 64M
```

### 3. Configure APCu (Alternative Cache)
```bash
sudo apt install php8.0-apcu -y
```

### 4. Enable Browser Caching
Already configured in `.htaccess`.

---

## Post-Deployment Checklist

- [ ] Application deployed to production server
- [ ] Database created and schema imported
- [ ] Environment variables configured
- [ ] File permissions set correctly
- [ ] SSL certificate installed
- [ ] HTTPS redirect enabled
- [ ] Firewall configured
- [ ] Automated backups set up
- [ ] Log rotation configured
- [ ] Monitoring tools installed
- [ ] Error logging tested
- [ ] File upload tested
- [ ] Login/logout tested
- [ ] CSRF protection tested
- [ ] Rate limiting tested
- [ ] API endpoints tested
- [ ] Database backup tested
- [ ] Restore procedure tested

---

## Emergency Procedures

### 1. Rollback to Previous Version
```bash
cd /var/www/abuu-nufaysah-university
sudo git checkout previous-commit-hash
sudo systemctl restart apache2  # or nginx
```

### 2. Restore Database from Backup
```bash
gunzip < backups/abuu_nufaysah_university_20231215_120000.sql.gz | mysql -u university_user -p abuu_nufaysah_university
```

### 3. Emergency Maintenance Mode
Create `maintenance.html` in public directory and update `.htaccess`:
```apache
RewriteCond %{REQUEST_URI} !/maintenance.html
RewriteRule ^(.*)$ /maintenance.html [L]
```

---

## Support & Resources

- **Documentation**: `/docs` directory
- **Logs**: `/logs` directory
- **Backups**: `/backups` directory
- **Database Schema**: `/database/schema.sql`

For issues or questions, contact the development team or refer to the project repository.

---

**Last Updated**: 2024
**Version**: 1.0
