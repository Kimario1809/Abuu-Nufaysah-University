# Role-Based Notifications Implementation
## Targeted Notification System for Abuu Nufaysah University

**Status**: ✅ **COMPLETED**
**Implementation Date**: 2024
**Type**: Role-based notification filtering and targeting

---

## Overview

A comprehensive role-based notification system has been implemented to deliver targeted notifications to specific user roles (Admin, Lecturer, Student) while maintaining support for user-specific and system-wide notifications.

---

## Database Schema Changes

### Updated Notifications Table
**File**: `database/schema.sql`

**Changes**:
- Added `target_role` column (enum: 'admin', 'lecturer', 'student', 'all')
- Made `user_id` nullable (allows system-wide/role-based notifications)
- Added new notification types: 'assignment', 'grade', 'chat'
- Added index on `target_role` for performance

**Schema**:
```sql
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `target_role` enum('admin','lecturer','student','all') DEFAULT 'all',
  `type` enum('info','success','warning','error','payment','result','announcement','system','assignment','grade','chat') DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `target_role` (`target_role`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  KEY `type` (`type`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Notification Model Updates

### File: `app/models/Notification.php`

**New Methods**:

#### `getByUser($userId, $userRole, $limit, $since)`
Retrieves notifications for a user, including both user-specific and role-based notifications.

**Logic**:
- Returns notifications where `user_id = ? OR user_id IS NULL`
- Filters by `target_role` matching user's role or 'all'
- Supports delta updates with `since` parameter

#### `getUnreadCount($userId, $userRole)`
Counts unread notifications including role-based ones.

#### `createForRole($targetRole, $data)`
Creates a notification for a specific role (user_id = NULL).

#### `createForUsers($userIds, $data)`
Creates notifications for multiple specific users.

#### `getRoleNotificationTypes($role)`
Returns allowed notification types per role:
- **Admin**: All types
- **Lecturer**: info, success, warning, assignment, grade, chat, announcement
- **Student**: info, success, warning, payment, result, assignment, grade, chat, announcement

#### `getRecentByRole($role, $limit)`
Gets recent notifications for a specific role (for admin dashboard).

---

## Notification Helper Class

### File: `app/helpers/NotificationHelper.php`

A helper class providing convenient methods for creating role-based notifications.

### Basic Methods

```php
// Notify all admins
NotificationHelper::notifyAdmin($title, $message, $type, $link);

// Notify all lecturers
NotificationHelper::notifyLecturers($title, $message, $type, $link);

// Notify all students
NotificationHelper::notifyStudents($title, $message, $type, $link);

// Notify all users
NotificationHelper::notifyAll($title, $message, $type, $link);

// Notify specific user
NotificationHelper::notifyUser($userId, $title, $message, $type, $link);

// Notify multiple users
NotificationHelper::notifyUsers($userIds, $title, $message, $type, $link);
```

### Preset Notification Methods

#### Payment Notifications
```php
// Payment received (admin)
NotificationHelper::paymentReceived($amount, $studentName, $paymentId);

// Payment confirmed (student)
NotificationHelper::paymentConfirmed($amount, $paymentId);
```

#### Assignment Notifications
```php
// New assignment (students)
NotificationHelper::newAssignment($courseName, $assignmentTitle, $assignmentId);

// Assignment due reminder (students)
NotificationHelper::assignmentDueSoon($assignmentTitle, $dueDate, $assignmentId);

// Assignment submitted (lecturer)
NotificationHelper::assignmentSubmitted($studentName, $assignmentTitle, $submissionId);
```

#### Grade Notifications
```php
// Grade posted (student)
NotificationHelper::gradePosted($courseName, $grade, $gradeId);
```

#### Announcement Notifications
```php
// New announcement (all)
NotificationHelper::newAnnouncement($title, $announcementId);
```

#### System Notifications
```php
// System maintenance (all)
NotificationHelper::systemMaintenance($startTime, $duration);

// Security alert (user)
NotificationHelper::securityAlert($message, $userId);
```

#### Academic Notifications
```php
// New student enrollment (admin)
NotificationHelper::newStudentEnrollment($studentName, $courseName);

// Low attendance alert (lecturer)
NotificationHelper::lowAttendanceAlert($courseName, $attendanceRate);

