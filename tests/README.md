# Testing

Install [DDEV](https://docs.ddev.com/en/stable/users/install/ddev-installation/)
and a supported Docker provider (OrbStack works on macOS). From this plugin's
checkout, run:

```sh
ddev test
ddev test --filter='a test name'
ddev test --suite=all
ddev test --suite=performance
```

The command starts the dedicated test project, installs dependencies inside DDEV,
creates a clean Craft application, installs this checkout as a Composer path
dependency, seeds plugin fixtures and runs Pest. No separate Craft site, host PHP,
host Composer, database setup or `.env.testing` file is required. The root Composer
`test` aliases call this same command if you already have Composer on your host.

Tests run against real Craft. The PHPUnit XML discovers PHP tests; the suite
manifest in `tests/runtime/suite.json` defines intentional group exclusions.
The default excludes slow, performance, large-performance and migration-plugin
groups. Some plugins have additional suites listed in that manifest. Test files
named `Unit` may still rely on the Craft application.

Each invocation rebuilds database, project configuration and storage under
`.cache/verbb-tests/app`. Dependencies are cached between runs. The generated app
loads the plugin from this checkout; developer `.env` files and paired sites are
not used. Tests must not be pointed at an external database. Run serially; parallel
workers are rejected until they have independent state.

The DDEV project name is stable. Re-running tests does not allocate another
project. Use `ddev stop` when finished; use `ddev delete` from this checkout to
remove this dedicated project's containers and database volume. The next test run
recreates its baseline. Keep reports before deleting generated files.

Results and combined setup/test output are written to `.cache/verbb-tests/result.json`
and `.cache/verbb-tests/latest.log`; Craft logs remain in the generated app's
storage. A failed setup exits nonzero and does not run tests against partial state.

The runtime scaffold is committed with the plugin, so no private Verbb tooling or
sibling checkout is needed. Plugin-specific test fixtures belong in this repository.
Do not add database/schema repair to the PHPUnit bootstrap: fresh installation must
work through the normal Craft/plugin installation path first.

The initial runtime is PHP 8.3 and MySQL 8.0. This environment is not a claim of
complete coverage for every supported Craft/PHP/database version. Compatibility
matrix expansion must validate the actual runtime and fixture behavior.

The test application's dependency baseline is versioned in `tests/runtime/composer.lock`.
Use `ddev test --update-lock` when intentionally updating that baseline, and review
the lock diff alongside the test results. This does not update the plugin's root lock.
JUnit results are available in `.cache/verbb-tests/junit.xml`. Tests exceeding 60 seconds
are reported as failures; annotate genuinely long-running tests with PHPUnit size metadata.

Existing performance-report and baseline-maintenance aliases also provision through
this runner, using the named `--task=` entries in `suite.json`. These explicitly
requested maintenance tasks report `completed-task`, not a passing Pest suite.

## Quality checks and automation

```sh
npm ci --legacy-peer-deps
npm test
ddev test --order-by=random --random-order-seed=9122026
ddev test --suite=single-site
ddev test --suite=commerce
ddev test --suite=migration
ddev test --suite=migration --task=mutations
ddev test --task=upgrade
ddev test --task=lifecycle
```

The default runtime has a primary site and two secondary sites sharing a different
site group. Single-site behaviour has its own suite. Expected configuration skips
must name the lane that exercises them; missing required tables, fields or fixture
files must fail. Commerce and Freeform are locked development dependencies, enabled
only in their dedicated suites. Freeform fixtures are imported before the test
process because its query implementation caches the form registry for the process.

The lifecycle task creates a populated form, verifies that disabling and enabling retain its records, checks that uninstall removes all Formie tables and elements, and confirms that a fresh reinstall can save and reload submission content. It runs only in the owned disposable application.

The upgrade task installs the locked Formie 3.1.39 baseline on Craft 5.11.1 in a
separate generated app with an `upg_` table prefix. It creates real forms, a synced
field, nested submission content and a notification before Composer switches to
this checkout and Craft runs its migrations. The checks reload those original
records after migration and verify values, shared-field identity and notification
reference resolution. Weekly/manual automation runs this contract. It covers this
populated Formie 3 baseline; it does not imply every historical version or third-party
field combination has been upgraded successfully.

PR automation runs default and seeded-random PHP, core and CP JavaScript, and the
[Chromium browser contracts](browser/README.md). Weekly and manual runs add
performance, slow, migration, Commerce and single-site lanes, plus the six targeted
mutation checks after migration. A workflow definition
is not evidence that a hosted run has passed; inspect the uploaded result artifacts.

| Runtime | Coverage status |
| --- | --- |
| PHP 8.3, Craft version in `tests/runtime/composer.lock`, MySQL 8.0 | Automated integration baseline |
| Same runtime, single site | Dedicated lane |
| Same runtime, Commerce enabled | Dedicated lane |
| Same runtime, Freeform enabled | Dedicated migration lane |
| Node 22, Chromium | JavaScript and browser baseline |
| Other supported PHP/Craft/database versions and other browser engines | Not established by this matrix |

Prefer a named business outcome and independently specified expected values. For
persistence, reload before asserting and check that an unrelated update preserves
data. For rejection, use a valid control and verify the rejected operation leaves
no saved record or side effect. Use nonmatching records to test query boundaries.
Type checks remain useful for API smoke coverage, but do not imply content fidelity.

Performance tests must verify output before timing it. The submission query budget
compares one and twenty saved submissions and allows at most two additional read
queries; it detects per-row loading without relying on machine speed. Small workflow
baselines retain a generous five-second ceiling. Recorded timing profiles are not
portable throughput guarantees. Synthetic performance writes are rolled back after
each case. GraphQL schema benchmarks scope permissions to their own fixtures and
require exact form/submission type counts. Unscoped service benchmarks still include
pre-existing forms in an all-suite run; compare their dedicated clean-lane results.
Concurrency and delivery tests continue to use committed transactions.

The mutation task loads copies of individual source files in separate child
processes. It first requires a passing baseline, then an assertion failure from the
owning test for each deliberately broken behaviour. Syntax errors, missing fixtures,
and skipped tests cannot count as detected mutations. Results and logs are saved in
`.cache/verbb-tests/mutations/`. These targeted checks are not a whole-suite mutation
score and do not justify deleting other regression tests.
