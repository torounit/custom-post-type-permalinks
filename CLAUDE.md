# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Custom Post Type Permalinks** is a WordPress plugin that allows editing the permalink structure of custom post types. It is listed in the official WordPress.org plugin directory.

## Commands

### Testing

WordPress test environment setup is required before running tests:

```bash
bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
# Example: bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 trunk
```

```bash
# Run tests
composer test

# Run tests in multisite mode
WP_MULTISITE=1 composer test
```

### Lint / Code Quality

```bash
# PHPCS check (WordPress Coding Standards)
composer phpcs

# Auto-format
composer format
```

## Architecture

### Design Pattern

The plugin uses a **singleton + modular architecture**:

- `custom-post-type-permalinks.php` — Plugin entry point. Defines constants and class autoloader.
- `CPTP.php` — Singleton main class. Initializes modules sequentially via `CPTP::get_instance()->init()`.
- `CPTP/Module.php` — Abstract base class for all modules.
- `CPTP/Util.php` — Utility functions for retrieving post types, checking rewrite support, etc.

### Modules (`CPTP/Module/`)

| Module        | Responsibility                                               |
| ------------- | ------------------------------------------------------------ |
| `Setting`     | Text domain loading, version management                      |
| `Rewrite`     | Registers rewrite rules for custom post types and taxonomies |
| `Admin`       | Settings UI (Settings → Permalinks)                          |
| `Option`      | Saves permalink structure options                            |
| `Permalink`   | Generates permalinks for posts, terms, and archives          |
| `GetArchives` | Supports `wp_get_archives()`                                 |
| `FlushRules`  | Handles rewrite rule flushing                                |

To add or modify modules, use the `CPTP_load_modules` / `cptp_load_modules` hooks.

### Backward Compatibility Policy

This plugin directly controls the URL structure of WordPress sites. **A plugin update must never cause existing published URLs to stop resolving.** Breaking a URL means real visitors get 404 errors, which damages SEO and user experience in a way that is hard to recover from.

Concretely:
- Do not change the default permalink structure in a way that alters already-saved URLs.
- Do not remove or rename rewrite rules that active sites may depend on.
- Do not change how saved option values are interpreted without a migration path.
- When a behavior change is unavoidable, provide a deprecation path and document it clearly.

### Coding Standards

- Prefix: `CPTP`
- Text domain: `custom-post-type-permalinks`
- WordPress Coding Standards (including Yoda conditions)
- PHP 8.0+

### Tests

PHPUnit tests under `tests/`. `tests/bootstrap.php` initializes the WordPress test environment and Yoast PHPUnit Polyfills.

### CI/CD

`.github/workflows/test-and-release.yml` runs tests across PHP 8.0/8.4 and WP trunk/6.9/6.7. Automatically deploys to WordPress.org on tag creation.
