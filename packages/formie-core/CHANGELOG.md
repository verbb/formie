# Changelog

## Unreleased

## 1.0.10 - 2026-06-26

### Changed
- Align headless client-side validation messages with server-side Formie copy, including field labels in required, email, number, min/max, URL, and match validation errors. ([#2907](https://github.com/verbb/formie/issues/2907))

## 1.0.9 - 2026-06-25

### Added
- Extend `FrontendSubmitResult` and `submitFormieClientForm` GraphQL selections with payment follow-up fields (`paymentStatus`, `paymentMessage`, `paymentRedirectUrl`, `paymentAction`, `paymentDecision`, `keepSubmitLoading`), plus `quizResult` and `clientEvents`. ([#1375](https://github.com/verbb/formie/issues/1375))

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
