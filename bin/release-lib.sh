#!/usr/bin/env bash
#
# Shared by bin/bump-version.sh and bin/check-version.sh.

# The main plugin file, which carries the "Version:" header.
PLUGIN_FILE=custom-post-type-permalinks.php

# Whether $1 is a release version: 1.2.3, without leading zeros. Prereleases
# (1.2.3-rc.1) are not supported.
is_version() {
	echo "$1" | grep -qE '^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$'
}

# Whether the tag $1 already exists on the origin remote.
tag_exists() {
	git ls-remote --exit-code --tags origin "refs/tags/$1" > /dev/null 2>&1
}

# Prints the lines of README.md under the changelog heading "$1" (e.g.
# "### 1.2.3"), up to the next "#" to "###" heading. Deeper subheadings
# ("#### Fixed") belong to the section.
changelog_section() {
	awk -v heading="$1" '
		$0 == heading { found = 1; next }
		found && /^(#|##|###)( |$)/ { exit }
		found { print }
	' README.md
}
