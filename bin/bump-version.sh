#!/usr/bin/env bash

set -e

if [ $# -lt 1 ]; then
	echo "usage: $0 <version>"
	exit 1
fi

version=$1

# Stable tag should only track stable releases, not prereleases.
if ! echo "${version}" | grep -qE "alpha|beta|RC|rc"; then
	# Preserve the existing alignment, only replace the value.
	sed -i.bak -E "s/^(Stable tag: *).*/\1${version}/" README.md
	rm README.md.bak
fi

# Turn the pending "Unreleased" changelog entry into the released version heading.
sed -i.bak -e "s/^### Unreleased$/### ${version}/" README.md
rm README.md.bak

sed -i.bak -E \
	-e "s/^( \* Version: *).*/\1${version}/" \
	-e "s/^( \* @version *).*/\1${version}/" \
	custom-post-type-permalinks.php
rm custom-post-type-permalinks.php.bak

# Update package.json and package-lock.json without creating a git commit or tag.
npm version "${version}" --no-git-tag-version --allow-same-version
