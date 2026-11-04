#!/usr/bin/env bash

set -e

if [ $# -ne 1 ]; then
	echo "usage: $0 <version>" >&2
	exit 1
fi

version=$1

# Work from the repository root, wherever the script is run from.
cd "$(dirname "$0")/.."
# shellcheck source=bin/release-lib.sh
source bin/release-lib.sh

# Everything is checked before any file is changed, so a failed run can simply be repeated.
if ! is_version "${version}"; then
	echo "${version} is not a release version such as 1.2.3. Prereleases are not supported." >&2
	exit 1
fi

if grep -qx "### Unreleased" README.md; then
	if grep -qxF "### ${version}" README.md; then
		echo "README.md has both \"### Unreleased\" and \"### ${version}\". Move the Unreleased entries under ### ${version}, or release them as a new version." >&2
		exit 1
	fi
	if ! changelog_section "### Unreleased" | grep -q '[^[:space:]]'; then
		echo "The \"### Unreleased\" section in README.md has no entries. Add the changes in ${version} first." >&2
		exit 1
	fi
elif ! grep -qxF "### ${version}" README.md; then
	echo "README.md has no \"### Unreleased\" changelog heading. Add the entries for ${version} under it first." >&2
	exit 1
fi

# Update package.json and package-lock.json without creating a git commit or tag.
# It runs first, since it is the step that can fail; nothing else has changed yet.
npm version "${version}" --no-git-tag-version --allow-same-version

# Preserve the existing header alignment, only replace the value.
sed -i.bak -E \
	-e "s/^( \* Version: *).*/\1${version}/" \
	-e "s/^( \* @version *).*/\1${version}/" \
	"${PLUGIN_FILE}"
rm "${PLUGIN_FILE}.bak"

# Only replace the value, keeping the alignment and any trailing markdown line break.
sed -i.bak -E "s/^(Stable tag: *)[^[:space:]]*/\1${version}/" README.md
rm README.md.bak

# Turn the pending "Unreleased" changelog entry into the released version heading.
sed -i.bak -e "s/^### Unreleased$/### ${version}/" README.md
rm README.md.bak
