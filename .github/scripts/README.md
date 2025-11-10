# Branch Protection Setup Scripts

This directory contains scripts to automate the setup of branch protection rules for this repository.

## Overview

Branch protection rules help maintain code quality and prevent accidental changes by:
- Blocking direct pushes to protected branches
- Preventing force pushes (history rewriting)
- Preventing branch deletion
- Requiring pull request reviews before merging
- Requiring status checks to pass before merging

## Configuration

The branch protection rules are defined in `.github/branch-protection-config.yml`. This configuration file specifies:

- **Branch to protect**: `main`
- **Force pushes**: Disabled ❌
- **Branch deletion**: Disabled ❌
- **Pull request required**: Yes ✅
- **Required approvals**: 1
- **Required status checks**:
  - WordPress Coding Standards
  - PHP Compatibility Check
  - Markdown Lint
- **Linear history**: Required (prevents merge commits)
- **Admin enforcement**: Yes (even admins must follow rules)
- **Conversation resolution**: Required

## Usage

There are three ways to apply these branch protection rules:

### Option 1: Using the GitHub Actions Workflow (Recommended)

This is the easiest method if you have admin access to the repository through GitHub's web interface.

1. Go to the repository on GitHub
2. Navigate to **Actions** tab
3. Select **Setup Branch Protection** workflow
4. Click **Run workflow**
5. Enter the branch name (default: `main`)
6. Click **Run workflow** button

The workflow will apply the protection rules automatically.

### Option 2: Using the Shell Script (Local)

If you prefer running the setup from your local machine:

#### Prerequisites

Either:
- **GitHub CLI (gh)** installed and authenticated, OR
- A **GitHub Personal Access Token** with `repo` scope and admin permissions

#### Steps

1. Navigate to the repository root:
   ```bash
   cd /path/to/log-changes
   ```

2. Run the script:
   
   **Using GitHub CLI:**
   ```bash
   ./.github/scripts/setup-branch-protection.sh
   ```
   
   **Using a Personal Access Token:**
   ```bash
   GITHUB_TOKEN=ghp_your_token_here ./.github/scripts/setup-branch-protection.sh
   ```

3. Follow the output to verify the protection rules were applied successfully.

#### Creating a Personal Access Token

1. Go to https://github.com/settings/tokens
2. Click **Generate new token** → **Generate new token (classic)**
3. Give it a descriptive name (e.g., "Branch Protection Setup")
4. Select scopes:
   - ✅ `repo` (Full control of private repositories)
5. Click **Generate token**
6. Copy the token (you won't be able to see it again)

### Option 3: Manual Setup via GitHub UI

If you prefer to set up protection manually or don't have API access:

1. Go to **Settings** → **Branches**
2. Click **Add rule** under "Branch protection rules"
3. Enter `main` as the branch name pattern
4. Configure settings as specified in `.github/branch-protection-config.yml`
5. Save changes

See `BRANCH_PROTECTION.md` and `SETUP_INSTRUCTIONS.md` for detailed step-by-step instructions.

## Verification

After applying the protection rules, verify they're working:

### 1. Check in GitHub UI

Visit: `https://github.com/schoedel-learn/log-changes/settings/branches`

You should see a protection rule for `main` with all the configured restrictions.

### 2. Test Protection

Try pushing directly to main (should fail):

```bash
git checkout main
echo "test" >> test.txt
git add test.txt
git commit -m "Test direct push"
git push origin main
```

**Expected result**: Push should be rejected with a message about branch protection.

```bash
# Clean up
git reset --hard HEAD~1
rm test.txt
```

### 3. Verify Status Checks

1. Create a new branch
2. Make a change
3. Open a pull request
4. Status checks should run automatically
5. Merge button should be disabled until all checks pass and approval is received

## Troubleshooting

### Error: "Not Found"

**Cause**: The branch doesn't exist yet.

**Solution**: Ensure the `main` branch exists in the repository before applying protection.

### Error: "Bad credentials"

**Cause**: GitHub token is invalid or expired.

**Solution**: 
- If using GitHub CLI: Run `gh auth login`
- If using token: Generate a new token with `repo` scope

### Error: "Resource not accessible by integration"

**Cause**: Insufficient permissions.

**Solution**: 
- Ensure you have admin access to the repository
- If using a token, verify it has `repo` scope
- Check that the token belongs to a user with admin rights

### Status Checks Not Listed

**Cause**: Status checks must run at least once before they can be made required.

**Solution**:
1. Create a pull request to trigger the workflows
2. Wait for all workflows to complete
3. Re-run the protection setup script
4. The status checks should now be available

### Protection Not Enforced

**Cause**: "Include administrators" might be unchecked.

**Solution**: The script sets `enforce_admins: true`. If it's not working, check the GitHub UI settings and ensure "Include administrators" is checked.

## Maintenance

### Updating Protection Rules

1. Edit `.github/branch-protection-config.yml` with your changes
2. Re-run the setup script or workflow to apply the updated rules

### Adding New Status Checks

When you add new GitHub Actions workflows:

1. Update `.github/branch-protection-config.yml`:
   ```yaml
   contexts:
     - "WordPress Coding Standards"
     - "PHP Compatibility Check"
     - "Markdown Lint"
     - "Your New Check Name"  # Add here
   ```

2. Update `.github/scripts/setup-branch-protection.sh`:
   - Add the new check name to the `PROTECTION_PAYLOAD`

3. Update `.github/workflows/setup-branch-protection.yml`:
   - Add the new check name to the workflow payload

4. Re-run the setup

### Protecting Additional Branches

To protect other branches (e.g., `develop`):

1. Modify the script or workflow to use a different branch name
2. Run the setup
3. Or use the GitHub Actions workflow and specify the branch name as input

## Security Considerations

- **Never commit GitHub tokens** to the repository
- Use environment variables or GitHub CLI for authentication
- Tokens should have minimal required permissions (`repo` scope only)
- Rotate tokens regularly
- Delete tokens after one-time use if possible

## Related Documentation

- [Branch Protection Setup Guide](../BRANCH_PROTECTION.md)
- [Complete Setup Instructions](../SETUP_INSTRUCTIONS.md)
- [Contributing Guidelines](../../CONTRIBUTING.md)
- [Quick Setup Guide](../../BRANCH_PROTECTION_SETUP.md)

## Support

If you encounter issues:
1. Check the troubleshooting section above
2. Review the GitHub API documentation: https://docs.github.com/en/rest/branches/branch-protection
3. Open an issue with the `question` label

---

**Note**: These scripts require repository admin permissions to run successfully. If you don't have admin access, contact the repository owner.
