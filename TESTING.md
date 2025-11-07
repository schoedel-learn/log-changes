# Testing Guide for Export and Auto-Delete Features

This document provides manual testing procedures for the new export and automatic deletion features added to the Log Changes plugin.

## Features to Test

### 1. Export to CSV
**Purpose**: Export all logs or filtered logs to a CSV file for backup or external analysis (Google Sheets, Excel, etc.)

**Test Steps**:
1. Navigate to WordPress Admin → Change Log
2. (Optional) Apply filters: action type, object type, user, date range, or search
3. Click "Export to CSV" button
4. Verify that a CSV file downloads with timestamp in filename
5. Open the CSV file and verify:
   - All columns are present (ID, Timestamp, User ID, User Login, etc.)
   - Data matches what's shown in the admin interface
   - Special characters are properly encoded
   - File opens correctly in Excel/Google Sheets

**Expected Result**: CSV file downloads successfully with all log data matching applied filters.

### 2. Export & Delete
**Purpose**: Export logs to CSV and then delete them from the database to free up space

**Test Steps**:
1. Navigate to WordPress Admin → Change Log
2. Apply filters to select specific logs (e.g., date range from 3 weeks ago to 2 weeks ago)
3. Note the number of log entries shown
4. Click "Export & Delete" button
5. Confirm the warning dialog about deletion
6. Verify CSV file downloads
7. Wait for page to redirect
8. Verify success message shows correct number of deleted entries
9. Verify the deleted logs no longer appear in the log list
10. Open the CSV file and verify all deleted data is present

**Expected Result**: 
- CSV file downloads successfully
- Confirmation dialog appears before deletion
- Logs are deleted from database after export
- Success message displays correct count
- Deleted logs do not appear in the interface

### 3. Date Range Filters
**Purpose**: Filter logs by date range for targeted export/delete operations

**Test Steps**:
1. Navigate to WordPress Admin → Change Log
2. Enter a "From" date (e.g., 30 days ago)
3. Enter a "To" date (e.g., 7 days ago)
4. Click "Filter" button
5. Verify only logs within the date range are displayed
6. Test export with date filter applied
7. Reset filters and verify all logs appear again

**Expected Result**: Only logs within the specified date range are displayed and exported.

### 4. Automatic 21-Day Cleanup
**Purpose**: Automatically delete logs older than 21 days to prevent database bloat

**Test Approach**: Since this runs on WordPress cron (daily), testing requires either:

#### Option A: Manual Trigger (Recommended for testing)
```php
// Add this to functions.php temporarily or use WP-CLI
if ( is_admin() && current_user_can( 'manage_options' ) ) {
    add_action( 'admin_init', function() {
        if ( isset( $_GET['test_cleanup'] ) ) {
            $plugin = new Log_Changes();
            $deleted = $plugin->auto_cleanup_old_logs();
            wp_die( "Cleanup complete. Deleted: $deleted logs" );
        }
    });
}
// Then visit: /wp-admin/?test_cleanup=1
```

#### Option B: Wait for Natural Execution
1. Ensure WordPress cron is working (not disabled)
2. Check logs after 24+ hours
3. Verify logs older than 21 days are removed
4. Look for a "System" log entry documenting the cleanup

#### Option C: WP-CLI Testing
```bash
wp cron event run log_changes_auto_cleanup
```

**Manual Verification**:
1. Check that event is scheduled:
   ```php
   wp_next_scheduled( 'log_changes_auto_cleanup' )
   ```
2. Create test logs with old timestamps:
   ```sql
   INSERT INTO wp_change_log (timestamp, user_id, user_login, action_type, object_type, object_name, description)
   VALUES ('2024-10-01 12:00:00', 1, 'admin', 'test', 'test', 'Old Test Log', 'Test log older than 21 days');
   ```
3. Trigger cleanup manually
4. Verify old test log is deleted

**Expected Result**: 
- Logs older than 21 days are automatically deleted
- A system log entry records the cleanup action
- Recent logs (< 21 days) are preserved

### 5. Security Verification

**Test Steps**:
1. Log out of WordPress admin
2. Try to access export URL directly (copy from browser when logged in)
3. Verify access is denied
4. Try to modify nonce parameter in URL
5. Verify security check fails
6. Log in as a non-admin user (editor, author, etc.)
7. Navigate to Change Log page
8. Verify the page is not accessible

**Expected Result**: 
- Nonce verification prevents unauthorized access
- Only administrators can access export/delete functions
- Invalid nonces show error message

### 6. UI/UX Testing

**Test Steps**:
1. Verify all buttons have appropriate icons (dashicons)
2. Check that date inputs work properly
3. Verify the note about "Logs older than 21 days are automatically deleted" is visible
4. Test responsive layout on smaller screens
5. Verify success notice dismisses properly
6. Confirm filter buttons work as expected
7. Test keyboard navigation

**Expected Result**: UI is intuitive, responsive, and accessible.

## Edge Cases to Test

1. **Empty Database**: Export with no logs → Should show appropriate message
2. **Large Dataset**: Export 1000+ logs → CSV should generate without timeout
3. **Special Characters**: Logs with quotes, commas, newlines → CSV properly escapes data
4. **Concurrent Actions**: Multiple admins exporting simultaneously → Each gets their own file
5. **Filter Edge Cases**: 
   - Invalid date ranges (To before From)
   - Future dates
   - Missing one date field
6. **Cleanup Edge Cases**:
   - Database empty (no logs to delete)
   - All logs are recent (nothing to delete)
   - Exactly 21 days old (boundary condition)

## Automated Testing Notes

For future automated testing, consider implementing:
- PHPUnit tests for filter building logic
- Integration tests for export/delete operations
- Cron event simulation tests
- CSV generation validation

## Performance Considerations

Monitor the following during testing:
- CSV generation time for large datasets (>10,000 logs)
- Database query performance with filters
- Memory usage during export
- Page load time with cleanup scheduled

## Cleanup After Testing

Remember to:
1. Remove any test logs created manually
2. Remove test code from functions.php if added
3. Verify scheduled cron event is properly registered
4. Clear any dismissed admin notices

## Sign-off Checklist

- [ ] Export to CSV works with no filters
- [ ] Export to CSV works with all filter combinations
- [ ] Export & Delete properly exports before deleting
- [ ] Confirmation dialog appears for Export & Delete
- [ ] Deleted logs are removed from database
- [ ] Success message shows correct count
- [ ] Date range filters work correctly
- [ ] Automatic cleanup is scheduled on activation
- [ ] Cleanup deletes only logs older than 21 days
- [ ] Cleanup logs its own action
- [ ] Security (nonces) prevent unauthorized access
- [ ] Only admins can access export/delete functions
- [ ] UI is responsive and accessible
- [ ] CSV files open correctly in Excel and Google Sheets
- [ ] Edge cases handled gracefully

## Troubleshooting

### CSV Not Downloading
- Check PHP error logs for errors
- Verify file permissions
- Check for output buffering issues
- Ensure headers not already sent

### Cleanup Not Running
- Verify WP-Cron is not disabled (DISABLE_WP_CRON constant)
- Check scheduled events: `wp_get_scheduled_events()`
- Verify plugin is active
- Check server time vs. scheduled time

### Filters Not Working
- Clear browser cache
- Check for JavaScript errors in console
- Verify nonce is being generated
- Check URL parameters are preserved

## Support

For issues during testing, check:
1. PHP error log: `/var/log/php/error.log` or similar
2. WordPress debug log: `wp-content/debug.log` (if WP_DEBUG enabled)
3. Browser console for JavaScript errors
4. Database queries in Query Monitor plugin
