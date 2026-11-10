# WP Plugin Boilerplate Pro

[![CI](https://github.com/saizul/wp-plugin-boilerplate-pro/actions/workflows/ci.yml/badge.svg)](https://github.com/saizul/wp-plugin-boilerplate-pro/actions/workflows/ci.yml)
[![License: GPL v2+](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)
![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)

A modern, working starter for WordPress plugins. It is not an empty skeleton: every pattern is
implemented once, tested where possible, and ready to be renamed into your own plugin.

Built by [Saizul Amin](https://saizul.com).

## What is included

| Area | Where | Notes |
| --- | --- | --- |
| Plugin header, activation, deactivation | `wp-plugin-boilerplate-pro.php`, `src/Lifecycle.php` | PHP version guard with admin notice |
| Autoloading | `composer.json`, main file | PSR-4 via Composer, plus a fallback loader when `vendor/` is missing |
| DI container | `src/Container/` | `get`/`has`, shared and factory bindings, constructor auto-wiring, circular dependency detection |
| Service providers | `src/Providers/` | `register()` binds services, `boot()` attaches hooks |
| Settings page | `src/Admin/SettingsPage.php`, `src/Settings/` | Settings API, schema-driven sanitising, nonce-protected reset action |
| REST API | `src/Rest/ItemsController.php` | `permission_callback` on every route, argument validation, JSON schema |
| Shortcode | `src/Shortcodes/ItemsShortcode.php` | `[wppbp_items count="5" layout="grid"]`, sanitised attributes, escaped output |
| Block | `src/Blocks/ItemsBlock.php`, `blocks/items/` | Dynamic block from `block.json`, PHP `render_callback`, editor script without a build step |
| Custom post type | `src/PostTypes/ItemPostType.php` | REST enabled, registered meta with sanitiser and auth callback |
| WP-CLI | `src/Cli/ItemsCommand.php` | `wp wppbp list`, `create`, `cleanup`, `settings` |
| Cron | `src/Cron/CleanupTask.php` | Self-healing schedule, cleared on deactivation |
| i18n | `languages/*.pot`, `I18nProvider` | Text domain equals the plugin slug |
| Uninstall | `uninstall.php` | Removes options and cron; deletes content only if the owner opted in |
| Rename tool | `bin/rename.php`, `bin/Renamer.php` | Namespace, slug, text domain, prefix, constants, author |
| Tests | `tests/Unit/` | Container, sanitisers, settings schema and renamer, no WordPress needed |
| Coding standards | `phpcs.xml.dist` | WordPress Coding Standards + PHPCompatibilityWP (8.1+) |
| CI and releases | `.github/workflows/` | `php -l` + PHPUnit on 8.1, 8.2, 8.3; tagged releases build a clean zip |

## Project structure

```
wp-plugin-boilerplate-pro/
├── wp-plugin-boilerplate-pro.php   main file: headers, autoload, hooks
├── uninstall.php
├── composer.json
├── readme.txt                      WordPress.org style readme
├── assets/css/admin.css
├── blocks/items/                   block.json, editor.js, editor.asset.php
├── bin/
│   ├── rename.php                  CLI: start a new plugin from this one
│   ├── Renamer.php                 pure PHP renamer (tested)
│   └── build-zip.sh                builds dist/<slug>.zip
├── languages/wp-plugin-boilerplate-pro.pot
├── src/
│   ├── Plugin.php                  kernel: container + providers
│   ├── Lifecycle.php               activate / deactivate
│   ├── Container/                  Container, ContainerException, NotFoundException
│   ├── Providers/                  ServiceProvider interface + 7 providers
│   ├── Admin/SettingsPage.php
│   ├── Settings/                   Schema (pure), Repository (get_option)
│   ├── Rest/ItemsController.php
│   ├── Shortcodes/ItemsShortcode.php
│   ├── Blocks/ItemsBlock.php       dynamic block, registered from blocks/items/block.json
│   ├── PostTypes/ItemPostType.php
│   ├── Cli/ItemsCommand.php
│   ├── Cron/CleanupTask.php
│   └── Support/                    Sanitizer, ItemsArgs (pure helpers)
├── tests/
│   ├── bootstrap.php               no WordPress required
│   ├── Unit/                       ContainerTest, SanitizerTest, ItemsArgsTest, SchemaTest, RenamerTest
│   └── Integration/                real WordPress via wp-env (bootstrap, PluginBootTest, RestItemsTest)
├── .wp-env.json
├── phpunit.xml.dist
├── phpcs.xml.dist
└── .github/workflows/              ci.yml, release.yml
```

## Start your own plugin

Clone the repository, then from its root:

```bash
php bin/rename.php --name="Acme Reviews" --author="Jane Doe" --author-uri="https://example.com" --dry-run
php bin/rename.php --name="Acme Reviews" --author="Jane Doe" --author-uri="https://example.com" --cleanup
```

Only `--name` is required. Everything else is derived, and each value can be set explicitly:

| Option | Example | Replaces |
| --- | --- | --- |
| `--name` | `"Acme Reviews"` | Plugin name in headers, titles and strings |
| `--slug` | `acme-reviews` | Slug, text domain, main file name, `.pot` file, REST namespace, admin page slug |
| `--namespace` | `"Acme\Reviews"` | PHP namespace, `composer.json` autoload, fallback autoloader |
| `--prefix` | `acrv` | `wppbp_` functions/options/hooks and `WPPBP_` constants |
| `--vendor` | `acme` | Composer vendor in `acme/acme-reviews` and the GitHub path |
| `--author`, `--author-uri` | `"Jane Doe"` | Author headers and `composer.json` |

Flags: `--dry-run` lists what would change; `--cleanup` also deletes `bin/rename.php`,
`bin/Renamer.php` and `tests/Unit/RenamerTest.php` once you no longer need them.

The script rewrites text files only (PHP, JSON, Markdown, XML, YAML, `.pot`, ...), skips `vendor/`,
`.git/` and the license, and renames the main plugin file and the `.pot` file. Afterwards:

1. Rename the project folder to the new slug.
2. Run `composer install` (or `composer dump-autoload`).
3. Run `composer lint:fix` because the new names have different lengths than the old ones.
4. Regenerate the translation template: `wp i18n make-pot . languages/<slug>.pot`.
5. Replace this README with your own and remove the features you do not need (delete the provider
   from `Plugin::defaultProviders()` and its folder).

`RenamerTest` renames a copy of the whole project and checks that no old identifier is left and that
every PHP file still lints, so the boilerplate cannot silently drift.

## Architecture in one minute

```php
// wp-plugin-boilerplate-pro.php
add_action( 'plugins_loaded', fn() => Plugin::instance()->boot() );
```

`Plugin::boot()` creates a container, registers every provider (binding services), then boots every
provider (attaching hooks). Because registration finishes before any boot, a provider may depend on
services bound by another provider.

```php
final class GreetingProvider implements ServiceProvider {
    public function register( Container $container ): void {
        $container->singleton( GreetingService::class );   // auto-wired from type hints
    }

    public function boot( Container $container ): void {
        add_filter( 'the_content', array( $container->get( GreetingService::class ), 'filter' ) );
    }
}
```

Add the class to `Plugin::defaultProviders()`. The container resolves constructor parameters by type:

```php
class ItemsShortcode {
    public function __construct( private Repository $settings ) {}   // Repository is injected
}
```

## Block

`blocks/items/block.json` is the single source of truth for the block name and attributes; `ItemsBlock`
passes it to `register_block_type()` with a PHP `render_callback`, so the front end always renders on the
server and `save()` returns `null`. The editor script (`editor.js`) uses the `wp.*` globals and
`ServerSideRender`, so there is nothing to compile; `editor.asset.php` lists its script dependencies.
Attribute rules (count 1-100, layout from the settings schema) live in `Support\ItemsArgs`, shared with
the shortcode and covered by unit tests.

## Security patterns

| Pattern | Example in this repo |
| --- | --- |
| Direct access guard | `if ( ! defined( 'ABSPATH' ) ) { exit; }` in every file that touches WordPress |
| Capability checks | `SettingsPage::render()` and `handle_reset()`, REST `create_item_permissions_check()` |
| Nonces | `settings_fields()` for the Settings API, `wp_nonce_field()` + `check_admin_referer()` for the reset action |
| Sanitise on input | `Settings\Schema::sanitize()`, `Support\Sanitizer`, REST `sanitize_callback`, shortcode attribute parsing |
| Escape on output | `esc_html()`, `esc_attr()`, `esc_url()` at the point of output in the settings page and shortcode |
| REST permissions | Every `register_rest_route()` has a `permission_callback`; public reads are explicit |
| Safe redirects | `wp_safe_redirect()` after the admin action |
| Safe uninstall | Content is removed only when the owner enabled it |

## REST API

```bash
curl https://example.com/wp-json/wp-plugin-boilerplate-pro/v1/items?per_page=5&search=guide
```

`POST /items` needs an authenticated user who can publish posts (cookie + `X-WP-Nonce`, or application
passwords). Body: `title` (required), `content`, `url`.

## WP-CLI

```bash
wp wppbp list --format=json
wp wppbp create "My first item" --porcelain
wp wppbp cleanup
wp wppbp settings
```

## Development

```bash
composer install
composer test        # PHPUnit (no WordPress required)
composer lint        # PHPCS
composer lint:fix    # PHPCBF
```

### Integration tests

The integration suite boots a real WordPress with the WordPress test library inside
[`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).
You need Docker and Node 18+:

```bash
npm install
npm run test:integration                              # start wp-env, install PHPUnit 9.6, run the suite
npm run test:integration -- --filter RestItemsTest    # extra arguments go to PHPUnit
npm run env:stop
```

`tests/Integration/composer.json` installs PHPUnit 9.6 and the Yoast polyfills separately from the root
`composer.json`, because the WordPress test suite does not support PHPUnit 10+. The unit tests are not
affected and still run with `composer test`. CI runs the same script in the `integration` job.

Without Composer you can still run the unit tests: `tests/bootstrap.php` falls back to a PSR-4 loader, so
`php phpunit.phar` works in a plain checkout.

## Releases

Push a tag such as `v1.0.1`. The release workflow checks that the tag matches the plugin header, the
version constant and `readme.txt`'s `Stable tag`, runs the tests, builds the zip with
`bin/build-zip.sh` (production Composer autoloader, no tests or tooling, correct folder name) and
attaches it to the GitHub release. You can build locally with `bash bin/build-zip.sh`.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
