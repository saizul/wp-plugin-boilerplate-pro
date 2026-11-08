# Contributing

Contributions are welcome: bug fixes, documentation and improvements to the patterns shown here.

## Setup

```bash
git clone https://github.com/saizul/wp-plugin-boilerplate-pro.git
cd wp-plugin-boilerplate-pro
composer install
```

## Checks

```bash
composer test    # PHPUnit, runs without WordPress
composer lint    # PHPCS with WordPress Coding Standards
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

CI runs the same checks on PHP 8.1, 8.2 and 8.3.

## Guidelines

- Keep the boilerplate dependency-free at runtime. Dev tools belong in `require-dev`.
- Code that does not need WordPress should stay free of WordPress functions so it can be unit-tested
  (see `Support\Sanitizer`, `Settings\Schema`, `Container\Container`).
- Every new input path needs sanitisation, every output path needs escaping, every state-changing
  request needs a capability check and (for non-REST requests) a nonce.
- Use the text domain `wp-plugin-boilerplate-pro` for all user-facing strings and regenerate
  `languages/wp-plugin-boilerplate-pro.pot` when strings change (`wp i18n make-pot . languages/wp-plugin-boilerplate-pro.pot`).
- Anything you add must survive `bin/rename.php`. If you introduce new identifiers, make sure they
  contain the existing prefix (`wppbp`), slug or namespace. `RenamerTest` renames a copy of the whole
  project and fails when old identifiers remain.
- Update `CHANGELOG.md` and, for user-visible changes, `readme.txt`.

## Pull requests

Describe the problem and how you tested it. Small, focused pull requests are reviewed faster.
