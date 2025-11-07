=== Log Changes ===
Contributors: barryschoedel
Tags: activity log, audit log, change tracking, site monitoring, security
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Track all changes to your WordPress site including posts, pages, users, plugins, themes, and settings.

== Description ==

Log Changes is a comprehensive WordPress plugin that tracks all changes made to your site. It records what changed, when it happened, and who made the changes - perfect for sites with multiple administrators.

**Key Features:**

* **Post & Page Tracking** - Monitor creation, updates, deletion, and status changes
* **User Management Tracking** - Track new users, profile updates, deletions, and role changes
* **Plugin & Theme Tracking** - Log activations, deactivations, and theme switches
* **Media Library Tracking** - Record uploads and deletions
* **Menu & Widget Tracking** - Monitor changes to navigation menus and widgets
* **Settings Tracking** - Log changes to WordPress options and settings
* **Detailed Information** - Captures user, timestamp, IP address, and user agent
* **Advanced Filtering** - Filter logs by action type, object type, user, or search terms
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

Currently, you can view and filter logs in the admin interface. Export functionality may be added in future versions.

= How long are logs kept? =

Logs are kept indefinitely by default. You can manually clear old logs from the database if needed.

= Does this work with multisite? =

Yes, the plugin works with WordPress multisite installations. Each site in the network has its own change log.

= What happens when I deactivate the plugin? =

When deactivated, the plugin stops tracking changes but keeps existing logs. When you delete the plugin, all logs are removed from the database.

= Can I exclude certain types of changes from being logged? =

The plugin automatically excludes transients and frequently-changing internal WordPress options. Custom filtering may be added in future versions.

== Screenshots ==

1. Main change log interface showing all tracked changes
2. Filtering options to find specific changes
3. Detailed view of a change showing old and new values
4. Clean, organized display of change information

== Changelog ==

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

This data is stored in your WordPress database and is not sent to any external services. The plugin is designed for internal site auditing and does not share data with third parties.

== Support ==

For support, please visit: https://schoedel.design/support

== Development ==

Development happens on GitHub: https://github.com/schoedel-learn/log-changes

Contributions are welcome!
