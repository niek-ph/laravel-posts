#!/bin/bash

# release.sh

# Version management script for Laravel packages
# Shows current version and provides quick access to bump commands
# Usage: ./scripts/release.sh [--dry-run]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Global dry run flag
DRY_RUN=false

# Global version variables
MAJOR=0
MINOR=0
PATCH=0
LATEST_TAG=""

# Global package name variable
PACKAGE_NAME=""

# Function to print with colors (compatible with different shells)
print_colored() {
    local color="$1"
    local message="$2"
    printf "%b%s%b\n" "$color" "$message" "$NC"
}

# Function to capitalize first letter (compatible with older bash versions)
capitalize() {
    local word="$1"
    echo "$(echo ${word:0:1} | tr '[:lower:]' '[:upper:]')${word:1}"
}

# Function to read package name from composer.json
read_package_name() {
    if [ ! -f "composer.json" ]; then
        print_colored "$RED" "❌ Error: composer.json not found"
        exit 1
    fi

    # Try to extract package name using grep and sed (more portable than jq)
    PACKAGE_NAME=$(grep '"name"' composer.json | head -1 | sed 's/.*"name"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/')

    if [ -z "$PACKAGE_NAME" ]; then
        print_colored "$RED" "❌ Error: Could not find package name in composer.json"
        exit 1
    fi

    print_colored "$BLUE" "📦 Package name: $PACKAGE_NAME"
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dry-run)
                DRY_RUN=true
                print_colored "$CYAN" "🧪 DRY RUN MODE: No changes will be made"
                echo
                shift
                ;;
            *)
                print_colored "$RED" "❌ Unknown argument: $1"
                echo "Usage: $0 [--dry-run]"
                exit 1
                ;;
        esac
    done
}

# Function to check if we're in a git repository
check_git_repository() {
    if [ ! -d .git ]; then
        print_colored "$RED" "❌ Error: Not in a git repository"
        exit 1
    fi
}

# Function to check for uncommitted changes
check_uncommitted_changes() {
    if [ -n "$(git status --porcelain)" ]; then
        if [ "$DRY_RUN" = true ]; then
            print_colored "$YELLOW" "⚠️  Warning: You have uncommitted changes (ignored in dry run):"
            git status --short
            echo
        else
            print_colored "$RED" "❌ Error: You have uncommitted changes. Please commit or stash them first."
            git status --short
            exit 1
        fi
    fi
}

# Function to check branch and optionally allow continuation
check_branch() {
    local BRANCH=$(git branch --show-current)
    if [[ "$BRANCH" != "main" && "$BRANCH" != "master" ]]; then
        print_colored "$YELLOW" "⚠️  Warning: You're not on main/master branch (currently on: $BRANCH)"
        if [ "$DRY_RUN" = false ]; then
            read -p "Continue anyway? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                print_colored "$RED" "❌ Aborted"
                exit 1
            fi
        else
            print_colored "$CYAN" "   Dry run: Would ask for confirmation to continue"
        fi
    fi
    echo "$BRANCH"
}

# Function to pull latest changes
pull_latest_changes() {
    local BRANCH=$1
    print_colored "$BLUE" "📦 Pulling latest changes..."
    if [ "$DRY_RUN" = true ]; then
        print_colored "$CYAN" "   Dry run: Would execute 'git pull origin $BRANCH'"
    else
        git pull origin "$BRANCH"
    fi
}

