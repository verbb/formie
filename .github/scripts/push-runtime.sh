# Shared by the plugin push commands. The caller has selected the checkout root.
minimum() {
    python3 .github/scripts/check-ci.py
    local image
    image=$(docker build --quiet --platform linux/amd64 --file .github/php-minimum/Dockerfile .github)
    # Keep this independent of the integration app and any locally installed dependencies.
    mkdir -p .cache tests/php-check/vendor
    docker run --rm --platform linux/amd64 -v "$PWD:/var/www/html:ro" \
        --mount type=volume,destination=/var/www/html/.cache,volume-nocopy \
        --mount type=volume,destination=/var/www/html/tests/php-check/vendor,volume-nocopy \
        --mount type=volume,source=verbb-push-composer-cache,destination=/tmp/composer-cache \
        "$image" bash .github/scripts/check-push-php.sh
}

check_npm_links() {
    # A sibling checkout can satisfy a local npm link that will be missing on GitHub.
    node - <<'NODE'
const { isAbsolute, relative, resolve } = require('node:path');
for (const [name, pkg] of Object.entries(require('./package-lock.json').packages)) {
    if (!pkg.link) continue;
    const target = relative(process.cwd(), resolve(pkg.resolved));
    if (target === '..' || target.startsWith('../') || isAbsolute(target)) {
        throw new Error(name + ' links outside the checkout: ' + pkg.resolved);
    }
}
NODE
}

start_runtime() {
    # Reuse the dedicated test site when this checkout already owns a healthy PHP 8.3 runtime.
    local project expected web db
    project=$(node -p "require('./tests/runtime/suite.json').project")
    expected="healthy $(pwd -P)"
    web=$(docker inspect --format '{{.State.Health.Status}} {{index .Config.Labels "com.ddev.approot"}}' "ddev-$project-web" 2>/dev/null || true)
    db=$(docker inspect --format '{{.State.Health.Status}} {{index .Config.Labels "com.ddev.approot"}}' "ddev-$project-db" 2>/dev/null || true)
    if [[ "$web" == "$expected" && "$db" == "$expected" ]] && ddev exec --raw -- php -r '
        exit(PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 3 && getenv("DDEV_DOCROOT") === "tests/runtime/web" ? 0 : 1);
    '; then
        return
    fi
    bash .github/scripts/configure-ddev.sh 8.3 tests/runtime/web
}

run_integration() {
    ddev exec --raw -- composer --no-plugins validate --no-check-publish --no-check-lock
    python3 .github/scripts/record-inputs.py before
    ddev exec --raw -- php tests/runtime/run.php --order-by=default "$@"
    python3 .github/scripts/record-inputs.py after
}
