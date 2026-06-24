# Changelog

## Unreleased

### Changed
- Improve default theme color contrast for WCAG 2.2 AA — darker error text, muted helper text, and border-only control styling with split border tokens. ([#2475](https://github.com/verbb/formie/issues/2475))
- Add Ajax-safe tab link state theme keys `tabLinkCurrent` and `tabLinkInactive`, with aliases for `pageTabLinkActive`, `pageTabLinkInactive`, and `pageInactive`. ([#1279](https://github.com/verbb/formie/issues/1279))
- Dispatch `formie:submit:result` before payment follow-up state handling so captcha modules can refresh one-time tokens before internal resubmits.

### Fixed
- Fix payment amount parsing for locale-formatted dynamic values (for example `1,234.56`, `1.750,00`, and `£750.00`) in browser payment modules. ([#2334](https://github.com/verbb/formie/issues/2334))
- Fix payment follow-up states (such as 3D Secure authentication) showing as red validation errors. Action-required and pending payment responses now render as neutral form notices via `paymentStatus` and `paymentMessage` metadata. ([#2660](https://github.com/verbb/formie/issues/2660))
- Fix Stripe and other payment follow-up submits failing when one-time captcha tokens are revalidated on the second Ajax POST by refreshing captcha tokens before follow-up handlers run. ([#2465](https://github.com/verbb/formie/issues/2465))

## 1.0.8 - 2026-06-24

### Added
- Add `survey-likert`, `survey-rank`, and `survey-rating` field modules for Survey fields, including Likert layout styling, drag-and-drop rank ordering with submitted order preserved on validation re-render, and interactive star rating controls. ([#605](https://github.com/verbb/formie/issues/605), [#798](https://github.com/verbb/formie/issues/798), [#2282](https://github.com/verbb/formie/issues/2282))
- Dispatch server-resolved `clientEvents` from Ajax submit responses to `dataLayer` and the `formie:client-event` DOM event, with support for multiple named events per page and pending client events on mount. ([#888](https://github.com/verbb/formie/issues/888))

### Changed
- Persist `themeConfig` and `frontendTheme` on the form element during client mount so Ajax field modules can round-trip render-time theme settings.

### Fixed
- Fix Summary field Ajax refreshes ignoring custom `themeConfig` classes by round-tripping render-time theme config through the summary HTML endpoint. ([#1721](https://github.com/verbb/formie/issues/1721))

## 1.0.7 - 2026-06-18

### Added
- Add configurable `aria-live` behaviour for field and form validation errors via the plugin `errorAriaLive` setting (`polite`, `assertive`, or `off`). Live validation while typing always uses polite announcements. ([#2505](https://github.com/verbb/formie/issues/2505))

### Fixed
- Skip validation for fields disabled by conditional logic, and disable conditionally hidden submit/next buttons so Enter no longer triggers hidden actions. ([#2727](https://github.com/verbb/formie/issues/2727), [#1136](https://github.com/verbb/formie/issues/1136), [Discussion #1628](https://github.com/verbb/formie/discussions/1628))
- Initialise captcha placeholders immediately when multipage forms change page via tabs or Ajax **Next**, so providers mount on the visible page before submit. ([#1893](https://github.com/verbb/formie/issues/1893))
- Tear down Cloudflare Turnstile widgets with `remove()` during remounts and apply CSP nonces to dynamically loaded captcha scripts where available. ([#2535](https://github.com/verbb/formie/issues/2535))
- Wait for reCAPTCHA Enterprise readiness before executing score/policy challenges on multipage navigation. ([#2224](https://github.com/verbb/formie/issues/2224))

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
