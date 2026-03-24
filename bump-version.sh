#!/bin/bash
# Usage: ./bump-version.sh 1.2.0
# Bumps the version in both wooseo-optimizer.php header and WSEO_VERSION constant

if [ -z "$1" ]; then
    echo "Usage: ./bump-version.sh <new-version>"
    echo "Example: ./bump-version.sh 1.2.0"
    exit 1
fi

NEW_VERSION="$1"
FILE="wooseo-optimizer.php"

if [ ! -f "$FILE" ]; then
    echo "Error: $FILE not found. Run this script from the plugin root directory."
    exit 1
fi

# Update plugin header version
sed -i "s/^ \* Version:.*/ * Version:           $NEW_VERSION/" "$FILE"

# Update WSEO_VERSION constant
sed -i "s/define( 'WSEO_VERSION', '.*' );/define( 'WSEO_VERSION', '$NEW_VERSION' );/" "$FILE"

echo "Version bumped to $NEW_VERSION"
echo "Updated: Plugin header + WSEO_VERSION constant"
echo ""
echo "Next steps:"
echo "  git add -A"
echo "  git commit -m \"v$NEW_VERSION - your changes here\""
echo "  git tag v$NEW_VERSION"
echo "  git push origin main --tags"
