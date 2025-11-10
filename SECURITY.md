# Security Policy

## Supported Versions

We take security seriously and provide security updates for the following versions:
We release security updates for the following versions:

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
We take the security of Log Changes seriously. If you discover a security vulnerability, please follow these steps:

### 1. Do Not Publicly Disclose

**Please do not create a public GitHub issue for security vulnerabilities.** Public disclosure could put users at risk.

### 2. Report Privately

Send your security report to: **security@schoedel.design**

Include in your report:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if you have one)
- Your contact information

### 3. What to Expect

- **Acknowledgment**: We'll acknowledge receipt within 48 hours
- **Initial Assessment**: We'll provide an initial assessment within 5 business days
- **Regular Updates**: We'll keep you informed of our progress
- **Resolution Timeline**: We aim to resolve critical issues within 7-14 days
- **Credit**: If you wish, we'll credit you in the security advisory

### 4. Disclosure Process

1. We'll confirm the vulnerability and determine its severity
2. We'll develop and test a fix
3. We'll prepare a security release
4. We'll coordinate disclosure timing with you
5. We'll publish a security advisory
6. We'll release the patched version
7. We'll notify users through appropriate channels

## Security Best Practices for Users

### Installation

- Only download from official sources (GitHub, WordPress.org)
- Verify plugin integrity before installation
- Keep WordPress, PHP, and database up to date

### Configuration

- Restrict admin access to trusted users only
- Use strong passwords for all admin accounts
- Enable two-factor authentication where possible
- Regular backup your WordPress database

### Monitoring

- Review change logs regularly for suspicious activity
- Monitor for unexpected admin users or role changes
- Set up alerts for critical changes (if possible)
- Review exported CSV files periodically

### Updates

- **Always update to the latest version** for security fixes
- Test updates in staging environment first (recommended)
- Subscribe to security announcements
- Review CHANGELOG.md for security-related updates

## Known Security Considerations

### Data Storage

- Log data is stored in your WordPress database
- Logs contain sensitive information:
  - User actions and identifiers
  - IP addresses
  - User agent strings
  - Content changes (including passwords changed, roles modified)

### Access Control

- Only users with `manage_options` capability (typically administrators) can:
  - View change logs
  - Export logs to CSV
  - Delete logs
  - Access plugin settings

### Privacy Implications

- IP addresses are logged for audit purposes
- Consider GDPR/privacy regulations in your jurisdiction
- Inform users that their actions are being logged
- Implement data retention policies as needed
- Use export/delete features to manage old logs

### Automatic Cleanup

- Logs older than configured period (default: 21 days) are automatically deleted
- Export critical logs before automatic deletion
- Adjust cleanup period in plugin settings as needed

## Security Features

This plugin implements multiple security layers:

### Input Validation

- All user input is sanitized using WordPress functions
- Type checking with `absint()`, `sanitize_text_field()`, etc.
- Database queries use `$wpdb->prepare()` to prevent SQL injection

### Output Escaping

- All output is escaped using appropriate functions
- `esc_html()`, `esc_attr()`, `esc_url()` used throughout
- Prevents XSS (Cross-Site Scripting) attacks

### Capability Checks

- All admin functions check user capabilities
- `current_user_can( 'manage_options' )` enforced
- Prevents unauthorized access

### Nonce Verification

- Forms include nonce fields for CSRF protection
- Nonces verified before processing actions
- Prevents Cross-Site Request Forgery

### Database Security

- Custom table with proper indexing
- Prepared statements prevent SQL injection
- Table name escaping with `esc_sql()`
- No direct SQL queries without preparation

### IP Spoofing Protection

- IP address validation
- Anti-spoofing measures implemented
- Legitimate IP addresses logged only

### Secure Defaults

- Restrictive default settings
- Opt-in rather than opt-out for sensitive features
- Minimal data collection by default

## Security Auditing

### Code Reviews

- All code changes undergo security review
- Follow WordPress Security Best Practices
- Use automated security scanning (CodeQL)

### Testing

- Security testing included in development process
- XSS prevention verified
- SQL injection prevention verified
- CSRF protection verified

### Continuous Monitoring

- GitHub Dependabot alerts enabled
- CodeQL analysis on pull requests
- Security advisories monitored

## Vulnerability Disclosure Policy

### Our Commitment

- We'll respond to security reports promptly
- We'll work with you to understand and resolve issues
- We'll credit researchers in security advisories (with permission)
- We won't take legal action against security researchers following responsible disclosure

### Our Expectations

- Give us reasonable time to fix issues before public disclosure
- Don't access or modify user data without authorization
- Don't perform testing on production sites without permission
- Don't engage in social engineering or phishing

## Contact

For security concerns:
- **Email**: security@schoedel.design
- **Website**: https://schoedel.design/security

For general support:
- **GitHub Issues**: https://github.com/schoedel-learn/log-changes/issues
- **Website**: https://schoedel.design/support

## Hall of Fame

We'd like to thank the following researchers for responsibly disclosing vulnerabilities:

*(No vulnerabilities reported yet)*

---

**Thank you for helping keep Log Changes and its users secure!**
