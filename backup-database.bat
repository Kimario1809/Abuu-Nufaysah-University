@echo off
REM Production Database Backup Script for Windows
REM Abuu Nufaysah University System

REM Configuration
set DB_HOST=localhost
set DB_PORT=3306
set DB_NAME=abuu_nufaysah_university
set DB_USER=root
set DB_PASS=
set BACKUP_DIR=.\backups
set RETENTION_DAYS=30

REM Create timestamp
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set TIMESTAMP=%datetime:~0,8%_%datetime:~8,6%
set BACKUP_FILE=%BACKUP_DIR%\%DB_NAME%_%TIMESTAMP%.sql
set COMPRESSED_FILE=%BACKUP_FILE%.gz

REM Create backup directory if it doesn't exist
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo Starting database backup...
echo Database: %DB_NAME%
echo Host: %DB_HOST%
echo Backup file: %COMPRESSED_FILE%

REM Perform database backup using mysqldump
mysqldump -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p%DB_PASS% ^
    --single-transaction ^
    --quick ^
    --lock-tables=false ^
    --routines ^
    --triggers ^
    --events ^
    %DB_NAME% | gzip > "%COMPRESSED_FILE%"

REM Check if backup was successful
if %ERRORLEVEL% EQU 0 (
    echo Backup completed successfully: %COMPRESSED_FILE%
    
    REM Calculate file size
    for %%F in ("%COMPRESSED_FILE%") do set FILE_SIZE=%%~zF
    echo Backup size: %FILE_SIZE% bytes
    
    REM Remove old backups beyond retention period (using PowerShell)
    echo Removing backups older than %RETENTION_DAYS% days...
    powershell -Command "Get-ChildItem '%BACKUP_DIR%\%DB_NAME%_*.sql.gz' | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-%RETENTION_DAYS%) } | Remove-Item"
    
    REM List current backups
    echo Current backups:
    dir "%BACKUP_DIR%\%DB_NAME%_*.sql.gz" /b 2>nul || echo No backups found
    
    exit /b 0
) else (
    echo ERROR: Backup failed!
    exit /b 1
)
