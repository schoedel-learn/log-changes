=== Log Changes ===
Contributors: barryschoedel
Tags: activity log, audit log, change tracking, site monitoring, security
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.3.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Track all changes to your WordPress site including posts, users, logins, WooCommerce, Fluent plugins, and more.

== Description ==

Log Changes is a comprehensive WordPress plugin that tracks all changes made to your site with extensive support for popular e-commerce and business plugins. It records what changed, when it happened, and who made the changes - perfect for sites with multiple administrators.

**Key Features:**

* **Authentication Tracking** - Track successful logins, failed login attempts, and logouts with IP and timestamp
* **Post & Page Tracking** - Monitor creation, updates, deletion, and status changes
* **User Management Tracking** - Track new users, profile updates, deletions, and role changes
* **Plugin & Theme Tracking** - Log activations, deactivations, updates, and theme switches
* **Media Library Tracking** - Record uploads and deletions
* **Menu & Widget Tracking** - Monitor changes to navigation menus and widgets
* **Settings Tracking** - Log changes to WordPress options and settings
* **Customizer Tracking** - Track theme customizer changes

**E-Commerce & Business Plugin Tracking:**

* **WooCommerce** - Track product creation, updates, deletions, orders, and status changes
* **SureCart** - Track purchases and order status changes
* **Fluent Forms** - Track form creation, updates, and deletion
* **Fluent CRM Pro** - Track contact management activities
* **Fluent Support** - Track support ticket creation and updates
* **Fluent Boards** - Track board and task creation
* **Slim SEO** - Track SEO meta data changes
* **Spectra** - Track design imports
* **Code Snippets** - Track snippet creation, updates, and deletion

**Admin Features:**

* **Detailed Information** - Captures user, timestamp, IP address, and user agent
* **Export to CSV** - Download logs as CSV for backup or analysis in Excel/Google Sheets
* **Bulk Delete** - Export and delete old logs to free up database space
* **Date Range Filtering** - Filter logs by date range for targeted operations
* **Automatic Cleanup** - Logs older than 21 days are automatically deleted daily
* **Advanced Filtering** - Filter logs by action type, object type, user, date range, or search terms
* **Clean Interface** - Easy-to-use admin interface with pagination
* **Automatic Detection** - Identifies whether changes were made by users, cron jobs, or WP-CLI

**Perfect For:**

* Sites with multiple administrators
* Client sites where you need to track changes
* E-commerce sites requiring audit trails
* Membership sites with user management
* Any WordPress site where accountability is important

**Privacy & Performance:**

* All data is stored in your WordPress database
* Logs automatically deleted after 21 days (configurable)
* Export to CSV for long-term archival
* Minimal performance impact
* Automatically skips transients and temporary data
* Clean uninstall removes all data

== Installation ==

1. Upload the `log-changes` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the change log through the 'Change Log' menu item in the WordPress admin
4. Use filters to find specific changes or search for keywords

== Frequently Asked Questions ==

= Does this plugin slow down my site? =

No. The plugin is designed to have minimal impact on performance. It logs changes asynchronously and skips tracking of transient data that changes frequently.

= Can I export the change logs? =

Yes! Click the "Export to CSV" button to download all logs (or filtered logs) as a CSV file. The file can be opened in Excel, Google Sheets, or any spreadsheet application. Use this for backup or external analysis.

= How long are logs kept? =

Logs are automatically deleted after 21 days to prevent database bloat. You can export logs to CSV before they're deleted if you need to keep historical data. The automatic cleanup runs daily via WordPress cron.

= Does this work with multisite? =

Yes, the plugin works with WordPress multisite installations. Each site in the network has its own change log.

= What happens when I deactivate the plugin? =

When deactivated, the plugin stops tracking changes and clears the automatic cleanup schedule. Existing logs remain in the database. When you delete the plugin through WordPress admin, all logs are permanently removed from the database.

= Can I exclude certain types of changes from being logged? =

The plugin automatically excludes transients and frequently-changing internal WordPress options. Custom filtering may be added in future versions.

== Screenshots ==

1. Main change log interface showing all tracked changes
2. Filtering options to find specific changes
3. Detailed view of a change showing old and new values
4. Clean, organized display of change information

== Changelog ==

= 1.3.0 =
* Added: Successful login tracking with timestamp, user, and IP
* Added: Failed login attempt tracking for security monitoring
* Added: Logout tracking
* Added: WooCommerce product tracking (create, update, delete)
* Added: WooCommerce order tracking (purchases, status changes, returns)
* Added: SureCart purchase and order tracking
* Added: Plugin and theme update tracking with version information
* Added: WordPress customizer save tracking
* Added: Fluent Forms tracking (form creation, updates, deletion)
* Added: Fluent CRM Pro contact tracking
* Added: Fluent Support ticket tracking
* Added: Fluent Boards tracking (boards and tasks)
* Added: Slim SEO meta data tracking
* Added: Spectra design import tracking
* Added: Code Snippets tracking (create, update, delete)
* Improved: Comprehensive audit trail for e-commerce and business sites
* Improved: Better support for modern WordPress plugin ecosystem

= 1.2.0 =
* Added: Settings page at Settings → Change Log Settings
* Added: Enhanced option filtering with exclusion patterns
* Added: Allowlist support for important settings
* Added: Configurable cleanup period (1-365 days, default: 21)
* Added: Manual cleanup option in settings
* Added: Logging controls for each content type
* Added: wp_user_roles logging control
* Added: Developer filters for custom control
* Added: Wildcard pattern matching for exclusions
* Added: Settings link on Plugins page
* Improved: Option tracking with smart filtering
* Improved: Performance with compiled patterns

= 1.1.0 =
* Added: Export logs to CSV functionality
* Added: Export & Delete feature to archive and remove old logs
* Added: Date range filtering for targeted operations
* Added: Automatic deletion of logs older than 21 days (daily cron)
* Added: Success/error notifications for user actions
* Improved: Admin interface with better organization
* Improved: Filter controls with date inputs

= 1.0.0 =
* Initial release
* Track post/page changes (create, update, delete, status changes)
* Track user changes (register, update, delete, role changes)
* Track plugin and theme changes
* Track media uploads and deletions
* Track menu and widget changes
* Track settings/options changes
* Admin interface with filtering and search
* Pagination support
* Detailed change information including old/new values
* IP address and user agent tracking
* Automatic detection of cron jobs and WP-CLI commands
* Clean uninstall process

== Upgrade Notice ==

= 1.0.0 =
Initial release of Log Changes plugin.

== Privacy Policy ==

Log Changes stores the following information:
* Timestamp of changes
* User ID and username (if applicable)
* Type of change and object changed
* Description of the change
* Old and new values (for relevant changes)
* IP address of the user making the change
* User agent (browser information)

This data is stored in your WordPress database and is not sent to any external services. Logs are automatically deleted after 21 days. Exported CSV files are downloaded to your local machine and not stored on the server. The plugin is designed for internal site auditing and does not share data with third parties.

== Support ==

For support, please visit: https://schoedel.design/support

== Development ==

Development happens on GitHub: https://github.com/schoedel-learn/log-changes

Contributions are welcome!
