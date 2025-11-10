# Log Changes - WordPress Plugin

[![CodeQL](https://github.com/schoedel-learn/log-changes/actions/workflows/codeql.yml/badge.svg)](https://github.com/schoedel-learn/log-changes/actions/workflows/codeql.yml)
[![PHP Linting](https://github.com/schoedel-learn/log-changes/actions/workflows/php-linting.yml/badge.svg)](https://github.com/schoedel-learn/log-changes/actions/workflows/php-linting.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://www.php.net/)

A comprehensive WordPress plugin for tracking all changes to your site with detailed information about what changed, when, and who made the changes.

## Description

Log Changes is designed for WordPress sites with multiple administrators who need detailed audit trails. Unlike simpler logging plugins, this captures comprehensive information including old and new values, making it perfect for accountability and troubleshooting.

## Features

### Comprehensive Tracking

- **Posts & Pages**: Create, update, delete, and status changes
- **Users**: Registration, profile updates, deletions, and role changes
- **Authentication**: Successful logins, failed login attempts, and logouts (with time, user, and IP)
- **Plugins**: Activation, deactivation, installation, deletion, and updates
- **Themes**: Theme switches, activations, and updates
- **Media**: Uploads and deletions with file type information
- **Menus**: Navigation menu creation, updates, and deletions
- **Widgets**: Widget updates and changes
- **Customizer**: Theme customizer changes
- **Settings**: WordPress options and settings changes

### E-Commerce Tracking

- **WooCommerce**: Product creation/editing/deletion, order creation (purchases), order status changes (including returns/refunds)
- **SureCart**: Purchase tracking, order status changes

### Plugin-Specific Integrations

- **Fluent Forms**: Form creation, editing, and deletion
- **Fluent CRM Pro**: Contact creation, updates, and deletion
- **Fluent Support**: Ticket creation and updates
- **Fluent Boards**: Board and task creation
- **Slim SEO**: SEO meta data updates
- **Spectra**: Design imports
- **Code Snippets**: Code snippet creation, editing, and deletion

### Detailed Information

Each change log entry includes:
- Timestamp (when the change occurred)
- User information (who made the change)
- Action type (created, updated, deleted, etc.)
- Object type (post, user, plugin, etc.)
- Object name and ID
- Detailed description
- **Old and new values** (for updates and changes)
- IP address
- User agent (browser information)

### Smart Detection

- Only logs user-initiated changes through the UI
- Automatically skips automated changes (WP-Cron, WP-CLI, system processes)
- Skips transients and frequently-changing temporary data
- Prevents log bloat from automated processes

### Export and Cleanup

- **Export to CSV**: Download logs as CSV for backup or analysis in Excel/Google Sheets
- **Export & Delete**: Export logs to CSV then delete them from database to free up space
- **Date Range Filtering**: Filter logs by date range for targeted operations
- **Automatic Cleanup**: Logs older than 21 days are automatically deleted daily
- **Bulk Operations**: Export or delete multiple logs at once based on filters

### User-Friendly Interface

- Clean, organized admin interface
- Advanced filtering by action type, object type, user, and date range
- Search functionality across descriptions and object names
- Pagination for large log sets
- Expandable details view for old/new values
- Clickable badges for quick filtering
- One-click export to CSV
- Confirmation dialogs for destructive actions

## Installation

### Standard Installation

1. Download the plugin files
2. Upload the `log-changes` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin Plugins menu
4. Access logs via the "Change Log" menu item

### Development Installation

```bash
cd /wp-content/plugins/
git clone https://github.com/schoedel-learn/log-changes.git
cd log-changes
```

Then activate through WordPress admin.

## Configuration

### Settings Page

Access settings at **Settings → Change Log Settings**

**Option Logging Controls:**
- Control which WordPress option changes are logged
- Exclude noisy automated options (asset versions, hit counters, session data, etc.)
- Allowlist critical settings to always log (blogname, siteurl, etc.)
- Control whether to log wp_user_roles (often updated by plugins automatically)
- One pattern per line with wildcard support (e.g., `*_transient*` matches all transients)

**Logging Controls:**
- Enable/disable logging by content type
- Toggle logging for posts, users, plugins, themes, media, menus, and widgets
- Fine-tune what gets tracked based on your needs

**Cleanup Settings:**
- Adjust automatic cleanup period (default: 21 days, range: 1-365 days)
- Manually trigger cleanup to delete old logs immediately
- Logs older than the configured period are automatically deleted daily

### Developer Filters

**Programmatically exclude options:**
```php
add_filter( 'log_changes_option_exclusions', function( $exclusions ) {
    $exclusions[] = 'my_plugin_cache_key';
    $exclusions[] = 'another_noisy_option';
    return $exclusions;
} );
```

**Control whether specific option should log:**
```php
add_filter( 'log_changes_should_log_option', function( $should_log, $option_name, $old_value, $new_value ) {
    // Skip if value didn't actually change
    if ( $old_value === $new_value ) {
        return false;
    }
    
    // Skip specific option based on custom logic
    if ( $option_name === 'my_special_option' && some_condition() ) {
        return false;
    }
    
    return $should_log;
}, 10, 4 );
```

## Usage

### Viewing Logs

Navigate to **Change Log** in the WordPress admin menu to view all tracked changes.

### Filtering Logs

Use the filter dropdowns and inputs to narrow results by:
- Action type (created, updated, deleted, etc.)
- Object type (post, user, plugin, etc.)
- User (who made the change)
- Date range (from and to dates)
- Search terms (in descriptions and object names)

### Exporting Logs

**Export to CSV**:
1. Apply filters to select the logs you want to export (or export all)
2. Click "Export to CSV" button
3. CSV file downloads automatically with timestamp in filename
4. Open in Excel, Google Sheets, or any spreadsheet application

**Export & Delete**:
1. Apply filters to select logs you want to archive and remove
2. Click "Export & Delete" button
3. Confirm the action in the dialog
4. CSV downloads and selected logs are deleted from database
5. Success message shows number of deleted entries

**Note**: Logs are automatically cleaned up based on the configured cleanup period (default: 21 days, configurable in Settings). Use export before they're deleted if you need historical data.

### Viewing Details

Click "Show Details" on any log entry to see:
- Old and new values for changes
- IP address of the requester
- User agent information

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher / MariaDB 10.0 or higher

## Compatibility

Tested with:
- WordPress 6.8+
- PHP 8.2+
- MariaDB 11.8+

Works with:
- Multisite installations
- All standard WordPress post types
- Custom post types
- **WooCommerce** - Product and order tracking
- **SureCart** - Purchase and order tracking
- **Fluent Forms** - Form management tracking
- **Fluent CRM Pro** - Contact management tracking
- **Fluent Support** - Ticket tracking
- **Fluent Boards** - Board and task tracking
- **Slim SEO** - SEO meta tracking
- **Spectra** - Design import tracking
- **Code Snippets** - Snippet management tracking
- Membership plugins
- Other standard WordPress plugins

## Database

The plugin creates a single custom table `{prefix}_change_log` with the following structure:

- `id` - Unique identifier
- `timestamp` - When the change occurred
- `user_id` - WordPress user ID (if applicable)
- `user_login` - Username or system identifier
- `action_type` - Type of action performed
- `object_type` - Type of object changed
- `object_id` - ID of the object
- `object_name` - Name of the object
- `description` - Human-readable description
- `old_value` - Previous value (JSON for complex data)
- `new_value` - New value (JSON for complex data)
- `ip_address` - IP address of requester
- `user_agent` - Browser/client information

### Automatic Cleanup

The plugin automatically deletes logs older than the configured period to prevent database bloat:
- Runs daily via WordPress cron
- Default cleanup period: 21 days (configurable in Settings: 1-365 days)
- Deletes logs with timestamp older than the configured period
- Logs the cleanup action itself for audit trail
- Manual cleanup also available in Settings page

Export logs before they're automatically deleted if you need to retain historical data.

## Privacy

All data is stored locally in your WordPress database. No data is sent to external services. The plugin logs:

- User actions and identifiers
- IP addresses
- User agent strings
- Content changes

Logs are automatically deleted after 21 days. Exported CSV files are downloaded to your local machine and not stored on the server.

Ensure your privacy policy discloses this tracking if required by your jurisdiction.

## Security

This plugin is built with security as a top priority. See our [Security Policy](SECURITY.md) for details.

### Security Features

- **Input Validation**: All user inputs are sanitized using WordPress functions
- **Output Escaping**: All output is properly escaped to prevent XSS
- **SQL Injection Prevention**: Uses prepared statements with `wpdb->prepare()`
- **Access Control**: Only users with `manage_options` capability can view logs
- **Nonce Verification**: All forms and AJAX requests are protected with nonces
- **IP Spoofing Protection**: Validates IP addresses against server variables
- **No External Calls**: All data stays in your WordPress database

### Security Scanning

This plugin is regularly scanned for security vulnerabilities:

- **CodeQL**: Automated code analysis for security issues
- **WordPress Coding Standards**: Follows WordPress best practices
- **Dependabot**: Automatic dependency vulnerability alerts
- **Manual Reviews**: Regular security audits

### Reporting Security Issues

Found a security vulnerability? Please report it responsibly:

- **GitHub Security Advisories**: [Report privately](https://github.com/schoedel-learn/log-changes/security/advisories/new) (preferred)
- **Email**: security@schoedel.design

See our [Security Policy](SECURITY.md) for more information.

## Uninstallation

When you delete the plugin through WordPress admin, it will:
1. Remove the custom database table
2. Delete all stored logs
3. Remove all plugin options

This ensures a clean removal with no leftover data.

## Performance

The plugin is optimized for minimal performance impact:
- Efficient database queries with proper indexing
- Automatic exclusion of transients and temporary data
- Batch operations where appropriate
- Minimal memory footprint

## Development

### File Structure

```
log-changes/
├── log-changes.php          # Main plugin file
├── includes/
│   └── admin-page.php       # Admin interface template
├── assets/
│   ├── css/
│   │   └── admin.css        # Admin styles
│   └── js/
│       └── admin.js         # Admin JavaScript
├── uninstall.php            # Clean uninstall script
├── readme.txt               # WordPress.org readme
├── README.md                # This file
└── LICENSE                  # MIT License
```

### Contributing

Contributions are welcome! We appreciate your help in making this plugin better.

**Before contributing, please read:**
- [Contributing Guidelines](CONTRIBUTING.md) - How to contribute
- [Code of Conduct](CODE_OF_CONDUCT.md) - Community standards
- [Security Policy](SECURITY.md) - Reporting security issues

**Quick start:**
Contributions are welcome! Please read our [Contributing Guidelines](CONTRIBUTING.md) before submitting pull requests.

**Quick Start:**
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes following [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
4. Test thoroughly (see [CONTRIBUTING.md](CONTRIBUTING.md))
5. Commit your changes (`git commit -m 'feat: add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

**Areas where we need help:**
- 🐛 Bug fixes
- 📝 Documentation improvements
- 🌐 Translations
- ✨ New features
- 🧪 Testing and QA
- 🔒 Security improvements
3. Make your changes following WordPress coding standards
4. Test thoroughly
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

**Important Notes:**
- All pull requests require review before merging
- Automated tests must pass (PHPCS, PHP compatibility)
- Follow the [Code of Conduct](CODE_OF_CONDUCT.md)
- See [Branch Protection Guide](.github/BRANCH_PROTECTION.md) for repository rules

### Coding Standards

This plugin follows:
- WordPress Coding Standards
- WordPress Plugin Best Practices
- WordPress Security Best Practices
- PHPCS with WordPress ruleset

Run code quality checks:
```bash
composer install
composer run-script phpcs  # Check coding standards
composer run-script phpcbf # Fix coding standards automatically
```

### Branch Protection

The `main` branch is protected to ensure code quality:
- ✅ Pull requests required (no direct pushes)
- ✅ Code review required before merge
- ✅ All automated tests must pass
- ✅ Conversation resolution required
- ✅ Force pushes disabled

See [Branch Protection Setup Guide](.github/BRANCH_PROTECTION.md) for detailed information.

## Support

Need help? Here's how to get support:

### Documentation
- 📖 [README](README.md) - Getting started and features
- 🚀 [Quick Start Guide](QUICKSTART.md) - Fast setup
- 📦 [Installation Guide](INSTALL.md) - Detailed installation
- 🧪 [Testing Guide](TESTING.md) - Testing procedures
- 🤝 [Contributing Guide](CONTRIBUTING.md) - How to contribute

### Getting Help
- 🐛 [Report a Bug](https://github.com/schoedel-learn/log-changes/issues/new?template=bug_report.yml) - Submit a bug report
- 💡 [Request a Feature](https://github.com/schoedel-learn/log-changes/issues/new?template=feature_request.yml) - Suggest new features
- 💬 [Discussions](https://github.com/schoedel-learn/log-changes/discussions) - Ask questions and share ideas
- 🌐 [Support Website](https://schoedel.design/support) - Direct support

### Security Issues
- 🔒 [Security Policy](SECURITY.md) - Read our security policy
- 🚨 [Report Vulnerability](https://github.com/schoedel-learn/log-changes/security/advisories/new) - Report security issues privately

## License

This plugin is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Credits

Created by Barry Schoedel for schoedel.design

## Changelog

### 1.3.0 - Comprehensive Plugin Integrations and Login Tracking

- **Authentication Tracking**: Successful logins, failed login attempts, and logouts with timestamp, user, and IP
- **WooCommerce Integration**: Track product creation/editing/deletion, order creation, and order status changes (purchases, returns, refunds)
- **Plugin/Theme Updates**: Track all plugin and theme updates with version information
- **Customizer Tracking**: Track WordPress customizer save events
- **Fluent Forms**: Track form creation, editing, and deletion
- **Fluent CRM Pro**: Track contact creation, updates, and deletion
- **Fluent Support**: Track ticket creation and updates
- **Fluent Boards**: Track board and task creation
- **Slim SEO**: Track SEO meta data updates
- **SureCart**: Track purchases and order status changes
- **Spectra**: Track design imports
- **Code Snippets**: Track code snippet creation, editing, and deletion
- **Enhanced Tracking**: Now covers all major WordPress plugins used in modern sites

### 1.2.0 - Enhanced Option Filtering and Settings

- **Settings Page**: New admin settings at Settings → Change Log Settings
- **Enhanced Option Filtering**: Comprehensive exclusion patterns for automated options (asset versions, hit counters, sessions, transients, etc.)
- **Allowlist Support**: Always log important settings even if they match exclusions
- **Configurable Cleanup Period**: Adjust auto-delete period (1-365 days, default: 21)
- **Manual Cleanup**: Run cleanup immediately from settings page
- **Logging Controls**: Enable/disable logging for each content type (posts, users, plugins, themes, media, menus, widgets, options)
- **wp_user_roles Control**: Toggle logging of wp_user_roles (reduces noise from plugin updates)
- **Developer Filters**: `log_changes_should_log_option` and `log_changes_option_exclusions` for custom control
- **Wildcard Pattern Matching**: Flexible exclusion patterns with `*` wildcard support
- **Settings Link**: Quick access from Plugins page

### 1.1.0 - Export and Auto-Delete Features

- CSV export functionality with date range filtering
- Export & Delete feature for archiving and cleanup
- Automatic cleanup of logs older than 21 days
- Enhanced security with nonce verification
- Improved admin UI with export controls

### 1.0.0 - Initial Release

- Complete change tracking for posts, pages, users, plugins, themes, media, menus, widgets, and settings
- Detailed logging with old/new values
- Advanced filtering and search
- Clean admin interface
- Smart detection of automated vs. user changes
- Proper security measures (nonces, capability checks)
- Clean uninstall process
- Full WordPress coding standards compliance
