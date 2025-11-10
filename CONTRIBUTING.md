# Contributing to Log Changes

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
