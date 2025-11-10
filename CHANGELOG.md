# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Branch protection recommendations and GitHub workflow templates
- Pull request template for standardized contributions
- Issue templates (bug reports and feature requests)
- CONTRIBUTING.md with comprehensive contribution guidelines
- CODE_OF_CONDUCT.md for community standards
- SECURITY.md for vulnerability reporting
- .editorconfig for consistent code formatting
- phpcs.xml for WordPress coding standards enforcement
- composer.json for dependency management
- GitHub Actions workflow for automated code quality checks

### Changed
- Updated .gitignore with additional WordPress plugin best practices

## [1.2.0] - 2024-01-XX

### Added
- Settings Page at Settings → Change Log Settings
- Enhanced option filtering with comprehensive exclusion patterns
- Allowlist support for important settings
- Configurable cleanup period (1-365 days, default: 21)
- Manual cleanup trigger in settings page
- Logging controls to enable/disable by content type
- wp_user_roles logging toggle
- Developer filters: `log_changes_should_log_option` and `log_changes_option_exclusions`
- Wildcard pattern matching for exclusions
- Settings link in Plugins page

### Changed
- Improved option filtering reduces noise from automated changes
- More granular control over what gets logged

### Fixed
- Excessive logging of automated option changes
- wp_user_roles frequent updates by plugins

## [1.1.0] - 2024-01-XX

### Added
- CSV export functionality with date range filtering
- Export & Delete feature for archiving and cleanup
- Automatic cleanup of logs older than 21 days
- Enhanced security with nonce verification
- Export controls in admin UI

### Changed
- Improved admin UI with export buttons
- Better filtering options

### Security
- Added nonce verification for all export operations
- Enhanced capability checks

## [1.0.0] - 2024-01-XX

### Added
- Initial release
- Complete change tracking for:
  - Posts & Pages (create, update, delete, status changes)
  - Users (registration, profile updates, deletions, role changes)
  - Plugins (activation, deactivation, installation, deletion)
  - Themes (theme switches and activations)
  - Media (uploads and deletions with file type information)
  - Menus (navigation menu creation, updates, deletions)
  - Widgets (widget updates and changes)
  - Settings (WordPress options and settings changes)
- Detailed information capture:
  - Timestamp
  - User information
  - Action type
  - Object type and ID
  - Old and new values
  - IP address with anti-spoofing protection
  - User agent
- Smart detection of automated vs. user changes
- Advanced filtering by action type, object type, user, and date range
- Search functionality
- Clean admin interface with expandable details
- Pagination for large datasets
- Proper security measures (capability checks, input sanitization, output escaping)
- Clean uninstall process
- WordPress coding standards compliance

### Security
- SQL injection prevention with prepared statements
- XSS prevention with proper output escaping
- CSRF protection with capability checks
- IP spoofing protection

[Unreleased]: https://github.com/schoedel-learn/log-changes/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/schoedel-learn/log-changes/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/schoedel-learn/log-changes/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/schoedel-learn/log-changes/releases/tag/v1.0.0
