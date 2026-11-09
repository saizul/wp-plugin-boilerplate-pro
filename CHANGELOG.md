# Changelog

All notable changes are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- `wp-plugin-boilerplate-pro/items` Gutenberg block, registered from `blocks/items/block.json`. It is dynamic (server-rendered, no build step) and shares its list markup with the `[wppbp_items]` shortcode.
- `Support\ItemsArgs`, a pure normaliser used by both the shortcode and the block, with unit tests.

## [1.0.0] - 2026-11-08

### Added
- Main plugin file with headers, PHP version guard, Composer autoloader and a fallback PSR-4 autoloader.
- Dependency injection container with shared and factory bindings, auto-wiring and circular dependency detection.
- Service providers for i18n, settings, post types, REST, shortcodes, cron and WP-CLI.
- Settings page built on the Settings API, with a schema-driven sanitiser and a nonce-protected reset action.
- REST routes `GET/POST /items` and `GET /items/<id>` with permission callbacks, validation and a schema.
- `[wppbp_items]` shortcode, `wppbp_item` custom post type with registered meta.
- `wp wppbp` commands: `list`, `create`, `cleanup`, `settings`.
- Daily cleanup cron event, activation and deactivation handlers, `uninstall.php`.
- Translation template (`languages/*.pot`).
- `bin/rename.php` to rename the namespace, slug, text domain, prefix and author.
- PHPUnit tests for the container, sanitisers, settings schema and renamer (no WordPress needed).
- PHPCS configuration (WordPress Coding Standards and PHPCompatibilityWP).
- GitHub Actions: lint and tests on PHP 8.1-8.3, and a release workflow that builds an installable zip.
