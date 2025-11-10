# Branch Protection Configuration

This document describes the recommended branch protection rules for the Log Changes repository. These rules must be configured manually through the GitHub repository settings.

## Why Branch Protection?

Branch protection rules help maintain code quality and security by:
- Preventing accidental force pushes and deletions
- Requiring code reviews before merging
- Ensuring automated checks pass before merging
- Maintaining a clean commit history
- Protecting against unauthorized changes

## Recommended Protection Rules for `main` Branch

To configure these settings, go to:
**Repository Settings → Branches → Add branch protection rule**

### Basic Settings

**Branch name pattern:** `main`

### Protection Rules to Enable

#### 1. Require Pull Request Reviews Before Merging
- [x] **Enable** - Require a pull request before merging
- **Required approvals:** 1
- [x] Dismiss stale pull request approvals when new commits are pushed
- [x] Require review from Code Owners
- [ ] Restrict who can dismiss pull request reviews (optional - enable if you have a team)

**Why:** Ensures all changes are reviewed before being merged, maintaining code quality.

#### 2. Require Status Checks Before Merging
- [x] **Enable** - Require status checks to pass before merging
- [x] Require branches to be up to date before merging

**Required status checks:**
- `Analyze PHP Code` (from CodeQL workflow)
- `PHP Syntax Check` (from PHP Linting workflow)
- `WordPress Coding Standards` (from PHP Linting workflow)

**Why:** Ensures code passes automated tests and security scans before merging.

#### 3. Require Conversation Resolution Before Merging
- [x] **Enable** - Require conversation resolution before merging

**Why:** Ensures all feedback and questions are addressed before code is merged.

#### 4. Require Signed Commits
- [ ] **Optional** - Require signed commits

**Why:** Adds extra security by verifying commit authenticity. Optional because it requires GPG setup.

#### 5. Require Linear History
- [x] **Enable** - Require linear history

**Why:** Prevents merge commits, keeping history clean and easier to follow.

#### 6. Include Administrators
- [x] **Enable** - Include administrators

**Why:** Even admins should follow the same rules to prevent accidental mistakes.

#### 7. Restrict Who Can Push to Matching Branches
- [ ] **Optional** - Enable if you have a team

**Who can push:**
- Repository administrators
- Specific users or teams (configure as needed)

**Why:** Limits who can push directly to main, reducing risk of unauthorized changes.

#### 8. Allow Force Pushes
- [ ] **Disable** - Do not allow force pushes

**Why:** Prevents rewriting history which can cause issues for other developers.

#### 9. Allow Deletions
- [ ] **Disable** - Do not allow deletions

**Why:** Prevents accidental deletion of the main branch.

## Protection Rules for Other Branches

### `develop` Branch (if using Git Flow)

Apply the same rules as `main` with these modifications:
- Required approvals: 1 (can be same as main or fewer)
- May allow force pushes if needed during active development
- Same status checks as main

### Feature Branches

**Branch name pattern:** `feature/*`

Lighter protection:
- [ ] No specific protection required
- [x] Delete branch after merge (optional, for cleanliness)

### Hotfix Branches

**Branch name pattern:** `hotfix/*`

Similar to feature branches but may want:
- [x] Require pull request
- [x] Require 1 approval (expedited process for critical fixes)

## Step-by-Step Configuration Guide

### 1. Navigate to Settings
1. Go to your repository on GitHub
2. Click **Settings** (requires admin access)
3. Click **Branches** in the left sidebar

### 2. Add Branch Protection Rule
1. Click **Add branch protection rule**
2. Enter branch name pattern: `main`
3. Enable the checkboxes as described above
4. Configure required status checks
5. Scroll down and click **Create** or **Save changes**

### 3. Configure Status Checks
After running workflows at least once:
1. The status checks will appear in the list
2. Select the required checks:
   - `Analyze PHP Code`
   - `PHP Syntax Check`
   - `WordPress Coding Standards`

### 4. Test the Protection
1. Try to push directly to main (should be blocked)
2. Create a test PR and verify:
   - Review is required
   - Status checks must pass
   - Conversations must be resolved

## Security Considerations

### Additional Security Settings

Navigate to **Settings → Security → Code security and analysis**:

#### Dependency Graph
- [x] **Enable** - Automatically enabled for public repositories
- Tracks dependencies in your code

#### Dependabot Alerts
- [x] **Enable** - Receive alerts about vulnerable dependencies
- Already configured via `.github/dependabot.yml`

#### Dependabot Security Updates
- [x] **Enable** - Automatic pull requests to fix vulnerable dependencies

#### Code Scanning
- [x] **Enable** - CodeQL analysis for security vulnerabilities
- Already configured via `.github/workflows/codeql.yml`

#### Secret Scanning
- [x] **Enable** - Automatically enabled for public repositories
- Detects accidentally committed secrets

#### Secret Scanning Push Protection
- [x] **Enable** - Blocks pushes that contain secrets

## Workflow Permissions

Navigate to **Settings → Actions → General → Workflow permissions**:

- [x] **Select:** Read repository contents and packages permissions
- [x] **Enable:** Allow GitHub Actions to create and approve pull requests

This allows workflows to function while maintaining security.

## Required Checks Configuration

Once workflows have run at least once, you can specify them as required:

```
Settings → Branches → Edit rule → Require status checks to pass before merging
```

Add these status checks:
1. `Analyze PHP Code` - From codeql.yml
2. `PHP Syntax Check` - From php-linting.yml  
3. `WordPress Coding Standards` - From php-linting.yml

## Rulesets (Alternative Approach)

GitHub also supports Rulesets (newer feature):

Navigate to **Settings → Rules → Rulesets → New ruleset**

Benefits of Rulesets:
- More flexible targeting
- Better permission model
- Easier to manage across multiple repositories

See [GitHub Rulesets Documentation](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-rulesets/about-rulesets)

## Verification Checklist

After configuring, verify:

- [ ] Cannot push directly to main branch
- [ ] PRs require review before merging
- [ ] Status checks must pass before merging
- [ ] Conversations must be resolved before merging
- [ ] Force pushes are blocked
- [ ] Branch cannot be deleted
- [ ] CodeQL scans run automatically
- [ ] Dependabot is active
- [ ] Secret scanning is enabled

## Maintenance

Review and update branch protection rules:
- **Quarterly**: Review if rules are still appropriate
- **After major changes**: Update required status checks
- **When adding collaborators**: Update review requirements
- **After security incidents**: Strengthen rules as needed

## Troubleshooting

### "Status check is required but not present"
- Ensure workflows have run at least once
- Check workflow names match exactly
- Verify workflows are enabled

### "Administrator bypass not working"
- Ensure "Include administrators" is NOT checked
- Or temporarily disable the rule

### "Cannot merge even though all checks pass"
- Check if conversations need resolution
- Verify review approvals are valid
- Ensure branch is up to date

## Resources

- [GitHub Branch Protection Documentation](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches)
- [GitHub Rulesets Documentation](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-rulesets)
- [Status Checks Documentation](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/collaborating-on-repositories-with-code-quality-features/about-status-checks)

## Questions?

For questions about branch protection:
- Check [GitHub Documentation](https://docs.github.com)
- Open a [Discussion](https://github.com/schoedel-learn/log-changes/discussions)
- Contact repository maintainers

---

**Last Updated:** 2025-11-09
**Maintained By:** Repository Administrators
