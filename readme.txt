=== WP Plugin Boilerplate Pro ===
Contributors: saizulamin
Tags: boilerplate, starter, developer, rest-api, wp-cli
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern WordPress plugin starter with a DI container, service providers, a settings page, REST route, shortcode, custom post type, WP-CLI command and cron event.

== Description ==

WP Plugin Boilerplate Pro is a working example plugin meant to be copied and renamed. It demonstrates:

* PSR-4 autoloading through Composer, with a fallback autoloader when `vendor/` is missing
* A small dependency injection container with constructor auto-wiring
* Service providers that register services first and attach hooks second
* A Settings API page with sanitisation, escaping, capability checks and nonces
* A REST API endpoint with a permission callback, argument validation and a JSON schema
* Settings export and import as JSON (REST and WP-CLI), with strict schema validation and a dry run
* A shortcode, `[wppbp_items]`
* A custom post type with registered meta
* WP-CLI commands under `wp wppbp`
* A daily cron event that removes expired trashed items
* Translation-ready strings and a `.pot` file
* A clean uninstall routine

To start your own plugin, run `php bin/rename.php --name="My Plugin"` from the project folder. See the README on GitHub for details.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the release zip from the Plugins screen.
2. Activate the plugin.
3. Go to Settings > Boilerplate Pro.

== Frequently Asked Questions ==

= Does it need Composer? =

No. Composer generates an optimised autoloader, but the plugin falls back to a built-in PSR-4 loader. Release zips already contain the production autoloader.

= How do I use the REST endpoint? =

`GET /wp-json/wp-plugin-boilerplate-pro/v1/items` lists published items. `POST` to the same URL requires a user who can publish posts.

= How do I copy settings to another site? =

Run `wp wppbp export-settings --file=settings.json` on the first site and `wp wppbp import-settings settings.json` on the second, or use `GET` and `POST` on `/wp-json/wp-plugin-boilerplate-pro/v1/settings` as an administrator. Add `--dry-run` (or `?dry_run=1`) to preview the changes. Invalid files are rejected and nothing is changed.

= What happens to my data on uninstall? =

Options and the scheduled event are always removed. Items are deleted only if "Delete data on uninstall" is enabled.

== Changelog ==

= 1.0.0 =
* Initial release.
