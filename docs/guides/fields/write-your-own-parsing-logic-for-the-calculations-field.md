# Write Your Own Parsing Logic for the Calculations Field

The Calculations field evaluates a formula from other field values and shows the result in a read-only input. Formie uses a JavaScript implementation of Symfony Expression Syntax for front-end evaluation. When built-in formatting is not enough — currency display, rounding rules, capping input values before they enter the formula — you can hook into evaluation events and adjust the formula, variables, or result.

The [Calculations module events](https://docs.verbb.io/formie/browser/modules/field/calculations) describe the browser hooks used to extend calculation behaviour.

## Prerequisites

- A form with at least one [Calculations field](/fields/calculations)
- Formie's front-end assets loaded on the page (included automatically when you use `craft.formie.renderForm()`)

## How Evaluation Events Work

Formie fires two document-level events around each evaluation:

| Event | When it fires | What you can change |
| --- | --- | --- |
| `formie:field:calculations:before-evaluate` | Before the formula runs | `event.detail.formula`, `event.detail.variables` |
| `formie:field:calculations:after-evaluate` | After the formula runs, before the input updates | `event.detail.result` |

Each event is dispatched on the Calculations field's container, and its `detail` includes the field's read-only input as `calculations`. Match the field you care about with `event.target.dataset.formieFieldHandle`, which holds the field handle. For a field inside a Group or Repeater it holds the field's path instead, such as `myGroup.result` or `items.0.result`. The input's `data-formie-input-id` combines the form and field handles, so to target one form on a page with several, compare `calculations.dataset.formieInputId` with that combined value, such as `contactForm-result`.

Formie names each formula variable after the field it refers to, with a `field_` prefix. A formula of `{fieldA} + {fieldB}` reaches these events as `field_fieldA + field_fieldB`, and `event.detail.variables` uses the same keys. Nested fields and handles that would clash get longer names, so inspect `event.detail.formula` in the browser console if you're unsure.

## Format Results as Currency

Suppose you have two Number fields and a Calculations field with the formula `{fieldA} + {fieldB}`. You want the result shown as USD.

Add this script on the page where the form renders:

```javascript
document.addEventListener('formie:field:calculations:after-evaluate', (event) => {
    const { result } = event.detail;

    if (event.target.dataset.formieFieldHandle !== 'result') {
        return;
    }

    if (typeof result !== 'number') {
        return;
    }

    event.detail.result = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(result);
});
```

Once both Number fields have values, the field shows the result as currency, such as `$6.00` for `1` and `5`. While either field is blank the result isn't a number, so the example leaves it unformatted.

## Round Numeric Results

The same pattern works for rounding:

```javascript
document.addEventListener('formie:field:calculations:after-evaluate', (event) => {
    const { result } = event.detail;

    if (event.target.dataset.formieFieldHandle !== 'result') {
        return;
    }

    if (typeof result === 'number') {
        event.detail.result = Math.round(result);
    }
});
```

Given `1.276` and `7.682` for the two Number fields, the displayed result becomes `9`.

## Cap Values Before Evaluation

Use `before-evaluate` when you want to clamp or transform source values without changing what the user typed in the original fields.

If `fieldA` should contribute at most `20` to the calculation:

```javascript
document.addEventListener('formie:field:calculations:before-evaluate', (event) => {
    const { variables } = event.detail;

    if (event.target.dataset.formieFieldHandle !== 'result') {
        return;
    }

    // {fieldA} in the formula is available as field_fieldA.
    if (variables.field_fieldA > 20) {
        variables.field_fieldA = 20;
    }
});
```

The user can still enter values above 20 in the Number field; only the calculation respects the cap.

## Advanced: Modify the Formula or Inject Functions

You can replace the formula string or add custom variable objects (including functions) before evaluation. A replacement formula refers to fields by their variable names, such as `field_fieldA`:

```javascript
document.addEventListener('formie:field:calculations:before-evaluate', (event) => {
    const { variables } = event.detail;

    if (event.target.dataset.formieFieldHandle !== 'result') {
        return;
    }

    event.detail.formula = 'field_fieldA + field_fieldB + suffix.myMethod("🔥")';

    variables.suffix = {
        myMethod(string) {
            return ' is ' + string;
        },
    };
});
```

Given `1` and `5`, the result becomes `6 is 🔥`.

## Calculations That Set a Payment Amount

When a Calculations field sets a Payment field's amount, Formie works out every Calculations field in the form again on the server before charging, and refuses the payment if a submitted answer is different. It checks them all because conditions on the server read their answers. The server doesn't run these browser events. Formatting a result for display, such as adding a currency symbol, is fine. Rounding, capping or replacing the formula changes the answer, so use the field's **Formatting** and **Decimal Rounding** settings in those forms, or write the rule into the formula itself.

## Tips

- Use the **after-evaluate** event for display formatting. Use **before-evaluate** for logic that affects what the expression sees.
- Prefer the Calculations field's built-in prefix, suffix, and decimal settings when they are enough. Custom events are for behaviour the field settings cannot express.
- For formula syntax and built-in functions, see [Calculations field in detail](/guides/fields/calculations-field-in-detail).
