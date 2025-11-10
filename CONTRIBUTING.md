# Contributing to Log Changes

First off, thank you for considering contributing to Log Changes! It's people like you that make this plugin better for everyone.

## Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the [existing issues](https://github.com/schoedel-learn/log-changes/issues) to see if the problem has already been reported. If it has and the issue is still open, add a comment to the existing issue instead of opening a new one.

When creating a bug report, please include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** to demonstrate the steps
- **Describe the behavior you observed** and what you expected to see
- **Include screenshots or animated GIFs** if relevant
- **Include your environment details**: WordPress version, PHP version, plugin version, browser, etc.

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- **Use a clear and descriptive title**
- **Provide a detailed description** of the suggested enhancement
- **Explain why this enhancement would be useful** to most users
- **List any similar features** in other plugins if applicable
- **Include mockups or examples** if relevant

### Security Vulnerabilities

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them responsibly:
- Use [GitHub Security Advisories](https://github.com/schoedel-learn/log-changes/security/advisories/new)
- Or email: security@schoedel.design

See our [Security Policy](SECURITY.md) for more details.

### Pull Requests

We actively welcome your pull requests! Here's the process:

1. **Fork the repository** and create your branch from `main`
2. **Make your changes** following our coding standards
3. **Test thoroughly** - ensure your changes work and don't break existing functionality
4. **Update documentation** - README, inline comments, PHPDoc, etc.
5. **Ensure code quality** - run linters and follow WordPress coding standards
6. **Write clear commit messages** - use conventional commits format
7. **Submit your pull request** - fill out the PR template completely
Thank you for your interest in contributing to Log Changes! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Making Changes](#making-changes)
- [Testing](#testing)
- [Submitting Changes](#submitting-changes)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Features](#suggesting-features)

## Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a new branch for your changes
4. Make your changes
5. Test thoroughly
6. Submit a pull request

## Development Setup

### Prerequisites

- WordPress 5.0 or higher
- PHP 7.2 or higher (PHP 8.0+ recommended for development)
- MySQL 5.6+ or MariaDB 10.0+
- Composer (for development dependencies)
- Git

### Local Development Environment

1. **Clone the repository**
   ```bash
- PHP 7.2 or higher
- MySQL 5.6+ or MariaDB 10.0+
- Composer (for development dependencies)
- Node.js and npm (if working with assets)

### Local Development

1. Clone the repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/schoedel-learn/log-changes.git
   cd log-changes
   ```

2. **Install development dependencies**
   ```bash
   composer install --dev
   ```

3. **Set up WordPress locally**
   - Use Local by Flywheel, XAMPP, Docker, or your preferred WordPress development environment
   - Install WordPress
   - Copy the plugin to `wp-content/plugins/log-changes/`
   - Activate the plugin

4. **Enable WordPress debugging**
   Add to your `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   define('SCRIPT_DEBUG', true);
2. Install development dependencies:
   ```bash
   composer install
   ```

3. Activate the plugin in WordPress admin

4. Enable WordPress debugging in `wp-config.php`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   define( 'SCRIPT_DEBUG', true );
   ```

## Coding Standards

### PHP Code Standards

We follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

- Use tabs for indentation
- Use single quotes for strings (unless you need variable interpolation)
- Brace style: opening brace on same line, closing brace on its own line
- Use Yoda conditions for comparisons
- Always use strict comparisons (`===` instead of `==`)
- Document all functions with PHPDoc blocks

**Check your code:**
```bash
composer phpcs
```

**Auto-fix issues:**
```bash
composer phpcbf
```

### JavaScript Code Standards

Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/):

- Use tabs for indentation
- Use single quotes for strings
- Use camelCase for variable and function names
- Add comments for complex logic

### CSS Code Standards

Follow [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/):

- Use tabs for indentation
- Use lowercase for selectors
- Include a space before opening brace
- One property per line
- Include space after colon

## Security Best Practices

When contributing code, always follow these security practices:

### Input Validation & Sanitization
- **Sanitize all user input** using WordPress sanitization functions:
  - `sanitize_text_field()` for text
  - `absint()` for integers
  - `sanitize_email()` for emails
  - `esc_url_raw()` for URLs

### Output Escaping
- **Escape all output** using appropriate functions:
  - `esc_html()` for HTML content
  - `esc_attr()` for HTML attributes
  - `esc_url()` for URLs
  - `esc_js()` for JavaScript

### Database Queries
- **Always use prepared statements**:
  ```php
  $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
  ```
- **Escape table names**:
  ```php
  esc_sql($table_name)
  ```

### Capability Checks
- **Always check user capabilities**:
  ```php
  if (!current_user_can('manage_options')) {
      wp_die(__('Unauthorized', 'log-changes'));
  }
  ```

### Nonce Verification
- **Verify nonces for forms and AJAX**:
  ```php
  check_admin_referer('log_changes_action');
  ```

### Direct File Access
- **Prevent direct file access**:
  ```php
  if (!defined('ABSPATH')) {
      exit;
  }
  ```

## Testing

### Manual Testing

Before submitting a PR, test your changes:

1. **Fresh WordPress installation** - Test on a clean WP install
2. **Different environments** - Test with different PHP versions if possible
3. **Other plugins** - Test with common plugins active
4. **Different themes** - Test with a few popular themes
5. **Different browsers** - Test the admin interface in multiple browsers
6. **Edge cases** - Think about unusual scenarios and test them

### Testing Checklist

- [ ] Changes work as intended
- [ ] No PHP errors or warnings in debug.log
- [ ] No JavaScript errors in browser console
- [ ] No SQL errors or warnings
- [ ] Plugin activation/deactivation works
- [ ] Database tables created/removed properly
- [ ] Settings save and load correctly
- [ ] Export functionality works
- [ ] No XSS vulnerabilities introduced
- [ ] No SQL injection vulnerabilities
- [ ] Performance is acceptable
- [ ] Works on different screen sizes (responsive)

## Documentation

### Inline Code Comments

- Comment complex logic
- Explain "why" not just "what"
- Use PHPDoc blocks for all functions, classes, and methods
- Keep comments up to date with code changes

### PHPDoc Format

```php
/**
 * Brief description of function.
 *
 * Longer description if needed, explaining what the function does,
 * any important notes, and usage examples.
 *
 * @since 1.2.0
 * @param string $param1 Description of parameter.
 * @param int    $param2 Description of parameter.
 * @return bool True on success, false on failure.
 */
function my_function($param1, $param2) {
    // Function code
}
```

### README Updates

If your changes affect user-facing functionality:

- Update README.md with new features or changed behavior
- Add usage examples if introducing new functionality
- Update the changelog section
- Update screenshots if UI changes are made

## Git Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation only changes
- `style:` - Code style changes (formatting, no logic change)
- `refactor:` - Code refactoring (no feature change or bug fix)
- `perf:` - Performance improvements
- `test:` - Adding or updating tests
- `chore:` - Maintenance tasks

**Examples:**
```
feat: add export to JSON functionality
fix: correct SQL injection vulnerability in search
docs: update installation instructions
refactor: optimize database query performance
```

## Branch Naming

Use descriptive branch names with prefixes:

- `feature/` - New features (e.g., `feature/json-export`)
- `fix/` - Bug fixes (e.g., `fix/sql-injection`)
- `security/` - Security fixes (e.g., `security/xss-prevention`)
- `docs/` - Documentation (e.g., `docs/update-readme`)
- `refactor/` - Refactoring (e.g., `refactor/database-queries`)

## Pull Request Process

1. **Update documentation** for any changed functionality
2. **Update the version number** if applicable (discuss with maintainers)
3. **Ensure all tests pass** and no new warnings are introduced
4. **Fill out the PR template** completely
5. **Link related issues** using GitHub keywords (Fixes #123)
6. **Request review** from maintainers
7. **Address review feedback** promptly and professionally
8. **Squash commits** if requested before merging

### PR Review Criteria

Your PR will be reviewed for:

- **Functionality** - Does it work as intended?
- **Code quality** - Does it follow our standards?
- **Security** - Does it introduce vulnerabilities?
- **Performance** - Does it impact performance negatively?
- **Documentation** - Is it properly documented?
- **Testing** - Has it been tested thoroughly?
- **Backwards compatibility** - Does it break existing functionality?

## Release Process

Releases are managed by maintainers:

1. Version numbers follow [Semantic Versioning](https://semver.org/)
2. CHANGELOG.md is updated with all changes
3. Git tags are created for releases
4. Releases are published to WordPress.org plugin directory (when applicable)

## Questions?

- **Documentation**: Read the [README](README.md) and other docs
- **Support**: Visit https://schoedel.design/support
- **Discussions**: Use [GitHub Discussions](https://github.com/schoedel-learn/log-changes/discussions)
- **Issues**: Search [existing issues](https://github.com/schoedel-learn/log-changes/issues)

## Recognition

Contributors are recognized in several ways:

- Listed in CHANGELOG.md for their contributions
- Mentioned in release notes
- GitHub contributions graph
- Community recognition and thanks!

## License

By contributing, you agree that your contributions will be licensed under the MIT License. See [LICENSE](LICENSE) file for details.

---

Thank you for contributing to Log Changes! 🎉
This plugin follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

### PHP

- Use WordPress PHP Coding Standards
- Run PHPCS before committing:
  ```bash
  composer run-script phpcs
  ```
- Fix automatically when possible:
  ```bash
  composer run-script phpcbf
  ```

### Key Guidelines

- **Indentation**: Use tabs for indentation
- **Line Length**: Maximum 100 characters (soft limit)
- **Braces**: Opening braces on same line for functions and control structures
- **Naming**:
  - Functions: `lowercase_with_underscores()`
  - Classes: `Capitalized_With_Underscores`
  - Constants: `UPPERCASE_WITH_UNDERSCORES`
- **Comments**: Use DocBlocks for all functions and classes
- **Security**: Always sanitize input and escape output

### Security Best Practices

- Use `sanitize_text_field()`, `absint()`, etc. for input sanitization
- Use `esc_html()`, `esc_attr()`, `esc_url()` for output escaping
- Use `$wpdb->prepare()` for database queries
- Check capabilities with `current_user_can()`
- Verify nonces for form submissions
- Never trust user input

### Internationalization (i18n)

- All user-facing strings must be translatable
- Use `esc_html__()`, `esc_html_e()`, etc.
- Text domain: `log-changes`
- Example:
  ```php
  echo esc_html__( 'Change Log', 'log-changes' );
  ```

## Making Changes

### Branch Naming

Use descriptive branch names:
- `feature/add-email-notifications`
- `bugfix/fix-date-filter`
- `enhancement/improve-performance`
- `docs/update-readme`

### Commit Messages

Write clear, descriptive commit messages:
- Use present tense ("Add feature" not "Added feature")
- Use imperative mood ("Move cursor to..." not "Moves cursor to...")
- First line: brief summary (50 chars or less)
- Blank line, then detailed description if needed
- Reference issues: "Fixes #123" or "Relates to #456"

Good examples:
```
Add email notification feature for critical changes

Implements email notifications when specific events occur:
- User role changes
- Plugin activations/deactivations
- Theme switches

Fixes #42
```

## Testing

### Manual Testing

Before submitting a PR, test your changes:

1. **Functionality**: Verify the change works as expected
2. **Edge Cases**: Test boundary conditions and error cases
3. **Compatibility**: Test with different WordPress/PHP versions if possible
4. **Performance**: Check for performance impacts
5. **Security**: Verify no security vulnerabilities introduced

### Test Checklist

- [ ] Change works in WordPress admin
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors
- [ ] Works with WP_DEBUG enabled
- [ ] Follows WordPress coding standards
- [ ] All strings are internationalized
- [ ] Security best practices followed
- [ ] Documentation updated if needed

### Test Environment

Test in an environment similar to production:
- Fresh WordPress installation
- Common plugins active
- Default and popular themes
- Different PHP versions if possible

## Submitting Changes

### Pull Request Process

1. Update documentation if you've changed functionality
2. Update CHANGELOG.md following the existing format
3. Ensure all tests pass and coding standards are met
4. Fill out the pull request template completely
5. Link to any relevant issues
6. Request review from maintainers

### Pull Request Guidelines

- **One feature per PR**: Keep changes focused and atomic
- **Description**: Explain what and why, not just how
- **Documentation**: Update README, inline comments, etc.
- **Backwards Compatibility**: Don't break existing functionality
- **Testing**: Describe how you tested the changes

### Review Process

1. Maintainer reviews the PR
2. Feedback may be provided via comments
3. Make requested changes and push updates
4. Once approved, a maintainer will merge

## Reporting Bugs

### Before Reporting

1. Check if the bug is already reported in [Issues](https://github.com/schoedel-learn/log-changes/issues)
2. Test with only this plugin active
3. Test with a default WordPress theme
4. Enable WP_DEBUG and check for errors

### Bug Report Template

Use the bug report template when creating an issue. Include:
- Clear description of the bug
- Steps to reproduce
- Expected vs actual behavior
- WordPress environment details
- Error messages if any
- Screenshots if applicable

## Suggesting Features

### Feature Requests

We welcome feature suggestions! Use the feature request template and include:
- Clear description of the feature
- Use case and benefits
- How it fits with existing functionality
- Compatibility considerations
- Your willingness to contribute

### Feature Considerations

Good features for Log Changes:
- Enhance tracking capabilities
- Improve user experience
- Add useful filtering/reporting
- Increase security
- Optimize performance

## Code Review

All submissions require review. We look for:
- **Functionality**: Does it work as intended?
- **Code Quality**: Is it clean, readable, maintainable?
- **Standards**: Does it follow WordPress coding standards?
- **Security**: Are there any vulnerabilities?
- **Performance**: Is it efficient?
- **Documentation**: Is it properly documented?
- **Testing**: Has it been adequately tested?

## Development Tools

### Recommended Tools

- **IDE**: PHPStorm, VS Code with PHP extensions
- **Debugging**: Xdebug, Query Monitor plugin
- **Code Quality**: PHP_CodeSniffer with WordPress rules
- **Version Control**: Git with meaningful commit messages
- **Local Environment**: Local by Flywheel, XAMPP, Docker

### Useful Composer Scripts

```bash
# Check coding standards
composer run-script phpcs

# Fix coding standards automatically
composer run-script phpcbf

# Run all checks
composer run-script test
```

## Documentation

When adding new features:
- Update README.md with usage examples
- Add inline code comments for complex logic
- Update CHANGELOG.md with your changes
- Consider adding screenshots for UI changes

## Questions?

If you have questions:
- Check existing documentation
- Search closed issues
- Open a new issue with the "question" label
- Contact maintainers via GitHub

## Recognition

Contributors will be recognized in:
- CHANGELOG.md for their contributions
- GitHub contributors page
- Release notes when applicable

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Thank You!

Your contributions make this project better for everyone. We appreciate your time and effort!

---

**Happy Contributing!** 🎉
