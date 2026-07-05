# File Permission Security Requirements

## Overview
This document outlines the required file permissions for a secure production deployment of the Abuu Nufaysah University Management System.

## Security Principles
- **Principle of Least Privilege**: Files and directories should have the minimum permissions required
- **Separation of Concerns**: Different areas have different permission requirements
- **No World-Writable Files**: Prevent unauthorized modifications
- **Secure Sensitive Data**: Configuration files should be protected

## Directory Structure & Permissions

### Root Directory
```
/ (project root)
├── 755 - drwxr-xr-x
```

### Application Directory
```
/app/
├── 755 - drwxr-xr-x
├── /controllers
│   └── 644 - rw-r--r--
├── /models
│   └── 644 - rw-r--r--
├── /core
│   └── 644 - rw-r--r--
├── /middleware
│   └── 644 - rw-r--r--
└── /helpers
    └── 644 - rw-r--
```

### Configuration Files (CRITICAL)
```
/config/
├── 750 - drwxr-x---
├── app.php
│   └── 640 - rw-r-----
├── database.php
│   └── 640 - rw-r-----
└── session.php
    └── 640 - rw-r-----
```

### Environment Files (CRITICAL)
```
/.env
└── 600 - rw-------
/.env.example
└── 644 - rw-r--r--
```

### Public Directory
```
/public/
├── 755 - drwxr-xr-x
├── index.php
│   └── 644 - rw-r--r--
├── .htaccess
│   └── 644 - rw-r--r--
└── /assets
    ├── /css
    │   └── 644 - rw-r--r--
    ├── /js
    │   └── 644 - rw-r--r--
    └── /images
        └── 644 - rw-r--r--
```

### Storage Directory (WRITABLE)
```
/storage/
├── 775 - drwxrwxr-x
├── /framework
│   ├── /cache
│   │   └── 775 - drwxrwxr-x
│   ├── /sessions
│   │   └── 775 - drwxrwxr-x
│   └── /views
│       └── 775 - drwxrwxr-x
├── /logs
│   └── 775 - drwxrwxr-x
└── /uploads
    ├── /avatars
    │   └── 775 - drwxrwxr-x
    ├── /documents
    │   └── 775 - drwxrwxr-x
    └── /temp
        └── 775 - drwxrwxr-x
```

### Views Directory
```
/views/
├── 755 - drwxr-xr-x
├── /layouts
│   └── 644 - rw-r--r--
├── /auth
│   └── 644 - rw-r--r--
└── /errors
    └── 644 - rw-r--r--
```

### Database Directory
```
/database/
├── 755 - drwxr-xr-x
├── /migrations
│   └── 644 - rw-r--r--
├── /seeds
│   └── 644 - rw-r--r--
└── schema.sql
    └── 644 - rw-r--r--
```

### Bin Directory (Scripts)
```
/bin/
├── 755 - drwxr-xr-x
├── migrate
│   └── 755 - rwxr-xr-x
├── rotate-logs
│   └── 755 - rwxr-xr-x
├── backup-database
│   └── 755 - rwxr-xr-x
└── backup-database.bat
    └── 644 - rw-r--r--
```

### Logs Directory
```
/logs/
├── 750 - drwxr-x---
├── *.log
│   └── 640 - rw-r-----
```

### Vendor Directory (if using Composer)
```
/vendor/
├── 755 - drwxr-xr-x
└── (all files)
    └── 644 - rw-r--r--
```

## Permission Setting Commands

### Linux/Unix/macOS
```bash
# Set root directory permissions
chmod 755 /path/to/project

# Set application files
find /path/to/project/app -type f -exec chmod 644 {} \;
find /path/to/project/app -type d -exec chmod 755 {} \;

# Secure configuration files
chmod 750 /path/to/project/config
chmod 640 /path/to/project/config/*.php

# Secure environment file
chmod 600 /path/to/project/.env

# Set public directory
chmod 755 /path/to/project/public
find /path/to/project/public -type f -exec chmod 644 {} \;
find /path/to/project/public/assets -type d -exec chmod 755 {} \;

# Make storage writable by web server
chmod 775 /path/to/project/storage
find /path/to/project/storage -type d -exec chmod 775 {} \;
find /path/to/project/storage -type f -exec chmod 664 {} \;

# Set logs directory
chmod 750 /path/to/project/logs
chmod 640 /path/to/project/logs/*.log

# Make scripts executable
chmod 755 /path/to/project/bin/*
```

