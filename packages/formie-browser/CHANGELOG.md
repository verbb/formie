# Changelog

## Unreleased

### Added

- Add the `combobox` field module for searchable dropdown fields, powered by Tom Select. Supports single and multi-select filtering, placeholder handling, and Formie theme styling.
- Add the `address-state` field module for country-dependent Address state/province inputs, including subdivision fetching, loading UX, searchable dropdown enhancement, and password-manager autofill reconciliation.
- Add the `address-country` field module for IP-based country preselect on Address fields, plus shared `fetchCountryFromIp()` / `createGeoIpLookup()` utilities used by Phone country preselect.
- Export `initFormieCombobox()` from the combobox module for reuse by other field modules.

### Changed

- Normalise Address sub-field selectors to `data-formie-address-*`, with `ADDRESS_LEGACY_SELECTORS` fallbacks for older markup.

## 1.0.4

### Patch Changes

- version bump
- Updated dependencies
    - @verbb/formie-core@1.0.4

## 1.0.3

### Patch Changes

- version bump
- Updated dependencies
    - @verbb/formie-core@1.0.3

## 1.0.2

### Patch Changes

- version bump
- Updated dependencies
    - @verbb/formie-core@1.0.2

## 1.0.1

### Patch Changes

- d25d240: Update package homepage docs URLs
- Updated dependencies [d25d240]
    - @verbb/formie-core@1.0.1

## 1.0.0

- Initial public release.
