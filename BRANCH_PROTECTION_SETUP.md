# Quick Branch Protection Setup

This repository includes best practices for WordPress plugin development, including branch protection rules.

## 🤖 Automated Setup (Recommended - 2 Minutes)

### Option A: Using GitHub Actions Workflow

1. Go to **Actions** → **Setup Branch Protection**
2. Click **Run workflow**
3. Enter branch name: `main`
4. Click **Run workflow** button

✅ Done! The workflow will apply all protection rules automatically.

### Option B: Using Local Script

```bash
# Using GitHub CLI (recommended)
./.github/scripts/setup-branch-protection.sh

# Or with a personal access token
GITHUB_TOKEN=your_token ./.github/scripts/setup-branch-protection.sh
```

See [.github/scripts/README.md](.github/scripts/README.md) for detailed instructions.

## 📋 What Gets Protected

The automated setup configures:
- ❌ **Force pushes**: Disabled (prevents history rewriting)
- ❌ **Branch deletion**: Disabled (prevents accidental removal)
- ✅ **Pull request required**: 1 approval needed
- ✅ **Status checks required**:
  - WordPress Coding Standards
  - PHP Compatibility Check
  - Markdown Lint
- ✅ **Conversation resolution**: Required before merge
- ✅ **Linear history**: No merge commits allowed
- ✅ **Admin enforcement**: Even admins must follow rules

## 🔧 Manual Setup (Alternative)

### 1. Enable Branch Protection

Go to: **Settings** → **Branches** → **Add rule**

**Branch name pattern:** `main`

**Enable these settings:**
- ✅ Require pull request before merging (1 approval)
- ✅ Require status checks to pass before merging
- ✅ Require conversation resolution before merging
- ✅ Require linear history
- ❌ Allow force pushes (disabled)
- ❌ Allow deletions (disabled)

**Save changes**

### 2. Enable GitHub Actions

Go to: **Actions** → **General** → Enable workflows

### 3. Make First PR to Trigger Workflows

Create a test branch and PR to run the automated checks for the first time.

### 4. Configure Required Status Checks

After workflows run once:
- Go back to **Settings** → **Branches** → Edit rule
- Under "Status checks", add:
  - `WordPress Coding Standards`
  - `PHP Compatibility Check`
  - `Markdown Lint`

**Save changes**

## ✅ That's It!

Your main branch is now protected. All changes must:
- Go through pull requests
- Pass automated quality checks
- Receive code review approval
- Resolve all conversations

## 📚 Detailed Documentation

For comprehensive setup instructions, see:
- [.github/SETUP_INSTRUCTIONS.md](.github/SETUP_INSTRUCTIONS.md) - Complete setup guide
- [.github/BRANCH_PROTECTION.md](.github/BRANCH_PROTECTION.md) - Branch protection details

## 🔧 Local Development Setup

```bash
# Install dependencies
composer install

# Check code quality
composer run-script phpcs

# Fix issues automatically
composer run-script phpcbf
```

## 📝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for how to contribute to this project.

## 🛡️ Why Branch Protection?

Branch protection ensures:
- **Quality:** Code is reviewed before merging
- **Security:** Prevents unauthorized changes
- **Standards:** Automated checks enforce coding standards
- **History:** Clean, linear git history
- **Collaboration:** Structured review process

## 🆘 Need Help?

- Read [CONTRIBUTING.md](CONTRIBUTING.md)
- Check [.github/SETUP_INSTRUCTIONS.md](.github/SETUP_INSTRUCTIONS.md)
- Review [.github/scripts/README.md](.github/scripts/README.md) for automation details
- Open an issue with the `question` label

## ⚙️ Configuration Files

Branch protection rules are defined in:
- **[.github/branch-protection-config.yml](.github/branch-protection-config.yml)** - YAML configuration
- **[.github/scripts/setup-branch-protection.sh](.github/scripts/setup-branch-protection.sh)** - Setup script
- **[.github/workflows/setup-branch-protection.yml](.github/workflows/setup-branch-protection.yml)** - GitHub Actions workflow

These files are version-controlled and can be updated as needed.

---

**Repository Owner:** After setting up branch protection, you can delete or archive this file.
