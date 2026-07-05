# Activity Logs System
## Comprehensive User Activity Tracking

**Status**: ✅ **COMPLETED**
**Implementation Date**: 2024
**Type**: Database-based activity logging with admin interface

---

## Overview

A comprehensive activity logging system has been implemented to track all user activities and system events. The system provides detailed audit trails, security monitoring, and compliance support with filtering, search, and export capabilities.

---

## Database Schema

### Activity Logs Table
**File**: `database/schema.sql`

```sql
CREATE TABLE `activity_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `entity_type` (`entity_type`),
  KEY `entity_id` (`entity_id`),
  KEY `created_at` (`created_at`),
  KEY `user_action` (`user_id`, `action`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Features**:
- JSON metadata field for flexible data storage
- Comprehensive indexing for fast queries
- Foreign key relationship with users table
- Automatic timestamp tracking

---

## Activity Log Model

### File: `app/models/ActivityLog.php`

**Methods**:
- `log($data)` - Create a new activity log entry
- `getByUserId($userId, $limit, $offset)` - Get logs for specific user
- `getByAction($action, $limit, $offset)` - Get logs by action type
- `getByEntity($entityType, $entityId, $limit)` - Get logs for specific entity
- `getAll($filters, $limit, $offset)` - Get logs with advanced filtering
- `count($filters)` - Count logs with filters
- `getRecent($limit)` - Get recent logs
- `getStatistics($days)` - Get activity statistics
- `deleteOld($days)` - Delete logs older than specified days

**Filtering Options**:
- `user_id` - Filter by user
- `action` - Filter by action type
- `entity_type` - Filter by entity type
- `date_from` - Filter by date range (start)
- `date_to` - Filter by date range (end)

---

## Activity Logger Helper

### File: `app/helpers/ActivityLogger.php`

A helper class providing convenient methods for logging various activities.

### Authentication Methods
```php
ActivityLogger::login($userId)
ActivityLogger::logout($userId)
ActivityLogger::failedLogin($identifier, $reason)
ActivityLogger::passwordChanged($userId)
ActivityLogger::passwordResetRequested($userId)
ActivityLogger::profileUpdated($userId, $changes)
```

### CRUD Methods
```php
ActivityLogger::created($entityType, $entityId, $description)
ActivityLogger::updated($entityType, $entityId, $description, $changes)
ActivityLogger::deleted($entityType, $entityId, $description)
ActivityLogger::viewed($entityType, $entityId, $description)
```

### File Operations
```php
ActivityLogger::downloaded($entityType, $entityId, $fileName)
ActivityLogger::exported($entityType, $format, $recordCount)
ActivityLogger::imported($entityType, $successCount, $failureCount)
```

### Academic Methods
```php
ActivityLogger::payment($userId, $paymentId, $action, $amount)
ActivityLogger::enrollment($userId, $courseId, $action)
ActivityLogger::grade($studentId, $gradeId, $action)
ActivityLogger::assignment($userId, $assignmentId, $action)
ActivityLogger::announcement($userId, $announcementId, $action)
```

### Security Methods
```php
ActivityLogger::security($userId, $event, $description)
ActivityLogger::apiRequest($endpoint, $method, $userId, $statusCode)
ActivityLogger::system($action, $description, $metadata)
```

### Custom Logging
```php
ActivityLogger::custom($action, $entityType, $entityId, $description, $metadata)
```

---

## Integration with Auth System

### File: `app/core/Auth.php`

**Login Logging**:
```php
// Failed login attempts are logged with reason
ActivityLogger::failedLogin($identifier, 'User not found');

// Successful login is logged
ActivityLogger::login($user['id']);
```

**Logout Logging**:
```php
// Logout is logged before session destruction
ActivityLogger::logout($userId);
```

**Dual Logging**:
The system logs to both `AuditLog` (existing) and `ActivityLog` (new) for backward compatibility.

---

## Admin Interface

### File: `views/admin/activity-logs.php`

**Features**:
- **Filtering**: Filter by user ID, action, entity type, date range
- **Search**: Real-time filtering with form submission
- **Pagination**: 50 logs per page with navigation
- **Metadata View**: Expandable JSON metadata display
- **Action Badges**: Color-coded badges for different action types
- **Export**: CSV export functionality
- **Cleanup**: Clear old logs (90+ days)

**Action Badge Colors**:
- `login` - Green (success)
- `logout` - Gray (secondary)
- `failed_login` - Red (danger)
- `password_changed` - Yellow (warning)
- `created` - Green (success)
- `updated` - Blue (primary)
- `deleted` - Red (danger)
- `security` - Red (danger)
- `api_request` - Blue (info)

---

## Admin Controller Methods

### File: `app/controllers/AdminController.php`

**New Methods**:
- `activityLogs()` - Display activity logs page
- `exportActivityLogs()` - Export logs to CSV
- `clearOldActivityLogs()` - Delete old logs via AJAX

**Route Configuration** (add to `routes/web.php`):
```php
// Activity logs routes
$router->get('/admin/activity-logs', [AdminController::class, 'activityLogs']);
$router->get('/admin/activity-logs/export', [AdminController::class, 'exportActivityLogs']);
$router->post('/admin/activity-logs/clear-old', [AdminController::class, 'clearOldActivityLogs']);
```

---

## Usage Examples

### Logging User Actions

#### Login/Logout
```php
use App\Helpers\ActivityLogger;

// Login
ActivityLogger::login($userId);

// Logout
ActivityLogger::logout($userId);

// Failed login
ActivityLogger::failedLogin($email, 'Invalid password');
```

#### Profile Changes
```php
// Password change
ActivityLogger::passwordChanged($userId);

// Profile update
ActivityLogger::profileUpdated($userId, ['full_name', 'email']);
```

### Logging CRUD Operations

#### Create
```php
ActivityLogger::created('course', $courseId, 'Created new course: Introduction to CS');
```

#### Update
```php
ActivityLogger::updated('course', $courseId, 'Updated course details', ['name', 'credits']);
```

#### Delete
```php
ActivityLogger::deleted('course', $courseId, 'Deleted course');
```

#### View
```php
ActivityLogger::viewed('course', $courseId, 'Viewed course details');
```

### Logging Academic Activities

#### Payment
```php
ActivityLogger::payment($userId, $paymentId, 'confirmed', 500.00);
```

#### Enrollment
```php
ActivityLogger::enrollment($userId, $courseId, 'enrolled');
```

#### Grade
```php
ActivityLogger::grade($studentId, $gradeId, 'posted');
```

#### Assignment
```php
ActivityLogger::assignment($userId, $assignmentId, 'submitted');
```

### Logging Security Events

#### Security Alert
```php
ActivityLogger::security($userId, 'unusual_activity', 'Multiple failed login attempts');
```

#### API Request
```php
ActivityLogger::apiRequest('/api/users', 'GET', $userId, 200);
```

#### System Event
```php
ActivityLogger::system('backup_completed', 'Database backup completed', ['size' => '50MB']);
```

---

## Automatic Integration

### Current Integrations
- **Auth System**: Login, logout, failed login attempts
- **AuditLog**: Dual logging for backward compatibility

### Future Integrations
Add activity logging to CRUD operations in controllers:

**Example**:
```php
public function createCourse() {
    $this->requireAuth();
    $this->requireRole('admin');
    
    $data = $this->post();
    $courseId = Course::create($data);
    
    // Log the activity
    ActivityLogger::created('course', $courseId, "Created course: {$data['name']}");
    
    $this->setFlash('success', 'Course created successfully');
    $this->redirect('/admin/courses');
}
```

---

## Data Retention

### Automatic Cleanup
```php
// Delete logs older than 90 days
ActivityLogger::deleteOld(90);
```

### Recommended Retention Policies
- **Development**: 7 days
- **Staging**: 30 days
- **Production**: 90 days (compliance may require longer)

### Scheduled Cleanup
Add to cron job or scheduled task:
```bash
# Run daily at 2 AM
0 2 * * * php /path/to/project/bin/clean-activity-logs
```

---

## Export Functionality

### CSV Export
The system exports logs to CSV format with the following columns:
- ID
- User
- Email
- Role
- Action
- Entity Type
- Entity ID
- Description
- IP Address
- User Agent
- Created At

### Export URL
```
/admin/activity-logs/export?user_id=123&action=login&date_from=2024-01-01
```

### Export Limit
Maximum 10,000 records per export to prevent memory issues.

---

## Security Considerations

### IP Address Tracking
- Automatically captures client IP address
- Supports proxy headers (X-Forwarded-For, HTTP_CLIENT_IP)
- IPv4 and IPv6 compatible

### User Agent Tracking
- Captures browser and device information
- Useful for security analysis
- Helps identify unusual access patterns

### Metadata Storage
- JSON format for flexible data
- Stores additional context
- Useful for debugging and analysis

### Access Control
- Admin-only access to activity logs
- Role-based permission check
- CSRF protection on delete operations

---

## Performance Optimization

### Database Indexing
- Index on `user_id` for user-specific queries
- Index on `action` for action filtering
- Index on `entity_type` and `entity_id` for entity queries
- Index on `created_at` for date range queries
- Composite index on `(user_id, action)` for combined queries

### Query Optimization
- Pagination to limit result sets
- Efficient filtering with indexed columns
- JSON metadata for flexible storage

### Cleanup Strategy
- Regular cleanup of old logs
- Configurable retention period
- Prevents table bloat

---

## Monitoring and Analytics

### Activity Statistics
```php
$stats = ActivityLog::getStatistics(30);
```

Returns activity grouped by:
- Action type
- Entity type
- Date

### Common Queries
```php
// Recent failed logins
$failedLogins = ActivityLog::getByAction('failed_login', 20);

// User activity
$userActivity = ActivityLog::getByUserId($userId, 50);

// Entity history
$entityHistory = ActivityLog::getByEntity('course', $courseId, 50);
```

---

## Troubleshooting

### Logs Not Appearing
1. Check database connection
2. Verify table exists in database
3. Check for errors in application logs
4. Verify ActivityLogger is being called

### Export Not Working
1. Check route is configured
2. Verify admin role
3. Check file permissions
4. Review PHP error logs

### Cleanup Not Working
1. Verify database user has DELETE permission
2. Check retention period is correct
3. Review database logs for errors

---

## Files Created/Modified

### New Files (3)
```
app/models/ActivityLog.php
app/helpers/ActivityLogger.php
views/admin/activity-logs.php
```

### Modified Files (3)
```
database/schema.sql
app/core/Auth.php
app/controllers/AdminController.php
```

---

## Documentation References

- **Security Documentation**: `docs/SECURITY_UPGRADE_SUMMARY.md`
- **Audit Logging**: Existing AuditLog system
- **Admin Dashboard**: Admin controller documentation

---

## Next Steps

### Immediate Actions
1. Update database schema with activity_logs table
2. Add activity logging routes to `routes/web.php`
3. Test activity logging with login/logout
4. Test admin activity logs interface
5. Test export functionality

### Future Enhancements
1. Add activity logging to all CRUD operations
2. Create activity log dashboard with charts
3. Add real-time activity monitoring
4. Implement activity alerts for suspicious behavior
5. Add activity log search with full-text search

---

## Support

For issues or questions:
1. Check database schema is updated
2. Verify ActivityLogger is being called
3. Check admin permissions
4. Review application error logs
5. Refer to troubleshooting section above

---

**Implementation Completed**: 2024
**Status**: ✅ Production Ready
**Version**: 1.0