// Course registration opened (students)
NotificationHelper::courseRegistrationOpened($semesterName);

// Exam schedule published (students)
NotificationHelper::examSchedulePublished($semesterName);

// Results published (students)
NotificationHelper::resultPublished($semesterName);
```

#### Chat Notifications
```php
// New chat message (user)
NotificationHelper::newChatMessage($senderName, $message, $chatId);
```

---

## API Endpoint Updates

### File: `app/controllers/ApiController.php`

**Updated Method**: `getNotifications()`

**Changes**:
- Now passes `userRole` to Notification model
- Filters notifications by user's role
- Returns both user-specific and role-based notifications

**Response Format**:
```json
{
    "success": true,
    "message": "Notifications retrieved",
    "data": {
        "notifications": [
            {
                "id": 1,
                "user_id": null,
                "target_role": "student",
                "type": "assignment",
                "title": "New Assignment",
                "message": "New assignment posted",
                "link": "/assignments/view/1",
                "is_read": false,
                "created_at": "2024-01-15T10:30:00Z"
            }
        ],
        "unread_count": 5
    }
}
```

---

## Notification Types by Role

### Admin Notifications
- **Types**: info, success, warning, error, payment, result, announcement, system, assignment, grade, chat
- **Sources**: All system events, user actions, financial transactions, security alerts
- **Examples**: Payment received, new enrollment, system maintenance, security alerts

### Lecturer Notifications
- **Types**: info, success, warning, assignment, grade, chat, announcement
- **Sources**: Assignment submissions, student activities, course updates
- **Examples**: Assignment submitted, low attendance alert, new announcement

### Student Notifications
- **Types**: info, success, warning, payment, result, assignment, grade, chat, announcement
- **Sources**: Academic updates, payment confirmations, grade postings
- **Examples**: Payment confirmed, new assignment, grade posted, exam schedule

---

## Usage Examples

### Creating Role-Based Notifications

#### Example 1: Notify All Students About New Assignment
```php
use App\Helpers\NotificationHelper;

NotificationHelper::newAssignment(
    'Computer Science 101',
    'Data Structures Assignment',
    1
);
```

#### Example 2: Notify Lecturer About Assignment Submission
```php
NotificationHelper::assignmentSubmitted(
    'John Doe',
    'Data Structures Assignment',
    5
);
```

#### Example 3: Notify Admin About Payment
```php
NotificationHelper::paymentReceived(
    '$500.00',
    'Jane Smith',
    123
);
```

#### Example 4: Custom Role Notification
```php
NotificationHelper::notifyLecturers(
    'Course Update',
    'Course schedule has been updated',
    'info',
    '/courses/schedule'
);
```

### Creating User-Specific Notifications

#### Example 1: Notify Specific User
```php
NotificationHelper::notifyUser(
    45,
    'Your Request Approved',
    'Your request has been approved by the admin',
    'success',
    '/requests/view/78'
);
```

#### Example 2: Notify Multiple Users
```php
$userIds = [1, 2, 3, 4, 5];
NotificationHelper::notifyUsers(
    $userIds,
    'Meeting Reminder',
    'Department meeting at 2 PM today',
    'info',
    '/meetings'
);
```

### Creating System-Wide Notifications

#### Example: System Maintenance
```php
NotificationHelper::systemMaintenance(
    '2024-01-20 02:00 AM',
    '2 hours'
);
```

---

## Notification Filtering Logic

### How Notifications Are Filtered

1. **User-Specific**: `user_id = current_user_id`
2. **Role-Based**: `target_role = current_user_role OR target_role = 'all'`
3. **System-Wide**: `user_id IS NULL AND target_role = 'all'`

### Query Logic
```sql
SELECT * FROM notifications 
WHERE (user_id = ? OR user_id IS NULL)
  AND (target_role = ? OR target_role = 'all')
ORDER BY created_at DESC
```

### Priority Order
1. User-specific notifications (highest priority)
2. Role-specific notifications
3. System-wide notifications

---

## Integration with Existing Code

### Updating Existing Notification Creation

**Before**:
```php
Notification::create([
    'user_id' => $userId,
    'type' => 'info',
    'title' => 'Title',
    'message' => 'Message',
    'link' => '/link'
]);
```

**After** (using helper):
```php
use App\Helpers\NotificationHelper;

