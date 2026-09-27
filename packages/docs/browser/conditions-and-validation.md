# Conditions and Validation

All Formie browser packages share condition and validation rules through `@verbb/formie-core`. Use the `mode`, `effect` and `rules` definitions returned by Formie. Text comparisons use alphabetical order; numeric comparisons require a complete number; selected options match whole values. An invalid show rule keeps a field hidden, an invalid hide rule keeps it visible, and an invalid navigation rule prevents moving forward.

Server validation remains authoritative. Submit and page-transition results retain full nested errors such as `123.1.email`, rooted at the form-field instance ID. Render messages as plain text and focus the exact control. Derive page summaries from field errors and the current definition.

REST returns 422 validation, 409 stale state, 429 rate limits and 403 authorization. GraphQL submit and page mutations return expected domain failures in result data. A rejected page transition leaves the current page unchanged. Use the returned session when present.

Custom browser modules can add validation with `registerBrowserValidationRule(type, rule)` and remove it using the returned cleanup function. Formie supplies translated error messages and checks submissions again on the server, including rules the browser does not recognise.
