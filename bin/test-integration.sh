#!/usr/bin/env bash
# Run the integration tests inside wp-env (needs Docker and Node).
#
#   npm install && npm run test:integration
#
# Extra arguments are passed to PHPUnit, e.g. `npm run test:integration -- --filter RestTest`.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

# wp-env mounts the project as wp-content/plugins/<folder name>.
PLUGIN_DIR="wp-content/plugins/$(basename "$PWD")"

npx wp-env start
npx wp-env run tests-cli --env-cwd="$PLUGIN_DIR" composer install --working-dir=tests/Integration --no-interaction --prefer-dist --no-progress
npx wp-env run tests-cli --env-cwd="$PLUGIN_DIR" tests/Integration/vendor/bin/phpunit -c phpunit.integration.xml.dist "$@"
