# Browser contracts

Run `npm ci`, `npx playwright install chromium`,
`ddev test --task=browser-fixture`, then `npm run test:browser` from the plugin.

The command builds current CP and production frontend assets and bundles the current adapter source.
These tests mount React, Vue and Web Components
in Chromium, and submit through real Craft REST actions. Saved content is checked
through a read-only route restricted to the synthetic fixture form. The multi-page React journey checks retained nested values, the exact uploaded file
contents after Back/Next navigation, and one saved submission after duplicate clicks.
A control-panel journey creates a form, adds a field, and verifies its exact label
and placeholder after saving and reloading. No transport response is mocked. The web entry point returns 404 unless the owned disposable
runtime was explicitly provisioned for browser tests. A subsequent PHP test run
removes that marker. Run PHP and browser suites serially because they share this
runtime. Failures retain a Playwright trace and HTML report.

Twig-rendered forms also exercise AJAX submission, native page reload and token
refresh for cached markup. These journeys verify required-field rejection,
conditional value clearing, recalculation after an input changes and the exact
saved result. They use Craft's asset registration and the shipped production browser
bundle, including its CSS and lazy chunks. The frontend build rejects stale nested
runtime packages and builds the current core/browser workspaces first.

The dashboard journey loads the shipped widget libraries, verifies a saved
submission appears in the chart and checks that tooltip labels remain plain text.

Use stable labels and visible outcomes for browser assertions. Check saved values
as well as success UI. Each additional scenario should protect a distinct visitor
or editor failure mode; avoid reproducing unit-test matrices in Chromium.
