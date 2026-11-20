# Changelog

## Unreleased

### Added
- Added the `password` browser validation rule, with `min`, `uppercase`, `lowercase` and `specialCharacter`, which Password fields now send. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `addressPartMarker()`, `isAddressAutocompletePart()`, `addressAutocompleteMarkup()`, `readAddressParts()` and `isAddressField()`, and the client definition’s `input.currentLocationLabel` on the Address auto-complete part. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `uploadManagerMarkup()`, `readUploadManagerEntries()` and `fieldContentPath()` for File Upload fields using the Upload Manager. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `isSearchableDropdown()`, `searchableDropdownMarkup()` and `searchableDropdownValue()` for fields with **Use Searchable Dropdown**. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `isDatePickerField()`, `datePickerInputValue()`, `datePickerValue()`, `datePickerParts()`, `isDateRangePickerField()`, `dateRangePickerMarkup()` and `dateRangePickerValue()`, and the client definition’s `input.displayType` for Date fields. A date picker’s answer is `{ datetime }`, and a range’s is `{ startDatetime, endDatetime }`, as server-rendered date pickers post them, and conditions compare it by its parts. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `isRichTextField()` and the client definition’s `input.useRichText`. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `checkboxToggle()`, `allOptionsSelected()` and `toggleAllOptions()`, and the client definition’s `input.toggleCheckbox` and `input.toggleCheckboxLabel`, for the “Select all” checkbox. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `summaryFieldMarkup()` and `summaryTokens` on submit results, which client-rendered Summary fields use to show the answers saved so far. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `surveyLikertRows()`, `surveyRankOrder()`, `moveListItem()` and `attachPointerReorder()` for Survey fields, and the client definition’s `input.displayType`, `input.likertRows`, `input.starColor` and `settings.strings.moveUp`/`moveDown`. `resolveClientRendererType()` now picks a Survey field’s control from its display type: `survey-likert`, `survey-rank`, `survey-rating`, or the matching dropdown, radio, checkboxes or text control. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `paymentFieldMarkup()`, which returns the provider markup a client-rendered Payment field shows. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `phoneFieldValue()`, `readPhoneFieldControls()`, `isPhoneCountryEnabled()` and `PHONE_COUNTRY_CHANGE_EVENT` for rendering Phone fields and their country picker. ([#3146](https://github.com/verbb/formie/issues/3146))
- Added `getClientFieldIds()`, `getClientFieldLabelTarget()` and `joinIdReferences()` for linking a field’s label, instructions and errors to its control. ([#3140](https://github.com/verbb/formie/issues/3140))
- Added `projectElementConditionValues()`, which turns selected element IDs into the titles or URLs a condition compares.
- Added `resolveClientRendererType()`, which picks the default control for fields without a built-in type, such as Recipients, Entries, Calculations and Address or Date parts, from their field kind.
- Added `fieldOptions()`, `dropdownOptions()`, `isArrayValuedField()`, `tableColumns()`, `createTableRowValue()` and `isCalculationField()` for rendering option, element and Table fields.
- Added `deriveCalculationValues()`, and client form instances now work out Calculations fields from the form values, including those in Groups and Repeater rows and Repeater and Table row scopes. In a Repeater row, a source from the same Repeater is read from the calculation’s own row.
- Added `getClientPageProgress()` and `showsClientPageTitle()`, and the client definition’s `settings.progress.display`, `position` and `valuePosition` and `settings.displayCurrentPageTitle`.
- Added `parseRowsExpression()` for the row numbers a calculation picks.
- Added `getClientString()`, `isFieldLabelVisuallyHidden()`, `focusClientFormResult()`, `VISUALLY_HIDDEN_STYLE` and `VISUALLY_HIDDEN_CSS` for translated button text, Hidden label positions and focus after a submit or page change.

### Changed
- `isCompositeField()` now includes Group fields, which have parts like Name, Address and Date.
- `getClientFieldLabelTarget()` now returns `group` for Name, Address, Date, Group, Repeater and Table fields, and `none` for Section fields.
- A single-choice dropdown without an empty option now starts on its first option, as server-rendered forms submit it. Element fields still start empty. Composite parts the server sends no answer for start from their own default.
- `isMultiValueField()` now includes element fields, which keep a list of IDs whichever control they use, and Checkboxes-style fields such as Recipients.
- Table fields start with their minimum number of rows when they have no default rows, and a select cell without one of its options starts on the first option.
- `isFieldLabelHidden()` now hides the label of Section fields.

### Fixed
- Fixed Hidden fields with a cookie, query parameter, current URL or referring URL value source not reading it from the visitor’s page in `defaultValueForField()`. ([#3146](https://github.com/verbb/formie/issues/3146))
- Fixed labels pointing at a control that doesn’t exist for Survey Likert, Rank and Rating fields, and Payment and Summary fields, in `getClientFieldLabelTarget()`. ([#3146](https://github.com/verbb/formie/issues/3146))
- Fixed a required Likert scale with multiple rows counting as answered once one row was. ([#3146](https://github.com/verbb/formie/issues/3146))
- Fixed a required Phone field with a country but no number counting as answered. ([#3146](https://github.com/verbb/formie/issues/3146))
- Fixed client-rendered conditions on an element field’s **Title** or **Public URL** never matching. ([#3133](https://github.com/verbb/formie/issues/3133))

## 4.0.10 - 2026-11-17

### Fixed
- Fixed `=` and `!=` conditions comparing a Number field with an empty value never matching.

## 4.0.9 - 2026-11-13

### Added
- Added `displayFieldContent()` and `isFieldLabelHidden()` for rendering Rich Text, HTML and Heading fields in client-rendered forms. ([#3116](https://github.com/verbb/formie/issues/3116))

### Fixed
- Fixed Repeater fields in client-rendered forms starting empty instead of showing the rows sent by the server, such as prefilled values. ([#3115](https://github.com/verbb/formie/issues/3115))

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

### Fixed
- Fixed calculations using formatted calculation values as numeric inputs.

## 1.0.15 - 2026-10-15

### Changed
- Share browser condition and validation types across rendering products.
- Require canonical client-rendered field metadata instead of accepting obsolete beta runtime definitions.

## 1.0.14 - 2026-10-08

### Added
- Add the shared, versioned reference grammar and typed browser source diagnostics.

### Changed
- Expose one canonical payment decision across REST and GraphQL instead of parallel beta response fields.
- Keep numeric condition semantics separate from decimal-string runtime field types.
- Version browser-module manifests around one explicit surface, discriminated targets and configured occurrence keys without decorative capabilities.
- Share versioned tri-state conditions and plain-text validation rules across PHP and browser consumers; enforce recursive hidden-value clearing and server-authoritative navigation.
- Preserve exact nested submission error paths, return typed page-transition results, and distinguish skipped side effects from invalid condition configuration.
- Consume the shared completion outcome and preserve the server-owned configuration and query-prefill boundary.
- Version the client-rendered bootstrap and module contract, enforce explicit request profiles, stage selected files and expose shared browser-module host seams.
- Describe only non-null normalized field types because `null` is the universal absent value, and keep browser-input defaults separate from public data projections.
- Support browser-bound progress, purpose-bound grant exchange, revision sessions and portable Save & Continue responses across REST and GraphQL. Clear exchanged tokens from browser history, issue browser-bound upload-creation capabilities and preserve structured upload attachment references.
- Carry submission versions and typed outcomes through REST and GraphQL; return expected rejection and conflict payloads to renderers.

### Fixed
- Expose Formie's field-error announcement preference and stable, form-scoped error-region IDs to definition-driven renderers. ([#2954](https://github.com/verbb/formie/issues/2954))
- Read the field structure metadata emitted by current Craft client bootstrap responses.

## 1.0.13 - 2026-07-09

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-core).

## 1.0.12 - 2026-07-02

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-core).

## 1.0.11 - 2026-06-26

### Changed
- Send CSRF tokens from `session.tokens.csrf` on client REST `submit`, `refreshSession`, and `setPage` requests when guest CSRF validation is enabled on the server.

## 1.0.10 - 2026-06-26

### Changed
- Align headless client-side validation messages with server-side Formie copy, including field labels in required, email, number, min/max, URL, and match validation errors. ([#2907](https://github.com/verbb/formie/issues/2907))

## 1.0.9 - 2026-06-25

### Added
- Extend `ClientSubmitResult` and `submitFormieClientForm` GraphQL selections with payment follow-up fields (`paymentStatus`, `paymentMessage`, `paymentRedirectUrl`, `paymentAction`, `paymentDecision`, `keepSubmitLoading`), plus `quizResult` and `clientEvents`. ([#1375](https://github.com/verbb/formie/issues/1375))

## 1.0.8 - 2026-06-24

### Changed
- Released alongside `@verbb/formie-browser` to keep package versions aligned.

## 1.0.7 - 2026-06-18

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-core).

## 1.0.6 - 2026-06-14

### Changed
- Released alongside the other `@verbb/formie-*` packages to keep versions aligned (@verbb/formie-core).

## 1.0.5 - 2026-06-12

### Added

- Add `date-parts-validation` utilities for Date/Time text input and dropdown display types, rejecting impossible dates and enforcing min/max date settings.
- Add table column reference support to the calculations resolver for Calculations field expressions.

## 1.0.4 - 2026-06-07

### Changed

- Released alongside `@verbb/formie-browser` 1.0.4 to keep package versions aligned.

## 1.0.3 - 2026-06-06

### Changed

- Released alongside `@verbb/formie-browser` 1.0.3 to keep package versions aligned.

## 1.0.2 - 2026-06-03

### Changed

- Released alongside `@verbb/formie-browser` 1.0.2 to keep package versions aligned.

## 1.0.1 - 2026-06-02

### Changed

- Update package homepage and documentation URLs.

## 1.0.0 - 2026-06-02

### Added

- Initial public release of the headless Formie core runtime, including form instance management, field definitions, validation orchestration, and calculations utilities.
