# Log Changes Plugin - Installation Guide

## Installation Instructions for schoedel.design

### Method 1: Upload via WordPress Admin (Recommended)

1. **Download the plugin**
   - Download all files from this repository as a ZIP file
   - Or create a ZIP file containing all plugin files

2. **Upload to WordPress**
   - Log in to your WordPress admin at https://schoedel.design/wp-admin
   - Navigate to **Plugins → Add New**
   - Click **Upload Plugin** button at the top
   - Choose the ZIP file and click **Install Now**

3. **Activate the plugin**
   - After installation completes, click **Activate Plugin**
   - You'll see a new "Change Log" menu item in the WordPress admin sidebar

### Method 2: FTP/SFTP Upload

1. **Connect via FTP/SFTP**
   - Connect to your server using your FTP client
   - Navigate to: `/home/u487448689/domains/schoedel.design/public_html/wp-content/plugins/`

2. **Upload plugin folder**
   - Upload the entire `log-changes` folder to the plugins directory
   - Ensure all files and subdirectories are uploaded

3. **Activate the plugin**
   - Log in to WordPress admin
   - Navigate to **Plugins**
   - Find "Log Changes" and click **Activate**

### Method 3: File Manager (Hostinger)

1. **Access File Manager**
   - Log in to your Hostinger control panel
   - Open the File Manager

2. **Navigate to plugins directory**
   - Go to: `public_html/wp-content/plugins/`

3. **Upload files**
   - Create a new folder named `log-changes`
   - Upload all plugin files into this folder

4. **Activate the plugin**
   - Go to WordPress admin
   - Navigate to **Plugins**
   - Find "Log Changes" and click **Activate**

## After Installation

### First Steps

1. **Access the Change Log**
   - In WordPress admin, click **Change Log** in the sidebar
   - The plugin will start tracking changes immediately

2. **Review Initial Logs**
   - You'll see the plugin activation logged
   - All future changes will be tracked automatically

3. **Test the Functionality**
   - Make a test change (e.g., edit a page)
   - Check the Change Log to see if it was recorded
   - Click "Show Details" to see detailed information

### Configuration

The plugin works out-of-the-box with no configuration needed. However, you can:

- **Filter logs** by action type, object type, or user
- **Search** for specific changes using keywords
- **View details** including old/new values for any change

### Compatibility Notes

Your site is running:
- WordPress 6.8.3 ✓
- PHP 8.2.28 ✓
- MariaDB 11.8+ ✓

All requirements are met! The plugin is fully compatible with your setup.

### Database

The plugin will automatically create a table named `wp_change_log` in your database during activation. This table stores all change logs.

### Permissions

Only users with the `manage_options` capability (typically Administrators) can:
- View change logs
- Access the Change Log admin page

The plugin will track changes made by all users, but only administrators can view the logs.

### Existing Plugins

Your site currently has **Simple History** installed. You can:
- Keep both plugins running (they won't conflict)
- Deactivate Simple History if you prefer Log Changes
- Compare the two to see which provides more useful information

Log Changes provides more detailed information including old/new values, which Simple History may not capture.

## Troubleshooting

### Plugin Not Showing in Admin

- Clear your browser cache and refresh
- Check file permissions (should be 644 for files, 755 for directories)
- Verify all files were uploaded correctly

### Changes Not Being Logged

- Ensure the plugin is activated
- Check database permissions
- Verify the `wp_change_log` table exists in your database

### Performance Issues

The plugin is designed for minimal performance impact, but if you experience issues:
- The plugin automatically skips transients and temporary data
- Logs are stored efficiently with proper database indexes
- Consider clearing old logs if the table grows very large

## Uninstallation

If you need to remove the plugin:

1. **Deactivate** the plugin first (in WordPress admin)
2. **Delete** the plugin (this removes all data)
3. The plugin will automatically:
   - Drop the `wp_change_log` table
   - Remove all stored logs
   - Clean up all plugin options

This ensures complete removal with no leftover data.

## Support

For questions or issues:
- Check the README.md for detailed documentation
- Review the plugin code (it's well-commented)
- Contact support at https://schoedel.design/support

## What Gets Tracked?

The plugin tracks:
- ✓ Posts and pages (create, edit, delete, status changes)
- ✓ Users (registration, profile updates, deletions, role changes)
- ✓ Plugins (activation, deactivation)
- ✓ Themes (theme switches)
- ✓ Media (uploads, deletions)
- ✓ Menus (creation, updates, deletions)
- ✓ Widgets (updates)
- ✓ Settings (option changes)

Each log entry includes:
- Timestamp (when it happened)
- User (who made the change, or "system" for automated changes)
- Action type (created, updated, deleted, etc.)
- Object type (post, user, plugin, etc.)
- Description (human-readable summary)
- Old and new values (for changes)
- IP address (of the person making the change)
- User agent (browser/client information)

## Privacy Compliance

The plugin stores user activity data. Ensure your privacy policy discloses:
- Tracking of administrative actions
- Logging of IP addresses
- Storage of user agent information

This is especially important if you're subject to GDPR or similar regulations.

## Next Steps

After installation:
1. Review the initial logs
2. Familiarize yourself with the filtering options
3. Test the search functionality
4. Check the detailed view for old/new values
5. Monitor changes for the first few days

The plugin will help you track all changes on your site with detailed audit trails!
