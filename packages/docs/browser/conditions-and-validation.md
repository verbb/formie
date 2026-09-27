# Conditions and Validation

All Formie browser packages use the condition and pure validation engines in `@verbb/formie-core`. Conditions use the versioned `mode`, `effect` and `rules` wire definition emitted by PHP. Text ordering is lexical, numeric ordering requires a complete finite number, and collection operators match complete projected items. Invalid rules stay unknown: show rules hide, hide rules remain visible, and progression is denied.

Server validation remains authoritative. Submit and page-transition results retain full nested errors such as `123.1.email`, rooted at the form-field instance ID. Render messages as plain text and focus the exact control. Derive page summaries from field errors and the current definition; `errors.pages` has been removed.

REST returns 422 validation, 409 stale state, 429 rate limits and 403 authorization. GraphQL submit and page mutations return expected domain failures in result data. A rejected page transition leaves the current page unchanged. Use the returned session when present.

Custom browser modules can register specialist value rules using `registerBrowserValidationRule(type, rule)` and remove them using the returned cleanup function. PHP supplies localized rule messages; unknown rules defer to server authority. Browser checks never authorize persistence, navigation or side effects.
