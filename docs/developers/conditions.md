# Conditions

Conditions determine which fields and pages a visitor can use and whether an action, such as a notification or integration, should run. Formie evaluates them on the server before clearing hidden values, validating answers or processing the submission. Browser evaluation gives visitors immediate feedback; a posted field value or page target cannot override the server result.

For checking whether an answer is acceptable and showing errors in the browser, see [Validation](/developers/validation).

## Condition Model

A condition describes a decision, such as whether an enquiry should be sent to a sales notification. A `ConditionRule` describes one comparison: the value to read, the operator to apply and the value to compare it with. A `ConditionSet` groups those rules and specifies whether all of them or any one of them must match.

`ConditionOperator` provides the supported comparisons, such as equality and greater-than. Evaluating a set produces a `ConditionEvaluation`, which tells you whether the rules matched or could not be evaluated. The following example connects these pieces using a form’s submitted values.

### Define a Sales Enquiry Condition

In your local Craft site, create a form with handle `contact`. Add a Dropdown field with handle `enquiryType` and two options: **Sales**, with value `sales`, and **Support**, with value `support`. Save the form. The example compares stored option values, so the handles and values must match those names.

Place the imports below at the top of a PHP file in your Craft module, and the remaining code inside a method you can call locally after Craft and Formie have loaded. The code loads the saved form and creates an in-memory submission containing a sample answer. It does not save that submission or send a notification.

```php
use verbb\formie\conditions\ConditionOperator;
use verbb\formie\conditions\ConditionRule;
use verbb\formie\conditions\ConditionSet;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ConditionsHelper;

$form = Form::find()->handle('contact')->one();

if (!$form || !$form->getFieldByHandle('enquiryType')) {
    throw new \RuntimeException('Create the contact form and enquiryType field first.');
}

$submission = new Submission();
$submission->setForm($form);
$submission->setFieldValue('enquiryType', 'sales');

$salesRule = new ConditionRule(
    reference: 'enquiryType',
    operator: ConditionOperator::EQ,
    value: 'sales',
);

$conditions = new ConditionSet(
    mode: 'all',
    effect: 'show',
    rules: [$salesRule],
    purpose: 'notification',
);

$evaluation = ConditionsHelper::evaluate($conditions, $submission);

$evaluation->value;       // true
$evaluation->permits();   // true
$evaluation->diagnostics; // []
```

The rule’s `reference` identifies the field to read; it can also be a [reference token](/developers/reference-tokens). `ConditionOperator::EQ` selects equality, and `sales` is the expected value. A rule can optionally carry `metadata` for the code consuming it, but this comparison does not need any.

The set’s `mode` is `all`, meaning every rule must match. Use `any` when one matching rule is sufficient. `purpose` identifies the intended use of the set. `effect` describes visibility or enabled-state behaviour when a set is used for UI conditions; `show` is the default effect. For this notification decision, `permits()` checks the evaluated result. Neither the purpose nor the effect sends an email or attaches the set to a saved notification.

