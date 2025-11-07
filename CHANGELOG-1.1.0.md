# Changelog for Version 1.1.0

## Release Date
TBD (Ready for deployment)

## Overview
Version 1.1.0 adds comprehensive export and automatic cleanup functionality to address database bloat concerns. All logs can now be exported to CSV for external storage (Google Sheets, Excel, etc.), and logs are automatically deleted after 21 days.

## New Features

### 1. Export to CSV
Download all logs or filtered results as a CSV file compatible with Excel and Google Sheets.

**Features:**
- UTF-8 BOM encoding for proper Excel compatibility
- All log fields included (ID, timestamp, user info, action, object, old/new values, IP, user agent)
- Timestamped filename (e.g., `change-logs-2024-11-07-15-30-45.csv`)
- Chunked processing (1000 rows at a time) to prevent memory exhaustion
- 50,000 row export limit with clear error message
- Full error handling with user-friendly messages

**Usage:**
1. Navigate to Change Log page
2. Apply filters if desired (optional)
3. Click "Export to CSV" button
4. CSV file downloads automatically

### 2. Export & Delete
Export logs to CSV and then delete them from the database to free up space.

**Features:**
- Two-step confirmation process for safety
- Export via hidden iframe (page doesn't navigate away)
- Iframe onload event detection with 3-second fallback
- Button shows loading state during export
- Second confirmation dialog after export completes
- Success message displays count of deleted entries

**Usage:**
1. Navigate to Change Log page
2. Apply filters to select logs to archive
3. Click "Export & Delete" button
4. Confirm first dialog (export)
5. Wait for CSV download
6. Confirm second dialog (delete)
7. Logs are removed from database

### 3. Date Range Filtering
Filter logs by date range for targeted export or delete operations.

**Features:**
- "From" and "To" date inputs
- HTML5 date picker for easy selection
- Date format validation (YYYY-MM-DD regex)
- Works with all other filters (action, object, user, search)
- Proper SQL date range queries with time components

**Usage:**
1. Enter "From" date (optional)
2. Enter "To" date (optional)
3. Click "Filter" to see results
4. Export or Export & Delete as desired

### 4. Automatic 21-Day Cleanup
Logs older than 21 days are automatically deleted daily to prevent database bloat.

**Features:**
- Scheduled via WordPress cron (daily event)
- Runs automatically in background
- Calculates cutoff date (21 days ago)
- Deletes only logs older than cutoff
- Logs its own cleanup action for audit trail
- Event cleared on plugin deactivation

**Technical Details:**
- Event name: `log_changes_auto_cleanup`
- Schedule: Once daily
- Action: `auto_cleanup_old_logs()` method
- Cleanup logged as system action with count

**Note:** Export logs before 21 days if you need historical data for compliance or analysis.

## UI Improvements

### Admin Interface
- Added date range input fields (From/To)
- Added "Export to CSV" button with download icon
- Added "Export & Delete" button with trash icon
- Added informational note about automatic cleanup
- Export buttons show loading state during operations
- Improved layout with export controls in separate row
- Responsive design for mobile compatibility

### User Feedback
- Success notifications when logs are deleted
- Error messages for invalid operations
- Loading states on action buttons
- Two confirmation dialogs for destructive actions
- Clear messaging about automatic cleanup

## Security Improvements

### Input Validation
- Date format validation using regex (`/^\d{4}-\d{2}-\d{2}$/`)
- Prevents SQL injection via date parameters
- All user inputs sanitized before use

### Delete Safety
- Requires both WHERE clauses AND values to be non-empty
- Prevents accidental deletion of all logs
- Dual safety check in delete method

### CSRF Protection
- Separate WordPress nonces for each action:
  - `log_changes_export` - for CSV export
  - `log_changes_export_delete` - for export/delete flow
  - `log_changes_delete` - for actual deletion
- Nonce verification on all actions
- Capability check (`manage_options`) for admin-only access

### Filename Security
- CSV filename sanitized using WordPress `sanitize_file_name()`
- Prevents header injection attacks
- Timestamp validated and properly formatted

## Performance Optimizations

### Chunked CSV Export
- Processes 1000 rows at a time
- Frees memory after each chunk
- Prevents PHP memory exhaustion on large datasets
- Supports exports up to 50,000 rows

### Query Optimization
- Count check before export to avoid unnecessary processing
- Early exit for empty result sets
- Proper indexing on timestamp field for cleanup queries
- LIMIT/OFFSET for chunked processing

### Browser Performance
- Export via iframe doesn't navigate away from page
- Onload event detection instead of fixed timeouts
- Minimal JavaScript footprint
- Efficient DOM manipulation

## Technical Changes

### Database
No schema changes required. Uses existing `wp_change_log` table.

### Cron Jobs
New scheduled event:
- **Event Name:** `log_changes_auto_cleanup`
- **Schedule:** Daily (WordPress `daily` recurrence)
- **Hook:** Calls `auto_cleanup_old_logs()` method
- **Registered:** On plugin initialization
- **Unregistered:** On plugin deactivation

### New Methods

**In `Log_Changes` class:**
- `handle_export_delete_actions()` - Handles export/delete requests
- `build_filter_clauses()` - Builds SQL WHERE clauses from GET params
- `export_logs_to_csv()` - Generates and outputs CSV file
- `delete_logs()` - Deletes logs based on filters
- `auto_cleanup_old_logs()` - Daily cleanup of old logs

### Modified Methods
- `init()` - Added cron scheduling
- `deactivate()` - Added cron unscheduling
- `init_admin()` - Added action handler for export/delete
- `enqueue_admin_scripts()` - Added localization strings for JavaScript

### New Files
- `TESTING.md` - Comprehensive testing guide
- `CHANGELOG-1.1.0.md` - This file

### Modified Files
- `log-changes.php` - Core functionality additions
- `includes/admin-page.php` - UI for export features
- `assets/js/admin.js` - Export/delete flow handling
- `assets/css/admin.css` - Styling for new UI elements
- `README.md` - Documentation updates
- `readme.txt` - WordPress.org documentation

## Upgrade Notes

### From 1.0.0 to 1.1.0

**Automatic Changes:**
1. Cron event `log_changes_auto_cleanup` will be scheduled on next page load
2. Logs older than 21 days will be deleted within 24 hours
3. No database schema changes required

**Manual Actions:**
1. Review and export logs older than 21 days if you need them
2. Test export functionality with a few filters
3. Verify cron is working: `wp cron event list` (WP-CLI)

**No Breaking Changes:**
- All existing functionality remains unchanged
- Existing logs are preserved
- Admin interface is backward compatible

## Configuration

### Export Limits
To change the 50,000 row export limit, modify line 971 in `log-changes.php`:
```php
if ( $count > 50000 ) {
```

### Chunk Size
To change the 1000-row chunk size, modify line 1021 in `log-changes.php`:
```php
$chunk_size = 1000;
```

### Retention Period
To change the 21-day retention period, modify line 1058 in `log-changes.php`:
```php
$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( '-21 days' ) );
```

### Disable Automatic Cleanup
To disable automatic cleanup (not recommended), add to `functions.php`:
```php
add_action( 'init', function() {
    $timestamp = wp_next_scheduled( 'log_changes_auto_cleanup' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'log_changes_auto_cleanup' );
    }
}, 999 );
```

## Browser Compatibility
Tested and working in:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Opera 76+

**Note:** HTML5 date input fallback provided for older browsers.

## Known Limitations

1. **Export Limit:** Maximum 50,000 rows per export. Use date filters for larger datasets.
2. **File Size:** Large exports may take several seconds to generate.
3. **Cron Dependency:** Automatic cleanup requires WordPress cron to be enabled.
4. **Timezone:** All timestamps use WordPress configured timezone.
5. **Iframe Detection:** 3-second fallback may show dialog slightly early on slow servers.

## Future Enhancements

Potential features for future versions:
- Direct Google Sheets integration
- Scheduled exports via email
- Configurable retention period in admin
- Export to JSON format
- Bulk export in background (WP-CLI)
- Export progress indicator

## Support

For issues or questions about this release:
1. Check `TESTING.md` for troubleshooting guidance
2. Review logs in WordPress debug.log
3. Visit: https://schoedel.design/support
4. GitHub Issues: https://github.com/schoedel-learn/log-changes/issues

## Security

No security vulnerabilities were found in this release:
- CodeQL Analysis: 0 alerts
- Manual Security Review: Passed
- WordPress Coding Standards: Compliant

Report security issues to: security@schoedel.design

## Credits

Developed by Barry Schoedel for schoedel.design

## License

MIT License - See LICENSE file for details
