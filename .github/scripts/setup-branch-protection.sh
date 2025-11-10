#!/bin/bash

# Branch Protection Setup Script
# This script applies branch protection rules to the main branch using the GitHub API
# 
# Prerequisites:
# - GitHub CLI (gh) installed and authenticated, OR
# - GitHub Personal Access Token with repo permissions
#
# Usage:
#   ./setup-branch-protection.sh
#   or with token:
#   GITHUB_TOKEN=your_token_here ./setup-branch-protection.sh

set -e

# Repository details
REPO_OWNER="schoedel-learn"
REPO_NAME="log-changes"
BRANCH="main"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if GitHub CLI is available
if command -v gh &> /dev/null; then
    print_info "Using GitHub CLI (gh) for authentication"
    USE_GH_CLI=true
    # Verify authentication
    if ! gh auth status &> /dev/null; then
        print_error "GitHub CLI is not authenticated. Run: gh auth login"
        exit 1
    fi
    print_success "GitHub CLI is authenticated"
else
    USE_GH_CLI=false
    print_info "GitHub CLI not found, checking for GITHUB_TOKEN"
    
    # Check for token
    if [ -z "$GITHUB_TOKEN" ]; then
        print_error "GITHUB_TOKEN environment variable is not set"
        print_info "Please set GITHUB_TOKEN with a token that has 'repo' permissions"
        print_info "Create a token at: https://github.com/settings/tokens"
        exit 1
    fi
    print_success "GITHUB_TOKEN is set"
fi

print_info "Configuring branch protection for ${REPO_OWNER}/${REPO_NAME}:${BRANCH}"

# Branch protection payload
PROTECTION_PAYLOAD='{
  "required_status_checks": {
    "strict": true,
    "contexts": [
      "WordPress Coding Standards",
      "PHP Compatibility Check",
      "Markdown Lint"
    ]
  },
  "enforce_admins": true,
  "required_pull_request_reviews": {
    "dismiss_stale_reviews": true,
    "require_code_owner_reviews": false,
    "required_approving_review_count": 1,
    "require_last_push_approval": true
  },
  "restrictions": null,
  "required_linear_history": true,
  "allow_force_pushes": false,
  "allow_deletions": false,
  "required_conversation_resolution": true,
  "block_creations": false,
  "lock_branch": false
}'

# Make API call
print_info "Applying branch protection rules..."

if [ "$USE_GH_CLI" = true ]; then
    # Using GitHub CLI
    RESPONSE=$(gh api \
        --method PUT \
        -H "Accept: application/vnd.github+json" \
        -H "X-GitHub-Api-Version: 2022-11-28" \
        "/repos/${REPO_OWNER}/${REPO_NAME}/branches/${BRANCH}/protection" \
        --input - <<< "$PROTECTION_PAYLOAD" 2>&1)
else
    # Using curl with token
    RESPONSE=$(curl -s -X PUT \
        -H "Accept: application/vnd.github+json" \
        -H "Authorization: Bearer ${GITHUB_TOKEN}" \
        -H "X-GitHub-Api-Version: 2022-11-28" \
        "https://api.github.com/repos/${REPO_OWNER}/${REPO_NAME}/branches/${BRANCH}/protection" \
        -d "$PROTECTION_PAYLOAD" 2>&1)
fi

# Check if successful
if echo "$RESPONSE" | grep -q '"url"'; then
    print_success "Branch protection rules applied successfully!"
    echo ""
    print_info "Protection rules summary:"
    echo "  • Force pushes: DISABLED ✗"
    echo "  • Branch deletion: DISABLED ✗"
    echo "  • Pull request required: YES ✓"
    echo "  • Approvals required: 1"
    echo "  • Status checks required: YES ✓"
    echo "    - WordPress Coding Standards"
    echo "    - PHP Compatibility Check"
    echo "    - Markdown Lint"
    echo "  • Linear history: YES ✓"
    echo "  • Admin enforcement: YES ✓"
    echo ""
    print_success "The main branch is now protected!"
elif echo "$RESPONSE" | grep -q "Not Found"; then
    print_error "Branch '${BRANCH}' not found in repository"
    print_info "Please ensure the branch exists before setting up protection"
    exit 1
elif echo "$RESPONSE" | grep -q "Bad credentials"; then
    print_error "Authentication failed"
    print_info "Please check your GitHub token or CLI authentication"
    exit 1
elif echo "$RESPONSE" | grep -q "Resource not accessible by integration"; then
    print_error "Insufficient permissions"
    print_info "The token needs 'repo' scope with admin permissions"
    exit 1
else
    print_error "Failed to apply branch protection rules"
    print_warning "Response from GitHub API:"
    echo "$RESPONSE"
    exit 1
fi

print_info "Verification steps:"
echo "  1. Visit: https://github.com/${REPO_OWNER}/${REPO_NAME}/settings/branches"
echo "  2. Check that protection rules are active for '${BRANCH}'"
echo "  3. Try pushing directly to ${BRANCH} (should be blocked)"
echo ""
print_success "Setup complete!"
