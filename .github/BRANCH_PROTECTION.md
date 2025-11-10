# Branch Protection Setup Guide

This document provides instructions for setting up branch protection rules for the Log Changes WordPress plugin repository.

## Why Branch Protection?

Branch protection helps maintain code quality and security by:
- Preventing direct pushes to main branches
- Requiring code reviews before merging
- Ensuring automated tests pass before merging
- Preventing force pushes and deletions
- Maintaining a clean git history

## Recommended Protection Rules for `main` Branch

### Access via GitHub Settings

1. Go to your repository on GitHub
2. Click **Settings** → **Branches**
3. Under "Branch protection rules", click **Add rule**
4. Enter `main` as the branch name pattern

### Required Settings

#### ✅ Require a pull request before merging
- **Require approvals**: 1 (minimum)
- **Dismiss stale pull request approvals when new commits are pushed**: ✅
- **Require review from Code Owners**: ✅ (if CODEOWNERS file exists)
- **Require approval of the most recent reviewable push**: ✅

#### ✅ Require status checks to pass before merging
- **Require branches to be up to date before merging**: ✅
- Required status checks:
  - `phpcs` (WordPress Coding Standards)
  - `php-compatibility` (PHP version compatibility)
  - `markdown-lint` (Documentation quality)

#### ✅ Require conversation resolution before merging
- All conversations on code must be resolved before merging

#### ✅ Require signed commits
- ✅ Recommended for enhanced security

#### ✅ Require linear history
- ✅ Prevents merge commits, keeps history clean
- Use "Squash and merge" or "Rebase and merge"

#### ✅ Do not allow bypassing the above settings
- ⚠️ Even administrators must follow the rules
- For emergency fixes, temporarily disable protection

#### ✅ Restrict who can push to matching branches
- **Optional**: Limit to specific people or teams
- **Recommended for production**: Only CI/CD and maintainers

#### ✅ Allow force pushes
- ❌ Disabled (prevents history rewriting)

#### ✅ Allow deletions
- ❌ Disabled (prevents accidental branch deletion)

### Additional Recommended Settings

#### Lock branch
- ❌ Not locked (allows changes via PRs)
- Only lock for archive/frozen releases

#### Require deployments to succeed before merging
- ⚠️ Optional: Enable if you have deployment previews
- Ensures changes deploy successfully before merge

## Protection Rules for `develop` Branch

If using Git Flow, also protect the `develop` branch with similar rules:

### Less Strict Than Main
- Require pull request: ✅ (but can be 0 approvals for development)
- Require status checks: ✅
- Allow force pushes: ❌
- Allow deletions: ❌

## Setting Up via GitHub API (Automated)

For automated setup, use the GitHub API:

```bash
curl -X PUT \
  -H "Accept: application/vnd.github.v3+json" \
  -H "Authorization: token YOUR_GITHUB_TOKEN" \
  https://api.github.com/repos/schoedel-learn/log-changes/branches/main/protection \
  -d '{
    "required_status_checks": {
      "strict": true,
      "contexts": ["phpcs", "php-compatibility", "markdown-lint"]
    },
    "enforce_admins": true,
    "required_pull_request_reviews": {
      "dismissal_restrictions": {},
      "dismiss_stale_reviews": true,
      "require_code_owner_reviews": false,
      "required_approving_review_count": 1,
      "require_last_push_approval": true
    },
    "restrictions": null,
    "required_linear_history": true,
    "allow_force_pushes": false,
    "allow_deletions": false,
    "required_conversation_resolution": true
  }'
```

## CODEOWNERS File (Optional)

Create `.github/CODEOWNERS` to automatically request reviews from specific people:

```
# Default owners for everything in the repo
* @schoedel-learn

# Specific file owners
/includes/ @schoedel-learn
/assets/ @schoedel-learn
*.php @schoedel-learn
*.js @schoedel-learn

# Documentation
*.md @schoedel-learn
/docs/ @schoedel-learn

# Configuration files
.github/ @schoedel-learn
composer.json @schoedel-learn
phpcs.xml @schoedel-learn
```

## Testing Branch Protection

After setup, verify protection is working:

1. Try pushing directly to main:
   ```bash
   git checkout main
   echo "test" >> test.txt
   git add test.txt
   git commit -m "Test direct push"
   git push origin main
   ```
   **Expected**: Push should be rejected

2. Try creating a PR without passing tests:
   - Create a branch with failing code
   - Open a PR
   **Expected**: Merge button should be disabled until tests pass

3. Try merging without approval:
   - Open a valid PR
   - Try to merge without review
   **Expected**: Merge should be blocked until approved

## Workflow for Contributors

With branch protection enabled:

1. **Fork the repository** (external contributors) or **create a branch** (team members)
2. **Make changes** in your branch
3. **Commit** following the commit message guidelines
4. **Push** to your fork/branch
5. **Open a Pull Request** against `main`
6. **Wait for automated tests** to complete
7. **Address review comments** if any
8. **Get approval** from maintainers
9. **Squash and merge** once approved and tests pass

## Emergency Procedures

If you need to push an emergency fix:

1. Temporarily disable branch protection:
   - Settings → Branches → Edit rule
   - Uncheck "Include administrators"
   
2. Make the emergency commit

3. Re-enable protection immediately

4. Open a follow-up PR for additional review

**Note**: Document all emergency changes in an incident report.

## Best Practices

### For Maintainers
- Review PRs promptly (within 48 hours)
- Provide constructive feedback
- Test changes locally when needed
- Approve only when confident in quality
- Use "Request changes" when issues found
- Squash merge to keep history clean

### For Contributors
- Create small, focused PRs
- Write clear descriptions
- Respond to feedback promptly
- Keep your branch up to date
- Resolve conflicts before review
- Follow the contribution guidelines

## Monitoring

Regularly check:
- Protected branch settings are still active
- Status checks are running correctly
- No unauthorized changes to protection rules
- Review process is followed consistently

## Troubleshooting

### Tests Failing in CI
1. Run tests locally first: `composer run-script test`
2. Fix issues before pushing
3. Ensure composer dependencies are updated

### Can't Merge PR
- Check required approvals are obtained
- Verify all status checks pass
- Resolve all conversations
- Update branch if behind main

### Protection Rules Not Working
- Verify you're targeting the correct branch
- Check rule is enabled (not saved as draft)
- Ensure GitHub Actions workflows exist
- Check required status check names match workflow jobs

## Additional Resources

- [GitHub Branch Protection Documentation](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/defining-the-mergeability-of-pull-requests/about-protected-branches)
- [WordPress Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [Contributing Guide](../CONTRIBUTING.md)

## Maintenance

Review and update branch protection rules:
- **Quarterly**: Review if rules are appropriate
- **When adding new checks**: Update required status checks
- **Team changes**: Update reviewers/restrictions
- **Process improvements**: Adjust based on team feedback

---

**Last Updated**: 2024-11-10
**Owner**: Barry Schoedel (@schoedel-learn)
