# Changelog

## Unreleased

## 1.0.6 - 2026-06-14

### Added

- Add the `upload-manager` field module for File Upload **Upload Manager (Advanced)** fields, powered by Uppy (`@uppy/core` + `@uppy/xhr-upload`). Supports drag-and-drop, async staged uploads to Formie upload endpoints, transfer progress with server-processing feedback, remove, hidden asset ID sync, hydration for saved submissions and drafts, up/down reorder controls, and repeater row binding.

### Changed

- Run stale pending File Upload cleanup during Craft garbage collection.

### Fixed

- Fix Signature field initialization on multi-page forms, conditional fields, and late layout by retrying canvas sizing, watching visibility changes, and surfacing accurate status messages when canvas support or init fails. ([#2708](https://github.com/verbb/formie/issues/2708))

## 1.0.5 - 2026-06-12

### Added

- Add the `combobox` field module for searchable dropdown fields, powered by Tom Select. Supports single and multi-select filtering, placeholder handling, and Formie theme styling.
- Add the `address-state` field module for country-dependent Address state/province inputs, including subdivision fetching, loading UX, searchable dropdown enhancement, and password-manager autofill reconciliation.
- Add the `address-country` field module for IP-based country preselect on Address fields, plus shared `fetchCountryFromIp()` / `createGeoIpLookup()` utilities used by Phone country preselect.
- Add Date Range collection to the `date-picker` module for Calendar (Advanced) fields, using Flatpickr range mode for start and end date/time values.
- Export `initFormieCombobox()` from the combobox module for reuse by other field modules.
- Add table column reference resolution to the `calculations` module for Calculations field expressions.

### Changed

- Normalise Address sub-field selectors to `data-formie-address-*`, with `ADDRESS_LEGACY_SELECTORS` fallbacks for older markup.
- Honour the `includeFlatpickrCss` plugin setting so projects providing their own Flatpickr stylesheet can skip Formie's bundled CSS.

### Fixed

- Fix page-reload single-line and multi-line text limit validation rerenders so field error styling, form-level error messages, and scroll/focus behaviour match Ajax submissions.

## 1.0.4 - 2026-06-07

### Added

- Add client-side validation message overrides via `data-formie-validation-*-message` attributes, wired through the validator for required, email, number, pattern, match, and min/max rules.
- Add validation message parity for file upload and checkbox/radio options-limit rules.
- Add per-field validation error position support (`Above Input` / `Below Input`) via `field-error-container`.
- Extend `t()` to resolve Craft-style `{param, plural, …}` and `{param, number}` message syntax for translated validation copy.

### Changed

- Replace text-limit counter translation strings with Craft plural syntax and show context-aware counter copy for empty, under-limit, and over-limit states.
- Update checkbox-radio, file-upload, text-limit, and submit-result modules for validation message and error-position handling.

## 1.0.3 - 2026-06-06

### Added

- Add `submit-readiness` validation module to disable the submit button until the current page passes validation when the form setting is enabled.
- Add default country preselect support to the Google Places address auto-complete module.
- Add form CSS for placing the submit button at the end of the last field row.

### Changed

- Rework file-upload module persistence for Ajax and multi-page forms, including nested Repeater and Group fields.
- Update conditions module effects for control panel submission field condition behaviour.

### Fixed

- Fix File Upload fields inside Repeater or Group fields not initializing upload persistence for nested inputs.
- Fix Ajax and multi-page File Upload fields losing their uploaded-file summary after step navigation or when editing existing submissions.

## 1.0.2 - 2026-06-03

### Fixed

- Fix front-end JS/CSS build output, restoring TypeScript declaration files and correcting bundled module chunk references.

### Changed

- Rebuild browser distribution after build pipeline fixes.

## 1.0.1 - 2026-06-02

### Changed

- Update package homepage and documentation URLs.

## 1.0.0 - 2026-06-02

### Added

- Initial public release of the Formie browser runtime, including field modules, validation, conditions, payments, captcha integrations, and the `createFormieClient()` API.
