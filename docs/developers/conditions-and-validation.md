# Conditions and Validation

Formie evaluates conditions on the server before it clears hidden values, validates fields, chooses a page, saves a submission or sends notifications and integrations. Browser evaluation gives visitors immediate feedback. A posted field value or page target cannot override the server result.

## Condition Model

`ConditionSet` holds grouping, effect and purpose. Each `ConditionRule` holds a reference, operator, comparison value and metadata. `ConditionOperator` owns Formie's closed operator registry. `ConditionEvaluation` contains `true`, `false` or `null` for invalid evaluation, with per-rule diagnostic codes.

`ConditionCompiler` produces version 1 `mode` / `effect` / `rules` definitions for both server-rendered markup and client-rendered forms. The operator schema in `src/conditions/schema.json` generates the core browser package's schema during its build. Fields expose the operators supported by their condition projection. Arbitrary PHP-only operators are not supported.

```php
use verbb\formie\helpers\ConditionsHelper;

$evaluation = ConditionsHelper::evaluate($settings, $submission, 'notification');
if ($evaluation->permits()) {
    // Perform the permitted operation.
}
```

Always use `permits(false)` for an inverted permission. Negating `matches()` would turn an invalid condition into permission. Use `matchingRules()` separately when selecting recipients.

## Values and Operators

Operators consume normalized values through the field's condition projection and the reference runtime. References unavailable in a browser remain server-authoritative; browsers retain an unknown result instead of inventing a value.

| Value | Semantics |
| --- | --- |
| Text | Case-sensitive exact equality and substring matching. Greater/less comparisons use Unicode lexical order: text `10` sorts before `2`. |
| Number | A complete finite decimal or exponent value, optionally signed. Numeric `10` is greater than `2`. Prefix parsing such as `12px` is invalid. |
| Boolean | Boolean values and explicit `true/false`, `yes/no`, `on/off`, `1/0` aliases. |
| Scalar text conversion | Null becomes an empty string, booleans become `true`/`false`, and finite numbers use browser-compatible round-trip precision and fixed/scientific notation. Numeric and boolean parsing trims only ASCII whitespace; embedded NUL is invalid. |
| Empty | Null, an empty list, or an ASCII-whitespace-only string. Zero and false are not empty. |
| Date | Complete ISO `YYYY-MM-DD` or normalized year/month/day parts. Impossible dates are invalid. |
| Time | `HH:mm[:ss]` or normalized hour/minute/second parts, with optional AM/PM. |
| Date/time | ISO with an explicit UTC offset, or complete normalized parts with a timezone (UTC by default). DST gaps and ambiguous local times are invalid; supply an explicit offset for an ambiguous time. |
| Options and relations | Stable projected option values and relation IDs. Collection equality and contains test exact membership; they do not search substrings inside an item. |
| Nested collections | Membership traverses projected leaf values. Unsupported object values, operators and unresolved references produce an invalid evaluation. |

Invalid rules propagate through both `all` and `any`, with their diagnostics retained. An invalid show condition keeps its field hidden; an invalid hide condition keeps it visible. Enable/disable effects keep controls visible while changing their enabled state; an invalid enable rule disables its control, and an invalid disable rule leaves it enabled. Invalid navigation and side-effect conditions deny the operation regardless of inversion. False side-effect conditions produce a skipped result; invalid configuration produces a rejected result.

## Visibility and Navigation

New visibility rules select preceding fields, previous pages or preceding siblings. Button progression can reference the entire current page. Compatible legacy forward references are ordered by dependency; dependency cycles produce save-time and runtime diagnostics.

After applying initial, default, forced and submitted values, the server evaluates visibility and clears hidden or disabled values recursively, including repeater children. Public requests cannot opt out. Trusted control-panel follow-conditions and muted modes also clear values; explicit show-all mode may preserve hidden values.

Preflight then enforces progression rules and resolves the visible-page transition. Validate uses that transition to check the applicable visible fields before any page advancement is persisted. A target cannot skip an unvalidated page. Rejected forward navigation stays on the current page with a form-level error. Routing and validation errors halt before CAPTCHA, content-spam screening and persistence. Request-integrity and abuse checks remain outside that workflow boundary.

| Operation | Validation scope |
| --- | --- |
| Back, Save Draft | None |
| Advance | Current visible page |
| Completion | All visible pages |
| Control panel, Revise | Complete submission, subject to the trusted visibility policy |

## Submission Errors

`$submission->getSubmissionErrors()` normalizes Yii/Craft errors once into form-local identity and full nested paths. `toClient()` returns `form` and `fields`; a field key is `<field-instance-id>.<nested-path>`, such as `123.1.email`. It never uses a shared field definition ID. `toLegacy()` returns full handle paths such as `people.1.email` for server-rendered HTML and AJAX responses. `getErrors()` returns the underlying Yii/Craft errors.

Messages are plain text. Rich completion/outcome messages are a separate contract. CAPTCHA, spam and provider diagnostics are private. `forPage()` and `firstPageId()` derive summaries from canonical errors and the current layout; there is no persisted page-error map.

REST-style actions return 422 for validation/progression errors, 409 for stale state, 429 for rate limits and 403 for authorization. Interactive GraphQL submit/page mutations return expected domain failures in result data, including `httpStatus`; GraphQL protocol errors remain separate. Handle-based AJAX responses may return HTTP 200 with validation errors in the response body. Page reload rerenders the submitted form and its exact controls.

## Browser Validation

`@verbb/formie-core` owns pure value validation through `validateBrowserValue()`. The DOM package adapts controls to values, and React, Vue and Web Components use the same rules. PHP's `browserValidationRules()` supplies resolved localized messages. Specialist modules can register rules with `registerBrowserValidationRule()`; unknown rules defer to server validation.

Use `getBrowserValidationRulesJson()` when emitting rules into custom markup. Treat browser validation as feedback only. Preserve full error paths when rendering results and focus the exact child control instead of collapsing errors onto a Repeater or Group root.
