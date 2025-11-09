# Security Policy

## Supported Versions

We take security seriously and provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.2.x   | :white_check_mark: |
| 1.1.x   | :white_check_mark: |
| < 1.1   | :x:                |

## Reporting a Vulnerability

We appreciate your efforts to responsibly disclose your findings and will make every effort to acknowledge your contributions.

### How to Report a Security Vulnerability

**Please do NOT report security vulnerabilities through public GitHub issues.**

Instead, please report them via one of the following methods:

1. **Email**: Send an email to security@schoedel.design
2. **GitHub Security Advisories**: Use the [GitHub Security Advisory](https://github.com/schoedel-learn/log-changes/security/advisories/new) feature (preferred)

### What to Include in Your Report

Please include the following information:

- Type of vulnerability (e.g., XSS, SQL injection, authentication bypass)
- Full paths of source file(s) related to the vulnerability
- Location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

### What to Expect

- **Acknowledgment**: We will acknowledge receipt of your vulnerability report within 48 hours
- **Investigation**: We will investigate and validate the reported vulnerability
- **Updates**: You will receive updates on the progress of the fix every 5-7 days
- **Resolution**: We aim to release a fix within 30 days for critical vulnerabilities
- **Credit**: If you wish, we will publicly credit you for the discovery once the fix is released

## Security Best Practices for Users

When using this plugin, we recommend:

1. **Keep Updated**: Always use the latest version of the plugin
2. **WordPress Core**: Keep WordPress core up to date
3. **PHP Version**: Use PHP 7.4 or higher (PHP 8.0+ recommended)
4. **Access Control**: Limit administrator access to trusted users only
5. **Regular Audits**: Review change logs regularly for suspicious activity
6. **Backup Data**: Export logs regularly before automatic cleanup
7. **HTTPS**: Always use HTTPS for your WordPress admin area
8. **Strong Passwords**: Enforce strong password policies for all users
9. **Two-Factor Authentication**: Enable 2FA for administrator accounts
10. **File Permissions**: Ensure proper file and directory permissions on your server

## Known Security Considerations

### Data Logged
This plugin logs:
- User actions and identifiers
- IP addresses
- User agent strings
- Content changes (including sensitive data in posts/pages)

**Privacy Compliance**: Ensure your privacy policy discloses this tracking if required by GDPR, CCPA, or other privacy regulations in your jurisdiction.

### Automatic Cleanup
- Logs are automatically deleted after the configured period (default: 21 days)
- Export logs before they are deleted if you need to retain historical data
- Consider your data retention policies and compliance requirements

### Access Control
- Only users with `manage_options` capability (typically Administrators) can view logs
- All log viewing actions are themselves logged for audit trail
- Consider carefully who you grant administrator access to

## Security Features

This plugin implements the following security measures:

### Input Validation & Sanitization
- All user inputs are sanitized using WordPress sanitization functions
- Database queries use prepared statements with wpdb->prepare()
- Table names are properly escaped with esc_sql()

### Output Escaping
- All output is escaped using appropriate WordPress escaping functions
- HTML output uses esc_html()
- Attributes use esc_attr()
- URLs use esc_url()
- SQL uses esc_sql()

### Access Control
- Capability checks using current_user_can('manage_options')
- Nonce verification for all form submissions and AJAX requests
- Direct file access prevention (ABSPATH checks)

### IP Security
- IP spoofing protection (validates against server variables)
- IP addresses stored for audit trail purposes only

### SQL Injection Prevention
- All database queries use wpdb->prepare() with placeholders
- No direct SQL string concatenation
- Proper escaping of table names and identifiers

### XSS Prevention
- DOM manipulation uses safe methods
- Content Security Policy friendly
- No inline JavaScript in HTML attributes
- All dynamic content properly escaped

### CSRF Prevention
- Nonce verification on all state-changing operations
- Token validation before processing forms
- Separate nonces for different actions

## Security Testing

This plugin is regularly tested using:

- **CodeQL Analysis**: Automated code scanning for security vulnerabilities
- **WordPress Coding Standards**: PHPCS with WordPress rulesets
- **Manual Security Review**: Regular code audits
- **Penetration Testing**: Ad-hoc security testing

## Security Updates

Security updates are released as soon as possible after a vulnerability is confirmed. Critical vulnerabilities are prioritized and may result in immediate patch releases.

### Update Notification

Users will be notified of security updates through:
- WordPress plugin update notifications
- GitHub Security Advisories
- Release notes with security tags

## Disclosure Policy

- We follow responsible disclosure practices
- Security vulnerabilities are not disclosed until a fix is available
- We will coordinate disclosure timing with the reporter
- Public disclosure typically occurs 7 days after fix release

## Contact

For security concerns, contact:
- **Email**: security@schoedel.design
- **GitHub**: [Security Advisories](https://github.com/schoedel-learn/log-changes/security/advisories)
- **Website**: https://schoedel.design/support

Thank you for helping keep Log Changes and its users safe!