`ConditionsHelper::evaluate()` reads the field’s condition value and returns a `ConditionEvaluation`. A `true` value means the comparison matched, and `permits()` allows the operation. In your own notification-related code, use that permission result to decide whether to proceed. To configure an ordinary Formie notification through the control panel, follow [Submission Statuses and Conditional Workflows](/guides/submissions-workflows/submission-statuses-and-conditional-workflows#example-conditional-notifications).

### Check a Non-Matching Answer

Continue in the same method with the form, submission and condition set from the first example. Change the sample answer to `support` and evaluate the set again:

```php
$submission->setFieldValue('enquiryType', 'support');

$evaluation = ConditionsHelper::evaluate($conditions, $submission);

$evaluation->value;         // false
$evaluation->permits();     // false
$evaluation->permits(false); // true
$evaluation->diagnostics;   // []
```

This is a valid comparison that did not match. `permits(false)` allows an operation specifically when the rules do not match—for example, a separate action for non-sales enquiries. Changing the field value through `setFieldValue()` lets Formie invalidate the previous condition result before evaluating again.

### Handle an Invalid Condition

A missing field or unsupported comparison is different from a valid answer that does not match. To see that difference, continue in the same method with a rule referencing `missingEnquiryType`, a field that does not exist on the example form:

```php
$invalidConditions = new ConditionSet(
    mode: 'all',
    effect: 'show',
    rules: [
        new ConditionRule(
            reference: 'missingEnquiryType',
            operator: ConditionOperator::EQ,
            value: 'sales',
        ),
    ],
    purpose: 'notification',
);

$invalid = ConditionsHelper::evaluate($invalidConditions, $submission);

$invalid->value;          // null
$invalid->permits();      // false
$invalid->permits(false); // false
$invalid->diagnostics;    // [['code' => 'unresolvedReference', 'rule' => 0]]
```

The diagnostic identifies the first rule using its zero-based index. Use it to find and correct the configuration problem. An invalid rule makes the set invalid under both `all` and `any`, even if another rule would otherwise match.

Always use `permits(false)` when an operation should run for a non-match. Negating `matches()` would also produce `true` for an invalid condition, allowing the operation despite the configuration error. Use `ConditionsHelper::matchingRules()` separately when selecting recipients from individual matching rules.

## Browser Conditions

`ConditionCompiler` prepares a condition set for JavaScript evaluation. It converts the rules and their field references into a versioned definition containing `mode`, `effect`, `rules` and information about each value’s type and availability in the browser. Both server-rendered markup and client-rendered forms use this format.

To inspect the compiled definition, add this import to the same module file and run the remaining line after the first example, where `$conditions` and `$form` are defined:

```php
use verbb\formie\conditions\ConditionCompiler;

$definition = (new ConditionCompiler())->compile($conditions, $form);
```

For the example form, the first compiled rule identifies `enquiryType`, the equality operator and the comparison value `sales`. Compilation prepares the definition; it does not evaluate the visitor’s answer or make a permission decision. Formie supplies compiled conditions to its own renderers, so ordinary field and notification setup does not require calling the compiler yourself.

The operator schema in `src/conditions/schema.json` generates the core browser package’s schema during its build. Fields expose the operators supported by their condition values. Arbitrary PHP-only operators are not supported. If a reference cannot be evaluated in the browser, its result remains unknown there and the server makes the decision.

## Values and Operators

Conditions compare the values returned by each field's condition method, resolving reference tokens where needed. If a reference is unavailable in the browser, its result stays unknown there and the server makes the decision.

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

### Example: Comparing Text and Numbers

Suppose a visitor enters `10` and a rule checks whether the value is greater than `2`. A Number field compares their numeric values, so the rule matches. A text field compares characters in order, so `10` sorts before `2` and the rule does not match.

You can inspect these individual comparisons in a method in your Craft module using `ConditionOperator::evaluate()`. Its arguments are the operator, actual value, comparison value and value type. Each call returns a `ConditionEvaluation`; the `value` property is `true`, `false` or `null` when the comparison is invalid.

```php
use verbb\formie\conditions\ConditionOperator;

$number = ConditionOperator::evaluate(ConditionOperator::GT, '10', '2', 'number');
$text = ConditionOperator::evaluate(ConditionOperator::GT, '10', '2', 'text');
$invalid = ConditionOperator::evaluate(ConditionOperator::GT, '12px', '2', 'number');

$number->value;  // true
$text->value;    // false
$invalid->value; // null: "12px" is not a complete numeric value.
```

The explicit types here illustrate the comparison rules. When evaluating a submission through `ConditionsHelper`, Formie uses the field’s condition value and type. Use a Number field when the comparison needs numeric ordering.

### Example: Matching Selected Options

Suppose a Checkboxes field has selected option values `email` and `sms`. A **contains** condition matches `email`, but not `mail`: collections compare complete option values. A text **contains** condition can match that same substring.

In a module method, the corresponding comparisons are:

```php
use verbb\formie\conditions\ConditionOperator;

$selected = ['email', 'sms'];

$exact = ConditionOperator::evaluate(ConditionOperator::CONTAINS, $selected, 'email', 'collection');
$partial = ConditionOperator::evaluate(ConditionOperator::CONTAINS, $selected, 'mail', 'collection');
$substring = ConditionOperator::evaluate(ConditionOperator::CONTAINS, 'email', 'mail', 'text');

$exact->value;     // true
$partial->value;   // false
$substring->value; // true
```

Use the stored option value in the comparison, such as `email`, even if the editor-facing label is **Email Updates**.

## Visibility and Navigation

New visibility rules select preceding fields, previous pages or preceding siblings. Button progression can reference the entire current page. Compatible legacy forward references are ordered by dependency; dependency cycles produce save-time and runtime diagnostics.

After applying initial, default, forced and submitted values, the server evaluates visibility and clears hidden or disabled values recursively, including repeater children. Public requests cannot opt out. Trusted control-panel follow-conditions and muted modes also clear values; explicit show-all mode may preserve hidden values.

Preflight then enforces progression rules and resolves the visible-page transition. Validate uses that transition to check the applicable visible fields before any page advancement is persisted. A target cannot skip an unvalidated page. Rejected forward navigation stays on the current page with a form-level error. Routing and validation errors halt before CAPTCHA, content-spam screening and persistence. Request-integrity and abuse checks remain outside that workflow boundary.

Validation uses the resolved transition to choose the current page or the complete submission. See [Validation](/developers/validation#when-validation-runs) for operation-specific scopes, custom rules and error handling.
