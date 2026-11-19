# Changelog

## Unreleased

### Added
- Added `data-formie-field-required="true"` to the wrapper of a required field and `aria-required="true"` to its control (the radio group for Radio fields), so themes can style required fields and screen readers announce them. ([#3139](https://github.com/verbb/formie/issues/3139))
- Added `field` to the props passed to slot components, so a custom label can show its own required marker. ([#3139](https://github.com/verbb/formie/issues/3139))
- Added `inputId`, `labelId` and `instructionsId` to custom `Field` components and field components, so a custom label can point at the field’s control. ([#3140](https://github.com/verbb/formie/issues/3140))
- Added Address Country, Date dropdown parts, Group, Table, Recipients and Section fields, and Entries, Categories and other element fields, which showed “Unsupported field type”. Fields without a control of their own use the `dropdown`, `radio` or `checkboxes` renderer, so a component registered for one of those renders them too. Element fields send a list of IDs from all three. Survey fields with Likert, Rank, Rating or text display types still show “Unsupported field type”.
- Added `role="alert"` to the form error summary and error message, and `role="status"` to the completion message. Focus moves to the first invalid field, then to these messages, and to the new page after a page change.
- Added the page title (`.formie-vue-page-title`) for forms that turn on **Display Current Page Title**, and the progress bar (`.formie-vue-progress`, with `role="progressbar"` and `data-formie-progress-state`) for multi-page forms that turn on **Display Page Progress**, placed and worked out as in server-rendered forms. The page is named by its title. A custom `Page` component renders its own title, using `showsClientPageTitle()` from `@verbb/formie-core`.
- Added `autocomplete` to inputs that have an autocomplete hint, and kept labels in the **Hidden** position in the page, visually hidden, so they still name their control.

### Changed
- Field labels now point at their control with `for` instead of the control repeating the label in `aria-label`, and field instructions describe the control with `aria-describedby`. Controls rendered inside a custom `Field` component keep their `aria-label`. ([#3140](https://github.com/verbb/formie/issues/3140))
- Radio options are now grouped with `role="radiogroup"` and Checkboxes options with `role="group"`, each named by the field label. The Agree checkbox is wrapped in `.formie-vue-agree` named by the field label, and keeps its description as its own label. ([#3140](https://github.com/verbb/formie/issues/3140))
- Name, Address, Date, Group, Repeater and Table fields now group their controls with `role="group"`, named by the field label.
- The file list of a File Upload field now uses `formie-vue-file-list` instead of the field errors class.
- Repeater and Table **Remove** buttons are now named “Remove row 1”, “Remove row 2” and so on, and Repeater, Table and Signature buttons use the text the server translates for the site.

### Fixed
- Fixed Payment fields showing “Unsupported field type”. They now show the provider’s own payment form in `.formie-vue-payment`, where the provider’s browser module mounts, for every payment integration. ([#3146](https://github.com/verbb/formie/issues/3146))
- Fixed Phone fields with **Country Enabled** showing a plain input with no country picker. The number input and a hidden country input are now wrapped in `.formie-vue-phone`, where the Phone country picker attaches, and the chosen country is sent with the number. Typing a number in any Phone field also replaced the answer with the number alone instead of keeping `{ number, country }`. ([#3146](https://github.com/verbb/formie/issues/3146))
- Fixed Calculations fields never showing their answer, and accepting typing, including in Groups and Repeater rows.
- Fixed Dropdown fields showing their first option while sending no answer, and multi-select Dropdown fields not showing their default options as selected.
- Fixed Radio options having no shared `name`, so arrow keys didn’t move between them in some browsers.
- Fixed Repeater errors showing twice.
- Fixed the Signature pad having no accessible name.
- Fixed Repeater row fields with **Visibility** set to **Hidden** showing, as their hidden state was read from the wrong key.

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
- Use `addRowLabel` for the client-rendered Repeater button label.

### Fixed
- Stop the default form error summary from requiring field-only accessibility props.

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
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-vue).

## 1.0.12 - 2026-07-02

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-vue).

## 1.0.11 - 2026-06-26

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-vue).

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
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-vue).

## 1.0.6 - 2026-06-14

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-vue).

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

- Initial public release of Vue bindings for Formie, including composables and definition-driven field rendering components.
