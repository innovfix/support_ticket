# Auto-Cleanup Feature for Old Tickets

## Overview
This feature automatically hides and optionally removes resolved and closed tickets that are older than 2 days, ensuring your ticket list stays clean and focused on active issues.

## How It Works

### 1. Auto-Hide Logic
- **Resolved tickets**: Automatically hidden after 2 days
- **Closed tickets**: Automatically hidden after 2 days  
- **Active tickets**: Always visible (new, in-progress)
- **Time calculation**: Based on `updated_at` timestamp

### 2. Implementation Details
The auto-hide logic is implemented in these API endpoints:
- `api/tickets-list.php` - Main ticket listing
- `api/tickets-check.php` - Ticket count and recent tickets

### 3. Cleanup Options

#### Option A: Manual Cleanup
Call the cleanup API endpoint manually:
```
GET /api/cleanup-old-tickets.php
```

#### Option B: Automatic Cleanup (Recommended)
Set up a cron job to run daily:

**Cron Job Setup:**
1. Access your hosting control panel (cPanel, Plesk, etc.)
2. Go to **Cron Jobs** section
3. Add new cron job with these settings:
   - **Time**: `0 2 * * *` (runs daily at 2:00 AM)
   - **Command**: `php /path/to/your/hosting/directory/cron-cleanup-tickets.php`

**Example cron command:**
```bash
0 2 * * * php /home/username/public_html/query-desk/cron-cleanup-tickets.php
```

**Note**: Replace the path with your actual hosting directory path

## Files Modified/Created

### Modified Files:
- `api/tickets-list.php` - Updated auto-hide logic
- `api/tickets-check.php` - Updated auto-hide logic

### New Files:
- `api/cleanup-old-tickets.php` - Cleanup API endpoint
- `cron-cleanup-tickets.php` - Cron job script
- `AUTO-CLEANUP-FEATURE-README.md` - This documentation

## Benefits

1. **Cleaner Interface**: Old resolved/closed tickets don't clutter the view
2. **Better Performance**: Fewer tickets to load and display
3. **Focus on Active Issues**: Staff can focus on tickets that need attention
4. **Automatic Maintenance**: No manual cleanup required
5. **Configurable**: Easy to adjust the time period if needed

## Customization

To change the auto-hide period from 2 days to a different value:

1. Update the SQL queries in:
   - `api/tickets-list.php` (line ~40)
   - `api/tickets-check.php` (lines ~15-16)
   - `api/cleanup-old-tickets.php` (lines ~25, 35)

2. Change `INTERVAL 2 DAY` to your desired period:
   - `INTERVAL 1 DAY` - Hide after 1 day
   - `INTERVAL 3 DAY` - Hide after 3 days
   - `INTERVAL 1 WEEK` - Hide after 1 week

## Testing

To test the cleanup feature:

1. **Test Auto-Hide**: Create a ticket, mark it as resolved/closed, and wait for it to be hidden
2. **Test Manual Cleanup**: Call `/api/cleanup-old-tickets.php` directly
3. **Test Cron Job**: Run `php cron-cleanup-tickets.php` manually

## Monitoring

The cleanup operations are logged:
- **API calls**: Check your server's error log
- **Cron jobs**: Check `cron-cleanup.log` file in your project directory

## Security Notes

- The cleanup API endpoint can be called by anyone with access to the URL
- Consider adding authentication if needed
- The cron job script should be placed outside the public web directory if possible

## Support

If you encounter issues:
1. Check the server error logs
2. Verify the cron job is running (check `cron-cleanup.log`)
3. Test the manual cleanup endpoint
4. Ensure your hosting supports cron jobs
