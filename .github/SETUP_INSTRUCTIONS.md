# Repository Setup Instructions

This document provides step-by-step instructions for setting up branch protection and best practices for the Log Changes WordPress plugin repository.

## Prerequisites

- Admin access to the GitHub repository
- Git installed locally
- Composer installed (for development)

## Step 1: Enable Branch Protection Rules

### Via GitHub Web Interface

1. Navigate to your repository on GitHub: https://github.com/schoedel-learn/log-changes

2. Go to **Settings** → **Branches** (left sidebar)

3. Click **Add rule** under "Branch protection rules"

4. Configure the following:

   **Branch name pattern:**
   ```
   main
   ```

   **Protect matching branches - Enable these checkboxes:**
   
   ✅ **Require a pull request before merging**
   - Required approvals: 1
   - ✅ Dismiss stale pull request approvals when new commits are pushed
   - ✅ Require approval of the most recent reviewable push

   ✅ **Require status checks to pass before merging**
   - ✅ Require branches to be up to date before merging
   - Search and select required checks (after first workflow run):
     - `phpcs` (WordPress Coding Standards)
     - `php-compatibility` (PHP Compatibility Check)

   ✅ **Require conversation resolution before merging**
   
   ✅ **Require linear history**
   
   ✅ **Do not allow bypassing the above settings**
   - ⚠️ Uncheck "Allow specified actors to bypass required pull requests"
   
   ✅ **Restrict who can push to matching branches** (Optional)
   - Add specific users or teams if needed
   
   ❌ **Allow force pushes** - Leave UNCHECKED
   
   ❌ **Allow deletions** - Leave UNCHECKED

5. Click **Create** or **Save changes**

### Testing Branch Protection

After setup, verify it's working:

```bash
# Try to push directly to main (should fail)
git checkout main
echo "test" >> test.txt
git add test.txt
git commit -m "Test direct push"
git push origin main  # This should be rejected

# Clean up
git reset --hard HEAD~1
rm test.txt
```

## Step 2: Verify GitHub Actions

1. Navigate to **Actions** tab in your repository

2. If workflows are disabled, click **Enable workflows**

3. After enabling, make a test pull request to trigger workflows

4. Verify all workflows complete successfully:
   - ✅ WordPress Coding Standards
   - ✅ PHP Compatibility Check
   - ✅ Markdown Lint

## Step 3: Set Up Required Status Checks

⚠️ **Important:** Status checks can only be added as required after they run at least once.

1. Make your first pull request (this PR will do!)

2. Wait for all GitHub Actions to complete

3. Go back to **Settings** → **Branches** → Edit your rule

4. Under "Require status checks to pass before merging", search for:
   - `phpcs`
   - `php-compatibility`
   - `markdown-lint` (optional)

5. Select them to make them required

6. Save the rule

## Step 4: Configure Repository Settings

### General Settings

1. Go to **Settings** → **General**

2. **Pull Requests:**
   - ✅ Allow squash merging (keeps history clean)
   - ✅ Allow rebase merging
   - ❌ Allow merge commits (recommended to disable)
   - ✅ Always suggest updating pull request branches
   - ✅ Automatically delete head branches

3. **Features:**
   - ✅ Issues
   - ✅ Wikis (optional)
   - ❌ Discussions (optional, can enable later)
   - ❌ Projects (optional)

### Security Settings

1. Go to **Settings** → **Security** → **Code security and analysis**

2. Enable:
   - ✅ Dependabot alerts
   - ✅ Dependabot security updates
   - ✅ Code scanning (CodeQL)
   - ✅ Secret scanning

## Step 5: Set Up Local Development Environment

### For Contributors

1. Clone the repository:
   ```bash
   git clone https://github.com/schoedel-learn/log-changes.git
   cd log-changes
   ```

2. Install development dependencies:
   ```bash
   composer install
   ```

3. Configure git hooks (optional):
   ```bash
   # Create pre-commit hook
   cat > .git/hooks/pre-commit << 'EOF'
   #!/bin/bash
   composer run-script phpcs
   if [ $? -ne 0 ]; then
       echo "PHPCS check failed. Please fix errors before committing."
       exit 1
   fi
   EOF
   chmod +x .git/hooks/pre-commit
   ```

