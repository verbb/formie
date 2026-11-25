#!/usr/bin/env bash
set -euo pipefail

php .github/scripts/check-runtime-lock.php
composer install --working-dir=tests/php-check --no-interaction --prefer-dist --no-progress --no-plugins --no-scripts
php tests/php-check/tests/run.php
php tests/php-check/bin/check.php --project tests/php-check --vendor tests/php-check/vendor tests/php-check/src tests/php-check/bin tests/php-check/prepare.php tests/php-check/tests
php tests/php-check/prepare.php
composer install --working-dir=.cache/verbb-tests/app --no-interaction --prefer-dist --no-progress --no-plugins --no-scripts
composer check-platform-reqs --working-dir=.cache/verbb-tests/app --no-plugins
php tests/php-check/bin/check.php --project . --vendor .cache/verbb-tests/app/vendor --optional tests/Support/StaticAnalysis/optional-classes.php src .github/scripts tests/runtime/run.php tests/runtime/pest.php tests/bootstrap-craft.php tests/php-check/prepare.php
