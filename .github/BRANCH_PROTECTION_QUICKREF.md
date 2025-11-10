# Branch Protection Quick Reference

## Summary

This repository has automated branch protection configuration to protect the `main` branch.

## Protection Rules Applied

| Rule | Status | Description |
|------|--------|-------------|
| **Force Pushes** | ❌ Disabled | Prevents rewriting git history |
| **Branch Deletion** | ❌ Disabled | Prevents accidental removal of main branch |
| **Pull Request Required** | ✅ Required | All changes must go through PR |
| **Approvals** | ✅ Required (1) | At least 1 approval needed |
| **Status Checks** | ✅ Required | All CI checks must pass |
| **Conversation Resolution** | ✅ Required | All comments must be resolved |
| **Linear History** | ✅ Enforced | No merge commits allowed |
| **Admin Enforcement** | ✅ Enabled | Admins must follow rules too |

## Required Status Checks

Before merging, these checks must pass:

1. ✅ **WordPress Coding Standards** - Ensures code follows WordPress standards
2. ✅ **PHP Compatibility Check** - Tests compatibility with PHP 7.2-8.2
3. ✅ **Markdown Lint** - Validates documentation formatting

## How to Apply Protection

### Quick (Automated)

Run this workflow from GitHub Actions:
```
Actions → Setup Branch Protection → Run workflow
```

### Command Line

```bash
# Using GitHub CLI
./.github/scripts/setup-branch-protection.sh

# Or with token
GITHUB_TOKEN=your_token ./.github/scripts/setup-branch-protection.sh
```

## Verification

After setup, test that protection is working:

```bash
# This should be rejected:
git checkout main
git push origin main  # ❌ Should fail
```

## Configuration Files

- **Config**: `.github/branch-protection-config.yml`
- **Script**: `.github/scripts/setup-branch-protection.sh`
- **Workflow**: `.github/workflows/setup-branch-protection.yml`
- **Docs**: `.github/scripts/README.md`

## Normal Workflow

With branch protection enabled:

1. Create a feature branch
2. Make your changes
3. Push to your branch
4. Open a Pull Request
5. Wait for status checks to pass
6. Get approval from a reviewer
7. Squash and merge

## Troubleshooting

**Can't push to main?**
✅ This is expected! Create a branch and PR instead.

**Status checks not appearing?**
🔄 Make sure workflows have run at least once. Create a test PR first.

**Need admin access?**
📧 Contact the repository owner.

## More Information

- Full setup guide: [BRANCH_PROTECTION_SETUP.md](../BRANCH_PROTECTION_SETUP.md)
- Detailed docs: [BRANCH_PROTECTION.md](BRANCH_PROTECTION.md)
- Script docs: [scripts/README.md](scripts/README.md)
- Contributing: [CONTRIBUTING.md](../CONTRIBUTING.md)

---

**Quick Links:**
- [Repository Settings](https://github.com/schoedel-learn/log-changes/settings/branches)
- [GitHub Actions](https://github.com/schoedel-learn/log-changes/actions)
- [Open Issues](https://github.com/schoedel-learn/log-changes/issues)
