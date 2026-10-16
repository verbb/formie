# Browser Tests

Install the [test prerequisites](../README.md), then run these commands from the plugin checkout:

```sh
npm ci --legacy-peer-deps
npx playwright install chromium
ddev test --task=browser-fixture
npm run test:browser
```

The runner builds the assets and tests against the disposable Craft application in Chromium. Run PHP and browser suites serially; a PHP run resets the browser fixture, so provision it again before the next browser run.

The HTML report is saved in `.cache/verbb-tests/browser-report`. Failed tests retain a Playwright trace.