4. Run code quality checks:
   ```bash
   # Check coding standards
   composer run-script phpcs
   
   # Fix auto-fixable issues
   composer run-script phpcbf
   ```

## Step 6: Create CODEOWNERS File (Optional)

Create `.github/CODEOWNERS` to automatically request reviews:

```
# Default owners for everything
* @schoedel-learn

# PHP files require maintainer review
*.php @schoedel-learn

# Documentation
*.md @schoedel-learn
/docs/ @schoedel-learn

# Configuration files
/.github/ @schoedel-learn
composer.json @schoedel-learn
phpcs.xml @schoedel-learn
```

Commit this file:
```bash
git add .github/CODEOWNERS
git commit -m "Add CODEOWNERS file"
git push origin main
```

## Step 7: Configure Notifications

### Email Notifications

1. Go to **Settings** → **Notifications** (your personal settings)

2. Configure notification preferences:
   - ✅ Pull request reviews
   - ✅ Pull request pushes
   - ✅ Comments on issues and pull requests

### Watching the Repository

1. Click **Watch** at the top of the repository

2. Select your preference:
   - **All Activity** - Get notified of everything
   - **Custom** - Choose specific events

## Step 8: Update Repository Description

1. Go to the repository home page

2. Click the ⚙️ icon next to "About"

3. Update:
   - **Description:** "WordPress plugin for tracking all changes with detailed audit trails"
   - **Website:** https://schoedel.design
   - **Topics:** `wordpress`, `plugin`, `audit-log`, `activity-log`, `change-tracking`, `php`
   - ✅ **Include in the home page:** (if you want it featured)

## Step 9: Create Release Workflow (Optional)

Create `.github/workflows/release.yml` for automated releases:

```yaml
name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Create Release
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          tag_name: ${{ github.ref }}
          release_name: Release ${{ github.ref }}
          draft: false
          prerelease: false
```

## Step 10: Verify Everything Works

### Checklist

- [ ] Branch protection is active on `main`
- [ ] Direct pushes to `main` are blocked
- [ ] Pull requests require approval
- [ ] Status checks run on pull requests
- [ ] Required status checks are configured
- [ ] Squash merging is enabled
- [ ] Head branches auto-delete after merge
- [ ] Dependabot is enabled
- [ ] Code scanning (CodeQL) is enabled
- [ ] Secret scanning is enabled
- [ ] GitHub Actions workflows run successfully
- [ ] Contributors can fork and create PRs
- [ ] CODEOWNERS is configured (optional)
- [ ] Email notifications are configured

## Troubleshooting

### Branch Protection Not Working

- Ensure you saved the rule after creating it
- Check you're testing with the correct branch name
- Verify you don't have bypass permissions enabled

### Status Checks Not Appearing

- Ensure GitHub Actions workflows have run at least once
- Check workflow files are in `.github/workflows/`
- Verify workflows completed successfully
- Look for the exact job names in workflow YAML

### Composer Install Fails

```bash
# Clear cache and try again
composer clear-cache
composer install
```

### PHPCS Errors

```bash
# Auto-fix what's possible
composer run-script phpcbf

# Check remaining issues
composer run-script phpcs
```

## Maintenance

### Regular Tasks

**Weekly:**
- Review open pull requests
- Check for Dependabot alerts
- Review security advisories

**Monthly:**
- Update dependencies: `composer update`
- Review branch protection rules
- Check GitHub Actions usage

**Quarterly:**
- Review and update documentation
- Audit repository access
- Review security settings

## Getting Help

If you encounter issues:

1. Check [GitHub Documentation](https://docs.github.com)
2. Review [CONTRIBUTING.md](../CONTRIBUTING.md)
3. Search existing [Issues](https://github.com/schoedel-learn/log-changes/issues)
4. Create a new issue with the `question` label

## Additional Resources

- [Branch Protection Guide](.github/BRANCH_PROTECTION.md)
- [Contributing Guidelines](../CONTRIBUTING.md)
- [Security Policy](../SECURITY.md)
- [Code of Conduct](../CODE_OF_CONDUCT.md)

---

**Setup Complete!** 🎉

Your repository now has enterprise-grade protections and follows WordPress plugin development best practices.
