# Security Policy

## Supported Versions

We release security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.2.x   | :white_check_mark: |
| 1.1.x   | :white_check_mark: |
| < 1.1   | :x:                |

## Reporting a Vulnerability

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