# Function to get and parse current version - sets global variables
parse_current_version() {
    LATEST_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "0.0.0")
    print_colored "$BLUE" "📋 Current version: $LATEST_TAG"

    # Remove 'v' prefix if present for parsing
    local VERSION_WITHOUT_V=${LATEST_TAG#v}

    if [[ $VERSION_WITHOUT_V =~ ^([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]; then
        MAJOR=${BASH_REMATCH[1]}
        MINOR=${BASH_REMATCH[2]}
        PATCH=${BASH_REMATCH[3]}

        # Debug output to verify parsing
        if [ "$DRY_RUN" = true ]; then
            print_colored "$CYAN" "   Debug: Parsed version - Major: $MAJOR, Minor: $MINOR, Patch: $PATCH"
        fi
    else
        print_colored "$RED" "❌ Error: Invalid version format in tag $LATEST_TAG"
        exit 1
    fi
}

# Function to run quality checks
run_quality_checks() {
    if [ "$DRY_RUN" = true ]; then
        print_colored "$CYAN" "   Dry run: Skipping quality checks"
        return
    fi

    # Run tests
    print_colored "$BLUE" "🧪 Running tests..."
    if command -v vendor/bin/pest &> /dev/null; then
        vendor/bin/pest --stop-on-failure
    else
        print_colored "$YELLOW" "⚠️  No pest found, skipping tests"
    fi

    # Run code analysis
    print_colored "$BLUE" "🔍 Running code analysis..."
    if command -v vendor/bin/phpstan &> /dev/null; then
        vendor/bin/phpstan analyse --memory-limit=1024M --no-progress
    else
        print_colored "$YELLOW" "⚠️  No phpstan found, skipping analysis"
    fi

    # Check code formatting
    print_colored "$BLUE" "✨ Checking code formatting..."
    if command -v vendor/bin/pint &> /dev/null; then
        vendor/bin/pint --test
    else
        print_colored "$YELLOW" "⚠️  No pint found, skipping formatting check"
    fi
}

# Function to create and push tag
create_and_push_tag() {
    local NEW_TAG=$1
    local NEW_VERSION=$2
    local COMMIT_MESSAGE=$3

    if [ "$DRY_RUN" = true ]; then
        print_colored "$CYAN" "   Dry run: Would create tag $NEW_TAG with message:"
        print_colored "$CYAN" "   '$COMMIT_MESSAGE'"
        print_colored "$CYAN" "   Dry run: Would push tag to origin"
    else
        print_colored "$BLUE" "🏷️  Creating tag $NEW_TAG..."
        git tag -a "$NEW_TAG" -m "$COMMIT_MESSAGE"

        print_colored "$BLUE" "📤 Pushing tag to origin..."
        git push origin "$NEW_TAG"
    fi
}

# Function to show success message
show_success_message() {
    local NEW_VERSION=$1
    local RELEASE_TYPE=$2
    local CAPITALIZED_TYPE=$(capitalize "$RELEASE_TYPE")

    echo
    if [ "$DRY_RUN" = true ]; then
        print_colored "$CYAN" "🧪 Dry run complete for package $PACKAGE_NAME! $CAPITALIZED_TYPE version would be bumped to $NEW_VERSION"
        print_colored "$CYAN" "   No actual changes were made"
    else
        print_colored "$GREEN" "✅ Success! $CAPITALIZED_TYPE version bumped to $NEW_VERSION"
        if [[ "$RELEASE_TYPE" == "major" ]]; then
            print_colored "$RED" "💥 This is a BREAKING CHANGE release!"
        fi
        print_colored "$BLUE" "🤖 GitHub will now automatically:"
        echo "   • Create a GitHub release"
        echo "   • Update Packagist (if configured)"
        echo "   • Run release workflows"
        echo
        printf "%b📦 Install with: %bcomposer require %s:^%s%b\n" "$BLUE" "$GREEN" "$PACKAGE_NAME" "$NEW_VERSION" "$NC"
        if [[ "$RELEASE_TYPE" == "major" ]]; then
            print_colored "$YELLOW" "⚠️  Users will need to update their composer constraints for this major version"
        fi
    fi
}

# Function to create patch version
create_patch_version() {
    print_colored "$BLUE" "🔧 Starting patch version bump..."

    check_git_repository
    check_uncommitted_changes
    BRANCH=$(check_branch)
    pull_latest_changes "$BRANCH"
    parse_current_version

    # Increment patch version
    NEW_PATCH=$((PATCH + 1))
    NEW_VERSION="$MAJOR.$MINOR.$NEW_PATCH"
    NEW_TAG="v$NEW_VERSION"

    print_colored "$GREEN" "🆕 New version: $NEW_VERSION"

    run_quality_checks

    # Ask for confirmation
    echo
    print_colored "$YELLOW" "📋 Summary:"
    printf "  Current version: %b%s%b\n" "$BLUE" "$LATEST_TAG" "$NC"
    printf "  New version:     %b%s%b\n" "$GREEN" "$NEW_TAG" "$NC"
    printf "  Branch:          %b%s%b\n" "$BLUE" "$BRANCH" "$NC"
    if [ "$DRY_RUN" = true ]; then
        printf "  Mode:            %bDRY RUN%b\n" "$CYAN" "$NC"
    fi
    echo

    if [ "$DRY_RUN" = false ]; then
        read -p "Create tag and push? (y/N): " -n 1 -r
        echo

        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_colored "$RED" "❌ Aborted"
            exit 1
        fi
    else
        print_colored "$CYAN" "   Dry run: Would ask for confirmation"
    fi

    COMMIT_MESSAGE="Release $NEW_VERSION

🔧 Patch release with bug fixes and minor improvements"

    create_and_push_tag "$NEW_TAG" "$NEW_VERSION" "$COMMIT_MESSAGE"
    show_success_message "$NEW_VERSION" "patch"
}

# Function to create minor version
create_minor_version() {
    print_colored "$BLUE" "✨ Starting minor version bump..."

    check_git_repository
    check_uncommitted_changes
    BRANCH=$(check_branch)
    pull_latest_changes "$BRANCH"
    parse_current_version

    # Increment minor version and reset patch to 0
    NEW_MINOR=$((MINOR + 1))
    NEW_VERSION="$MAJOR.$NEW_MINOR.0"
    NEW_TAG="v$NEW_VERSION"

    print_colored "$GREEN" "🆕 New version: $NEW_VERSION"

    run_quality_checks

    # Ask for confirmation
    echo
    print_colored "$YELLOW" "📋 Summary:"
    printf "  Current version: %b%s%b\n" "$BLUE" "$LATEST_TAG" "$NC"
    printf "  New version:     %b%s%b\n" "$GREEN" "$NEW_TAG" "$NC"
    printf "  Branch:          %b%s%b\n" "$BLUE" "$BRANCH" "$NC"
    if [ "$DRY_RUN" = true ]; then
        printf "  Mode:            %bDRY RUN%b\n" "$CYAN" "$NC"
    fi
    echo

    if [ "$DRY_RUN" = false ]; then
        read -p "Create tag and push? (y/N): " -n 1 -r
        echo

        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_colored "$RED" "❌ Aborted"
            exit 1
        fi
    else
        print_colored "$CYAN" "   Dry run: Would ask for confirmation"
    fi

    COMMIT_MESSAGE="Release $NEW_VERSION

✨ Minor release with new features and improvements"

    create_and_push_tag "$NEW_TAG" "$NEW_VERSION" "$COMMIT_MESSAGE"
    show_success_message "$NEW_VERSION" "minor"
}

# Function to create major version
create_major_version() {
    print_colored "$BLUE" "💥 Starting major version bump..."
    print_colored "$YELLOW" "⚠️  This is a MAJOR version bump - indicates breaking changes!"

    check_git_repository
    check_uncommitted_changes
    BRANCH=$(check_branch)
    pull_latest_changes "$BRANCH"
    parse_current_version

    # Increment major version and reset minor and patch to 0
    NEW_MAJOR=$((MAJOR + 1))
    NEW_VERSION="$NEW_MAJOR.0.0"
    NEW_TAG="v$NEW_VERSION"

    print_colored "$GREEN" "🆕 New version: $NEW_VERSION"
    echo
    print_colored "$RED" "💥 BREAKING CHANGE WARNING:"
    print_colored "$YELLOW" "   Major version bumps indicate breaking changes!"
    print_colored "$YELLOW" "   Make sure you've updated:"
    print_colored "$YELLOW" "   • README.md with upgrade instructions"
    print_colored "$YELLOW" "   • CHANGELOG.md with breaking changes"
    print_colored "$YELLOW" "   • Documentation"
    echo

    # Extra confirmation for major version
    if [ "$DRY_RUN" = false ]; then
        read -p "Are you sure this contains breaking changes? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_colored "$RED" "❌ Aborted"
            exit 1
        fi
    else
        print_colored "$CYAN" "   Dry run: Would ask for breaking changes confirmation"
    fi

    run_quality_checks

    # Ask for final confirmation
    echo
    print_colored "$YELLOW" "📋 Summary:"
    printf "  Current version: %b%s%b\n" "$BLUE" "$LATEST_TAG" "$NC"
    printf "  New version:     %b%s%b %b(BREAKING CHANGES)%b\n" "$GREEN" "$NEW_TAG" "$NC" "$RED" "$NC"
    printf "  Branch:          %b%s%b\n" "$BLUE" "$BRANCH" "$NC"
    if [ "$DRY_RUN" = true ]; then
        printf "  Mode:            %bDRY RUN%b\n" "$CYAN" "$NC"
    fi
    echo

    if [ "$DRY_RUN" = false ]; then
        read -p "Create tag and push? (y/N): " -n 1 -r
        echo

        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_colored "$RED" "❌ Aborted"
            exit 1
        fi
    else
        print_colored "$CYAN" "   Dry run: Would ask for final confirmation"
    fi

    COMMIT_MESSAGE="Release $NEW_VERSION

💥 BREAKING: Major release with breaking changes
See CHANGELOG.md for upgrade instructions"

    create_and_push_tag "$NEW_TAG" "$NEW_VERSION" "$COMMIT_MESSAGE"
    show_success_message "$NEW_VERSION" "major"
}

# Parse command line arguments first
parse_arguments "$@"

# Main script execution
print_colored "$BLUE" "🚀 Laravel Package Version Manager"
echo

check_git_repository

# Read package name from composer.json
read_package_name

# Get current version for display
CURRENT_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "No tags found")
BRANCH=$(git branch --show-current)

print_colored "$BLUE" "📊 Current Information:"
printf "   Version: %b%s%b\n" "$GREEN" "$CURRENT_TAG" "$NC"
printf "   Branch:  %b%s%b\n" "$BLUE" "$BRANCH" "$NC"
printf "   Repo:    %b%s%b\n" "$YELLOW" "$(git config --get remote.origin.url)" "$NC"
if [ "$DRY_RUN" = true ]; then
    printf "   Mode:    %bDRY RUN%b\n" "$CYAN" "$NC"
fi
echo

# Check for uncommitted changes
if [ -n "$(git status --porcelain)" ]; then
    print_colored "$YELLOW" "⚠️  You have uncommitted changes:"
    git status --short
    echo
fi

# Show recent tags
print_colored "$BLUE" "📅 Recent versions:"
git tag --sort=-version:refname | head -5 | while read tag; do
    if [ ! -z "$tag" ]; then
        DATE=$(git log -1 --format=%ai "$tag" 2>/dev/null | cut -d' ' -f1)
        printf "   %b%s%b (%b%s%b)\n" "$GREEN" "$tag" "$NC" "$YELLOW" "$DATE" "$NC"
    fi
done

echo
print_colored "$BLUE" "🎯 What would you like to do?"
printf "   %b1)%b Patch version (bug fixes)         - x.y.Z\n" "$GREEN" "$NC"
printf "   %b2)%b Minor version (new features)      - x.Y.0\n" "$GREEN" "$NC"
printf "   %b3)%b Major version (breaking changes)  - X.0.0\n" "$GREEN" "$NC"
printf "   %b4)%b Show git log\n" "$GREEN" "$NC"
printf "   %b5)%b Exit\n" "$GREEN" "$NC"
echo

read -p "Choose an option (1-5): " -n 1 -r
echo

case $REPLY in
    1)
        print_colored "$BLUE" "🔧 Starting patch version bump..."
        create_patch_version
        ;;
    2)
        print_colored "$BLUE" "✨ Starting minor version bump..."
        create_minor_version
        ;;
    3)
        print_colored "$BLUE" "💥 Starting major version bump..."
        create_major_version
        ;;
    4)
        print_colored "$BLUE" "📜 Recent commits:"
        git log --oneline -10
        ;;
    5)
        print_colored "$GREEN" "👋 Goodbye!"
        ;;
    *)
        print_colored "$RED" "❌ Invalid option"
        exit 1
        ;;
esac