### Windows (IIS)
```powershell
# Set IIS user permissions
icacls "C:\path\to\project" /grant "IUSR:(OI)(CI)RX"
icacls "C:\path\to\project\storage" /grant "IUSR:(OI)(CI)M"
icacls "C:\path\to\project\logs" /grant "IUSR:(OI)(CI)M"

# Remove inheritance from sensitive files
icacls "C:\path\to\project\.env" /inheritance:r
icacls "C:\path\to\project\.env" /grant "Administrators:F"
```

## Web Server User

### Apache
- Default user: `www-data` (Debian/Ubuntu) or `apache` (RHEL/CentOS)
- Group: `www-data` or `apache`

### Nginx
- Default user: `www-data` (Debian/Ubuntu) or `nginx` (RHEL/CentOS)
- Group: `www-data` or `nginx`

### IIS
- Default user: `IUSR`
- Application Pool Identity: `IIS AppPool\DefaultAppPool`

## Ownership Commands

### Linux/Unix
```bash
# Set ownership to web server user
chown -R www-data:www-data /path/to/project/storage
chown -R www-data:www-data /path/to/project/logs

# Keep other directories owned by application user
chown -R youruser:yourgroup /path/to/project/app
chown -R youruser:yourgroup /path/to/project/config
chown -R youruser:yourgroup /path/to/project/views
```

## Security Checklist

- [ ] `.env` file is not accessible via web
- [ ] Configuration files are not world-readable
- [ ] Storage directory is writable by web server only
- [ ] Logs directory is not publicly accessible
- [ ] No files are world-writable (777)
- [ ] Scripts in `/bin` are executable
- [ ] Vendor directory has correct permissions
- [ ] Upload directories are properly restricted
- [ ] Session files directory is secure
- [ ] Cache directory is writable by web server

## Common Issues & Solutions

### Issue: File upload fails
**Solution**: Ensure upload directories have write permissions
```bash
chmod 775 /path/to/project/storage/uploads
chown www-data:www-data /path/to/project/storage/uploads
```

### Issue: Sessions not saving
**Solution**: Ensure session directory is writable
```bash
chmod 775 /path/to/project/storage/framework/sessions
chown www-data:www-data /path/to/project/storage/framework/sessions
```

### Issue: Cache not clearing
**Solution**: Ensure cache directory is writable
```bash
chmod 775 /path/to/project/storage/framework/cache
chown www-data:www-data /path/to/project/storage/framework/cache
```

### Issue: Logs not writing
**Solution**: Ensure logs directory has correct permissions
```bash
chmod 750 /path/to/project/logs
chown www-data:www-data /path/to/project/logs
```

## Automated Permission Script

### Linux/Unix
```bash
#!/bin/bash
PROJECT_PATH="/path/to/project"
WEB_USER="www-data"

echo "Setting file permissions..."

# Application files
find "$PROJECT_PATH/app" -type f -exec chmod 644 {} \;
find "$PROJECT_PATH/app" -type d -exec chmod 755 {} \;

# Configuration
chmod 750 "$PROJECT_PATH/config"
chmod 640 "$PROJECT_PATH/config"/*.php
chmod 600 "$PROJECT_PATH/.env"

# Public
chmod 755 "$PROJECT_PATH/public"
find "$PROJECT_PATH/public" -type f -exec chmod 644 {} \;
find "$PROJECT_PATH/public/assets" -type d -exec chmod 755 {} \;

# Storage (writable)
chmod 775 "$PROJECT_PATH/storage"
find "$PROJECT_PATH/storage" -type d -exec chmod 775 {} \;
find "$PROJECT_PATH/storage" -type f -exec chmod 664 {} \;
chown -R $WEB_USER:$WEB_USER "$PROJECT_PATH/storage"

# Logs
chmod 750 "$PROJECT_PATH/logs"
chmod 640 "$PROJECT_PATH/logs"/*.log
chown -R $WEB_USER:$WEB_USER "$PROJECT_PATH/logs"

# Scripts
chmod 755 "$PROJECT_PATH/bin"/*

echo "Permissions set successfully."
```

## Monitoring & Auditing

### Regular Permission Checks
```bash
# Find world-writable files
find /path/to/project -perm -o+w -type f

# Find world-writable directories
find /path/to/project -perm -o+w -type d

# Find files with incorrect ownership
find /path/to/project ! -user www-data ! -user root
```

### Automated Monitoring
Set up cron jobs to monitor permissions:
```bash
# Daily permission check
0 2 * * * /path/to/permission-check.sh
```

## References
- [OWASP File Permissions](https://owasp.org/www-community/controls/Secure_File_Upload)
- [Linux File Permissions](https://linux.die.net/man/1/chmod)
- [Apache Security Tips](https://httpd.apache.org/docs/2.4/misc/security_tips.html)
