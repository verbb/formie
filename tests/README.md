# Testing

Install [DDEV](https://docs.ddev.com/en/stable/users/install/ddev-installation/) and a supported Docker provider. Run PHP tests from the plugin checkout:

```sh
ddev test
ddev test --filter='a test name'
ddev test --suite=all
```

The runner installs dependencies and rebuilds a disposable Craft application under `.cache/verbb-tests/app`. Use its generated database; do not point it at an existing Craft site. Run PHP and browser suites serially because they share this runtime.

Results and setup output are saved in `.cache/verbb-tests/result.json` and `.cache/verbb-tests/latest.log`.

For JavaScript tests:

```sh
npm ci --legacy-peer-deps
npm test
```

See [Browser Tests](browser/README.md) for browser setup.
