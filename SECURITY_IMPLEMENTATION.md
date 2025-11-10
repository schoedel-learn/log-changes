# Security Implementation Details

This document describes the security measures implemented in the Log Changes WordPress plugin and the repository security configuration.

## Repository Security Configuration

### Automated Security Scanning

#### 1. CodeQL Analysis
- **Location**: `.github/workflows/codeql.yml`
- **Frequency**: On push to main/develop, pull requests, weekly schedule
- **Languages**: PHP and JavaScript
- **Purpose**: Automated vulnerability detection
- **Action Items**: Review and address any alerts in Security tab

#### 2. PHP Linting and WordPress Standards
- **Location**: `.github/workflows/php-linting.yml`
- **Tests**: 
  - PHP syntax check across versions 7.4-8.3
  - WordPress Coding Standards (PHPCS)
- **Purpose**: Ensure code quality and identify potential issues

#### 3. Dependency Review
- **Location**: `.github/workflows/dependency-review.yml`
- **Triggers**: Pull requests
- **Purpose**: Review dependencies for known vulnerabilities
- **Settings**: Fails on moderate+ severity, checks license compliance

#### 4. OpenSSF Scorecard
- **Location**: `.github/workflows/security-scorecard.yml`
- **Frequency**: Weekly and on main branch pushes
- **Purpose**: Comprehensive security posture assessment
- **Output**: Security score and recommendations

#### 5. Dependabot
- **Location**: `.github/dependabot.yml`
- **Updates**: 
  - GitHub Actions weekly
  - Composer dependencies weekly
- **Purpose**: Automated dependency updates and security patches

### Branch Protection

See [BRANCH_PROTECTION.md](BRANCH_PROTECTION.md) for detailed configuration instructions.

**Must be configured manually** in GitHub repository settings:
- Require pull request reviews (1 approval minimum)
- Require status checks to pass
- Require conversation resolution
- Require linear history
- Include administrators
- No force pushes
- No deletions

### Code Review

- **CODEOWNERS**: Automatic review requests for code changes
- **PR Template**: Comprehensive checklist for contributors
- **Issue Templates**: Structured reporting for bugs, features, and security

## Plugin Security Features

### 1. Input Validation & Sanitization

All user inputs are sanitized using WordPress functions:

```php
// Text fields
sanitize_text_field( $input )

// Integers
absint( $input )

// Email addresses
sanitize_email( $input )

// URLs
esc_url_raw( $input )

// Textareas
sanitize_textarea_field( $input )
```

**Implementation locations**:
- Line 1037, 1056, 1077: Nonce verification with sanitization
- Line 1134, 1527: Search term sanitization with `wpdb->esc_like()`
- Throughout: All `$_GET` and `$_POST` access

### 2. Output Escaping

All output is escaped using appropriate WordPress functions:

```php
// HTML content
esc_html( $text )

// HTML attributes
esc_attr( $value )

// URLs
esc_url( $url )

// SQL (for table names)
esc_sql( $table_name )

// JavaScript strings
esc_js( $string )
```

**Implementation locations**:
- Admin page template: All dynamic content escaped
- JavaScript localization: Proper escaping in `wp_localize_script()`
- Database queries: Table names use controlled prefix

### 3. SQL Injection Prevention

All database queries use prepared statements:

```php
// Prepared statement with placeholders
$wpdb->prepare( 
    "SELECT * FROM {$this->table_name} WHERE id = %d", 
    $id 
);

// With IN clause (multiple values)
$placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
$wpdb->prepare( 
    "SELECT * FROM {$this->table_name} WHERE column IN ($placeholders)", 
    ...$values 
);
```

**Implementation locations**:
- Line 1179, 1293: Count queries with WHERE clauses
- Line 1239, 1241: SELECT queries with prepared statements
- Line 1296: DELETE queries with prepared statements
- Line 1317, 1366: Cleanup queries with date comparison
- Line 1541-1553: Admin page queries

**Table Name Security**:
- Table name set once in constructor: `$wpdb->prefix . 'change_log'`
- Prefix comes from WordPress core (validated)
- Constant throughout plugin lifecycle
- No user input affects table name

### 4. Access Control

All admin actions require proper capabilities:

```php
// Check capability before any sensitive operation
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Unauthorized', 'log-changes' ) );
}
```

**Implementation locations**:
- Line 202: Admin menu registration
- Line 1042-1043: Export capability check
- Line 1061-1062: Delete capability check
- Line 1082-1083: Delete after export capability check
- Line 1333: Settings save capability check
- Line 1358: Manual cleanup capability check

### 5. Nonce Verification

All state-changing operations use nonces:

```php
// Generate nonce
wp_create_nonce( 'log_changes_action' );

// Verify nonce
wp_verify_nonce( $nonce, 'log_changes_action' );
```

**Implementation locations**:
- Line 1037: Export nonce verification
- Line 1056: Export & delete nonce verification
- Line 1077: Delete nonce verification
- Line 1335: Settings save nonce verification
- Line 1360: Manual cleanup nonce verification

**Nonce Actions Used**:
- `log_changes_export` - CSV export
- `log_changes_export_delete` - Export then delete
- `log_changes_delete` - Delete logs
- `log_changes_settings` - Save settings
- `log_changes_manual_cleanup` - Manual cleanup

### 6. CSRF Protection

Nonces prevent Cross-Site Request Forgery:

- All forms include nonce fields
- All links with actions include nonce parameters
- Nonces verified before processing
- Invalid nonces result in `wp_die()`

### 7. XSS Prevention

#### Server-side (PHP)
- All output escaped with appropriate functions
- No raw echo of user input
- HTML generation uses safe WordPress functions

