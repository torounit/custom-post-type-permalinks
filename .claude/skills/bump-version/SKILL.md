---
name: bump-version
description: Use when the user wants to bump the plugin version or prepare a release (e.g. "bump version", "/bump-version").
---

# Bump Version Skill

Version bumps go through a pull request, because `main` only accepts changes via PRs.

- `bin/bump-version.sh <version>` updates `custom-post-type-permalinks.php`, `README.md`,
  `package.json`, and `package-lock.json`.
- When a PR from a `release/*` branch is merged, CI (`.github/workflows/draft-release.yml`) creates a
  draft GitHub release targeting the merge commit, with the changelog section as its notes.
- Publishing the draft in the GitHub web UI creates the tag on that merge commit and triggers
  `.github/workflows/release.yml`, which runs lint and tests, deploys to WordPress.org, and attaches
  the plugin zip to the release.

## Steps

1. **Make sure the changelog is ready**

   Check that `README.md` has unreleased changes recorded under the `### Unreleased` heading in the
   `## Changelog` section. If recent merged PRs aren't reflected yet, add entries there first,
   creating the `### Unreleased` heading above the latest version if it doesn't exist:

   ```bash
   gh pr list --state merged --limit 10 --repo torounit/custom-post-type-permalinks
   ```

   Entry format:

   ```markdown
   ### Unreleased

   - Change description 1
   - Change description 2
   ```

2. **Create a release branch from the latest `main`**

   Choose the version following [Semantic Versioning](https://semver.org/).

   ```bash
   git switch main && git pull --ff-only
   git switch -c release/<version>
   ```

3. **Bump the version**

   ```bash
   bash bin/bump-version.sh <version>
   ```

   This updates:
   - `custom-post-type-permalinks.php`: the `Version:` and `@version` headers
   - `README.md`: `Stable tag:` (stable releases only), and the `### Unreleased` heading becomes
     `### <version>`
   - `package.json` and `package-lock.json`: the `version` field

   Review the diff before committing.

4. **Commit, push, and open a PR**

   ```bash
   git commit -am "Release <version>"
   git push -u origin release/<version>
   gh pr create --base main --title "Release <version>"
   ```

5. **Publish the draft release**

   After the PR is merged, CI creates a draft release `<version>`. Ask the user to review and publish
   it in the GitHub web UI (https://github.com/torounit/custom-post-type-permalinks/releases).
   Publishing it tags the merge commit on `main` and deploys to WordPress.org.

## Notes

- Write Changelog entries in English, based on PR titles and descriptions.
- Do not edit version numbers by hand — use `bin/bump-version.sh`, so the files don't drift apart.
