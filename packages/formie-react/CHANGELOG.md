# Changelog

## Unreleased

### Added
- Added `inputId`, `labelId` and `instructionsId` to custom `Field` components and field components, so a custom label can point at the field’s control. ([#3140](https://github.com/verbb/formie/issues/3140))

### Changed
- Field labels now point at their control with `for` instead of the control repeating the label in `aria-label`, and field instructions describe the control with `aria-describedby`. Controls rendered inside a custom `Field` component keep their `aria-label`. ([#3140](https://github.com/verbb/formie/issues/3140))
- Radio options are now grouped with `role="radiogroup"` and Checkboxes options with `role="group"`, each named by the field label. The Agree checkbox is wrapped in `.formie-react-agree` named by the field label, and keeps its description as its own label. ([#3140](https://github.com/verbb/formie/issues/3140))

## 4.0.10 - 2026-11-17

### Changed
- Released alongside Formie 4.0.10 with no changes to this package.

## 4.0.9 - 2026-11-13

### Fixed
- Fixed Rich Text, HTML and Heading fields showing an “Unsupported field type” message instead of their content. Rich Text and HTML labels are now hidden unless the field shows them. ([#3116](https://github.com/verbb/formie/issues/3116))

## 4.0.8 - 2026-11-09

### Changed
- Released alongside Formie 4.0.8 with no changes to this package.

## 4.0.7 - 2026-11-05

### Changed
- Changed the `@verbb/formie-*` package versions to match the Formie plugin, so Formie 4.0.7 pairs with version 4.0.7 of each package. The jump from 1.x has no breaking changes, but version ranges such as `^1.0.16` need updating once. ([#3081](https://github.com/verbb/formie/issues/3081))

### Fixed
- Fixed TypeScript errors in projects using NodeNext module resolution.

## 1.0.16 - 2026-10-28

### Changed
- Updated the package build toolchain to Vite 8.3.4.

## 1.0.15 - 2026-10-15

### Changed
- Use `addRowLabel` for repeater button labels in client-rendered field contracts.

## 1.0.14 - 2026-10-08

### Changed
- Report browser-module instances and failures through the shared hydration contract.
- Resolve field browser modules through exact manifest entry references rather than inferred capabilities.
- Clarify that client-rendered forms own their markup and that browser-supplied server-rendered theme config is bounded declarative data.
- Share versioned tri-state conditions and plain-text validation rules across PHP and browser consumers; enforce recursive hidden-value clearing and server-authoritative navigation.
- Preserve exact nested submission error paths, return typed page-transition results, and distinguish skipped side effects from invalid condition configuration.
- Consume the shared completion outcome and preserve the server-owned configuration and query-prefill boundary.
- Consume the canonical versioned client-rendered bootstrap and shared browser-module host with explicit request profiles and staged uploads.

### Fixed
- Render Formie-sanitized rich completion messages as HTML instead of showing their markup as text.
- Keep field error regions mounted without reserving empty layout space, respect Formie's announcement preference, and associate invalid controls with their messages. ([#2954](https://github.com/verbb/formie/issues/2954))
- Give default text controls accessible names and focus the first invalid input after submission.

## 1.0.13 - 2026-07-09

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-react).

## 1.0.12 - 2026-07-02

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-react).

## 1.0.11 - 2026-06-26

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-react).

## 1.0.10 - 2026-06-26

### Changed
- Released alongside `@verbb/formie-browser` and `@verbb/formie-core` to keep package versions aligned, including validation message copy parity updates from the browser and core runtimes.

## 1.0.9 - 2026-06-25

### Changed
- Released alongside `@verbb/formie-browser` and `@verbb/formie-core` to keep package versions aligned, including WCAG 2.2 AA theme contrast updates, Ajax-safe tab link theme keys, payment follow-up handling, locale-formatted payment amount parsing, and headless GraphQL payment follow-up fields.

## 1.0.8 - 2026-06-24

### Changed
- Released alongside `@verbb/formie-browser` to keep package versions aligned, including Survey field modules, client event dispatch updates, and Summary theme config fixes from the browser runtime.

## 1.0.7 - 2026-06-18

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-react).

## 1.0.6 - 2026-06-14

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-react).

## 1.0.5 - 2026-06-12

### Changed

- Update definition form rendering for Dropdown, Radio, and Checkboxes option availability states (`visible`, `hidden`, `disabled`).
- Update definition form rendering for table column reference support in Calculations fields.

## 1.0.4 - 2026-06-07

### Changed

- Released alongside `@verbb/formie-browser` 1.0.4 to keep package versions aligned, including validation message override and error-position updates from the browser runtime.

## 1.0.3 - 2026-06-06

### Changed

- Released alongside `@verbb/formie-browser` 1.0.3 to keep package versions aligned, including file-upload persistence and submit-readiness updates from the browser runtime.

## 1.0.2 - 2026-06-03

### Changed

- Released alongside `@verbb/formie-browser` 1.0.2 to keep package versions aligned.

## 1.0.1 - 2026-06-02

### Changed

- Update package homepage and documentation URLs.

## 1.0.0 - 2026-06-02

### Added

- Initial public release of React bindings for Formie, including `FormieProvider`, `FormieForm`, and definition-driven field rendering components.
