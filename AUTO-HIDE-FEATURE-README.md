# Auto-Hide Feature for Resolved Tickets

## Overview
This feature automatically hides resolved tickets that are older than 1 day (24 hours) from both manager and staff views. This keeps the dashboard clean and focused on active tickets while maintaining a complete audit trail in the database.

## What It Does
- **Automatically hides** resolved tickets older than 24 hours from all views
- **Keeps recent resolved tickets** visible (within 24 hours of resolution)
- **Applies to both** manager and staff dashboards
- **Maintains data integrity** - tickets are not deleted, just filtered from views
- **Updates automatically** - no manual intervention required

## How It Works
The feature uses a SQL WHERE clause that filters out resolved tickets based on their `updated_at` timestamp:

```sql
WHERE (t.status != "resolved" OR t.updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY))
```

This means:
- ✅ **Visible**: All non-resolved tickets (new, in-progress, closed)
- ✅ **Visible**: Resolved tickets updated within the last 24 hours
- ❌ **Hidden**: Resolved tickets older than 24 hours

## Files Modified

### 1. API Endpoints
- **`api/tickets-list.php`** - Main ticket listing endpoint
- **`api/tickets-check.php`** - Ticket count and recent tickets endpoint

### 2. Frontend Updates
- **`dashboard.html`** - Added informational note about auto-hide feature
- **`staff-dashboard.html`** - Added informational note about auto-hide feature

### 3. Test Files
- **`test-auto-hide.php`** - Test script to verify the feature is working

## Benefits

### For Managers
- Cleaner dashboard with focus on active tickets
- Reduced visual clutter from old resolved tickets
- Better overview of current workload and priorities

### For Staff
- Focus on tickets that need attention
- Clear view of their current assignments
- Reduced confusion from old resolved tickets

### For System Performance
- Faster ticket loading (fewer records to process)
- Reduced memory usage in frontend
- Better user experience

## Technical Details

### Database Impact
- **No data loss**: All tickets remain in the database
- **No schema changes**: Uses existing `updated_at` field
- **Performance**: Minimal impact on query performance

### Frontend Impact
- **Automatic**: No changes needed in JavaScript filtering
- **Dashboard counts**: Automatically reflect filtered data
- **User experience**: Seamless operation

## Testing

### Run the Test Script
```bash
# Navigate to your project directory
cd /path/to/hima-support

# Run the test script
php test-auto-hide.php
```

### What the Test Shows
1. **Visible tickets** - Tickets that will appear in dashboards
2. **Hidden tickets** - Resolved tickets older than 24 hours
3. **Summary counts** - Total vs. visible vs. hidden counts

## Configuration

### Time Interval
Currently set to **1 day (24 hours)**. To change this:

1. Edit `api/tickets-list.php` line 40:
```php
$where[] = '(t.status != "resolved" OR t.updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY))';
```

2. Edit `api/tickets-check.php` lines 12 and 18:
```php
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM tickets WHERE (status != "resolved" OR updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY))');
// ... and ...
$stmt = $pdo->prepare('SELECT id, ticket_code, mobile_or_user_id, issue_type, status, created_at FROM tickets WHERE (status != "resolved" OR updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) ORDER BY created_at DESC LIMIT 20');
```

### Available Intervals
- `INTERVAL 1 HOUR` - Hide after 1 hour
- `INTERVAL 6 HOUR` - Hide after 6 hours
- `INTERVAL 1 DAY` - Hide after 1 day (current)
- `INTERVAL 2 DAY` - Hide after 2 days
- `INTERVAL 1 WEEK` - Hide after 1 week

## User Experience

### Dashboard Information
Both manager and staff dashboards now display an informational note explaining the auto-hide feature:

> **Auto-hide Feature:** Resolved tickets older than 1 day are automatically hidden from your view to keep the dashboard clean and focused on active tickets.

### Status Filtering
The existing status filter dropdown still works normally:
- **All Status** - Shows all visible tickets (including recent resolved)
- **Resolved** - Shows only resolved tickets that are still visible
- **Other statuses** - Work as before

## Troubleshooting

### If Tickets Are Missing
1. Check if they have `status = "resolved"`
2. Check if `updated_at` is older than 24 hours
3. Run the test script to verify filtering logic

### If Feature Isn't Working
1. Verify database has `updated_at` field
2. Check API endpoint responses
3. Clear browser cache and refresh
4. Check browser console for errors

## Future Enhancements

### Potential Improvements
1. **Configurable time intervals** via admin settings
2. **User preference** to show/hide old resolved tickets
3. **Archive view** to access hidden resolved tickets
4. **Email notifications** when tickets are auto-hidden
5. **Audit log** of auto-hidden tickets

### Database Optimization
1. **Index on** `(status, updated_at)` for better performance
2. **Partitioning** by status for large ticket volumes
3. **Cleanup job** to archive very old resolved tickets

## Support

For questions or issues with this feature:
1. Check the test script output
2. Review database ticket records
3. Check API endpoint responses
4. Review browser console for errors

---

**Note**: This feature is designed to be non-disruptive and maintain all existing functionality while providing a cleaner, more focused user experience.