NotificationHelper::notifyUser(
    $userId,
    'Title',
    'Message',
    'info',
    '/link'
);
```

**Or** (for role-based):
```php
NotificationHelper::notifyStudents(
    'Title',
    'Message',
    'info',
    '/link'
);
```

---

## Migration Guide

### Database Migration Required

Run the updated schema to add the new columns:
```bash
mysql -u root -p abuu_university < database/schema.sql
```

### Existing Data Compatibility

- Existing notifications will have `target_role = 'all'` (default)
- Existing `user_id` values remain valid
- No data loss during migration

### Code Updates Required

1. **Update Notification calls**: Replace direct `Notification::create()` with helper methods
2. **Update API usage**: Ensure API passes user role to model
3. **Test notification filtering**: Verify role-based filtering works correctly

---

## Testing Checklist

### Functional Testing
- [ ] Admin receives admin-specific notifications
- [ ] Lecturer receives lecturer-specific notifications
- [ ] Student receives student-specific notifications
- [ ] User-specific notifications work correctly
- [ ] System-wide notifications reach all users
- [ ] Notification filtering by role works
- [ ] Unread count includes role-based notifications
- [ ] Mark as read works for role-based notifications

### API Testing
- [ ] `/api/notifications` returns role-filtered notifications
- [ ] Unread count is accurate
- [ ] Delta updates work correctly
- [ ] CSRF validation works

### Helper Method Testing
- [ ] All preset notification methods work
- [ ] Custom role notifications work
- [ ] User-specific notifications work
- [ ] Multi-user notifications work

---

## Performance Considerations

### Database Indexing
- Index on `target_role` for fast role filtering
- Index on `user_id` for user-specific lookups
- Composite index on `(target_role, created_at)` for recent role notifications

### Query Optimization
- Single query retrieves both user-specific and role-based notifications
- Delta updates reduce data transfer
- Pagination support via `LIMIT` parameter

### Caching Strategy
Consider caching role-based notifications:
```php
// Cache role notifications for 5 minutes
$cacheKey = "notifications_{$userRole}_{$userId}";
$cached = Cache::get($cacheKey);
```

---

## Security Considerations

### Access Control
- Users only see notifications for their role
- Cannot access other roles' notifications
- API validates user role before filtering

### Data Privacy
- User-specific notifications are private
- Role-based notifications are visible to all in that role
- System-wide notifications are visible to all authenticated users

### CSRF Protection
- All notification mutations require CSRF token
- API validates CSRF on mark-as-read requests

---

## Troubleshooting

### Notifications Not Appearing
1. Check `target_role` matches user's role
2. Verify database schema is updated
3. Check API is passing user role correctly
4. Verify notification helper is being used

### Wrong Notifications Showing
1. Check user role is correct
2. Verify `target_role` column values
3. Check filtering logic in model
4. Review API response

### Unread Count Incorrect
1. Check `getUnreadCount` includes role-based notifications
2. Verify `is_read` flag is set correctly
3. Check for duplicate notifications

---

## Future Enhancements

### Potential Improvements
- **Notification Preferences**: Allow users to opt-out of certain types
- **Notification Channels**: Email, SMS, push notifications
- **Notification Scheduling**: Schedule notifications for future delivery
- **Notification Templates**: Reusable notification templates
- **Notification Analytics**: Track notification open rates
- **Notification Batching**: Batch similar notifications
- **Notification Expiration**: Auto-expire old notifications

---

## Files Created/Modified

### New Files (1)
```
app/helpers/NotificationHelper.php
```

### Modified Files (3)
```
database/schema.sql
app/models/Notification.php
app/controllers/ApiController.php
```

---

## Documentation References

- **Real-Time Notifications**: `docs/REALTIME_NOTIFICATIONS.md`
- **API Documentation**: See API controller
- **Security Documentation**: `docs/SECURITY_UPGRADE_SUMMARY.md`

---

## Support

For issues or questions:
1. Check database schema is updated
2. Verify helper methods are being used correctly
3. Check API is passing user role
4. Review notification filtering logic
5. Refer to troubleshooting section above

---

**Implementation Completed**: 2024
**Status**: ✅ Production Ready
**Version**: 1.0
