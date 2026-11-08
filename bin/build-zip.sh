#!/usr/bin/env bash
#
# Builds an installable plugin zip in ./dist.
#
#   bin/build-zip.sh            -> dist/<slug>.zip
#
# Steps: copy the project without the files listed in .distignore, install the production
# Composer autoloader (no dev dependencies), and zip with the plugin folder at the archive root.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

SLUG="$(sed -n 's/^ \* Text Domain:[[:space:]]*//p' ./*.php | head -n 1 | tr -d '[:space:]')"
if [ -z "$SLUG" ]; then
  echo "Could not read the Text Domain header from the main plugin file." >&2
  exit 1
fi

VERSION="$(sed -n 's/^ \* Version:[[:space:]]*//p' "./$SLUG.php" | head -n 1 | tr -d '[:space:]')"
STAGE="$ROOT/dist/stage/$SLUG"

rm -rf "$ROOT/dist"
mkdir -p "$STAGE"

# composer.json is needed to generate the autoloader, so it is excluded only after that step.
rsync -a --exclude-from=<(grep -vE '^(#|$|composer\.json$|composer\.lock$)' .distignore) ./ "$STAGE/" --exclude=dist
cp composer.json "$STAGE/composer.json"

(
  cd "$STAGE"
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts --quiet
  rm -f composer.json composer.lock
  rm -rf vendor/composer/installed.json.bak
)

(
  cd "$ROOT/dist/stage"
  zip -rq "../$SLUG.zip" "$SLUG" -x '*.DS_Store'
)

rm -rf "$ROOT/dist/stage"
echo "Built dist/$SLUG.zip (version ${VERSION:-unknown})"