#### Client-side (JavaScript)
- Uses jQuery `.text()` for safe text insertion
- Uses `document.createTextNode()` for dynamic content
- Uses jQuery `.attr()` with validated values
- No `.html()` with user content
- No `innerHTML` with dynamic data

**Implementation**: `assets/js/admin.js` lines 97-102

### 8. IP Spoofing Protection

IP addresses validated against multiple server variables:

```php
// Get real IP address with anti-spoofing
private function get_client_ip() {
    // Check multiple headers in order of reliability
    // Validate IP format
    // Return validated IP or fallback
}
```

**Implementation**: Comprehensive IP detection in tracking methods

### 9. File System Security

#### Direct Access Prevention
All PHP files start with:
```php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

**Implementation locations**:
- Line 19-21: Main plugin file
- includes/admin-page.php: Admin template
- uninstall.php: Uninstall script

#### File Permissions
- Plugin files should be read-only for web server
- No file uploads or writes except to database
- No temporary file creation

### 10. Database Security

#### Table Creation
- Uses `dbDelta()` for safe table management
- Proper charset and collation
- Indexed columns for performance

#### Cleanup
- Automatic cleanup prevents database bloat
- Configurable retention period (1-365 days)
- Manual cleanup available
- Cleanup actions logged for audit

### 11. Privacy Protection

#### Data Minimization
- Only logs necessary information
- Automatic deletion after retention period
- Export functionality for GDPR compliance

#### Data Access
- Only admins can view logs
- IP addresses for audit purposes only
- No external data transmission

#### GDPR Compliance Features
- Configurable retention period
- Export to CSV for data portability
- Complete deletion on uninstall
- Privacy policy disclosure recommendations

## Security Best Practices for Users

### Installation Security
1. Download only from official sources (GitHub releases)
2. Verify integrity if checksums provided
3. Review code before installation (if concerned)
4. Use HTTPS for WordPress admin

### Configuration Security
1. Limit admin access to trusted users
2. Use strong passwords for all accounts
3. Enable two-factor authentication
4. Regular security audits of logs

### Operational Security
1. Keep WordPress core updated
2. Keep PHP updated (7.4+ minimum, 8.0+ recommended)
3. Keep plugin updated to latest version
4. Regular backups of database
5. Export logs before automatic deletion
6. Review logs regularly for anomalies

### Server Security
1. Proper file permissions (644 for files, 755 for directories)
2. Disable directory listing
3. Use ModSecurity or similar WAF
4. Enable fail2ban for brute force protection
5. Use security headers (CSP, X-Frame-Options, etc.)

## Security Testing

### Manual Testing Checklist

#### Input Validation
- [ ] Test SQL injection attempts in search
- [ ] Test XSS attempts in all inputs
- [ ] Test CSRF with forged requests
- [ ] Test unauthorized access attempts

#### Authentication & Authorization
- [ ] Test as non-admin user (should not see logs)
- [ ] Test capability checks with different roles
- [ ] Test nonce validation with expired nonces
- [ ] Test nonce validation with wrong nonces

#### Data Security
- [ ] Verify sensitive data not in HTML source
- [ ] Verify proper output escaping
- [ ] Verify database queries use prepared statements
- [ ] Verify IP addresses validated

#### File Security
- [ ] Verify direct file access blocked
- [ ] Verify proper file permissions
- [ ] Verify no temporary files created
- [ ] Verify clean uninstall removes all data

### Automated Testing

#### CodeQL Queries
- SQL injection detection
- XSS vulnerability detection
- Command injection detection
- Path traversal detection
- Insecure deserialization
- Weak cryptography
- Hard-coded credentials

#### PHPCS Rules
- WordPress.Security.EscapeOutput
- WordPress.Security.NonceVerification
- WordPress.Security.ValidatedSanitizedInput
- WordPress.DB.PreparedSQL
- WordPress.Security.SafeRedirect

## Vulnerability Disclosure

### Reporting
See [SECURITY.md](SECURITY.md) for reporting procedures.

### Response Process
1. **Acknowledgment** - Within 48 hours
2. **Investigation** - 3-5 business days
3. **Fix Development** - Based on severity
4. **Testing** - Comprehensive testing of fix
5. **Release** - Security release with advisory
6. **Disclosure** - Coordinated disclosure after fix

### Security Releases
- Critical: Immediate patch release
- High: Release within 7 days
- Medium: Release within 30 days
- Low: Include in next regular release

## Security Checklist for Maintainers

### Before Each Release
- [ ] Run CodeQL analysis
- [ ] Run PHPCS with WordPress standards
- [ ] Check Dependabot alerts
- [ ] Review security scorecard
- [ ] Test with latest WordPress version
- [ ] Test with latest PHP version
- [ ] Review all changes for security implications
- [ ] Update CHANGELOG with security fixes

### Monthly
- [ ] Review security alerts
- [ ] Check for dependency updates
- [ ] Review access logs (if available)
- [ ] Update security documentation

### Quarterly
- [ ] Comprehensive security audit
- [ ] Review and update security policies
- [ ] Test backup/restore procedures
- [ ] Review and update this document

## Resources

### WordPress Security
- [WordPress Security White Paper](https://wordpress.org/about/security/)
- [WordPress Plugin Security Handbook](https://developer.wordpress.org/plugins/security/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

### PHP Security
- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)

### General Security
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CWE Top 25](https://cwe.mitre.org/top25/)
- [OpenSSF Best Practices](https://bestpractices.coreinfrastructure.org/)

## Security Contact

For security concerns:
- **Email**: security@schoedel.design
- **GitHub**: [Security Advisories](https://github.com/schoedel-learn/log-changes/security/advisories)

---

**Last Updated**: 2025-11-09  
**Security Policy Version**: 1.0  
**Plugin Version**: 1.2.0
