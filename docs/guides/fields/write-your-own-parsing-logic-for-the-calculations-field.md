# Write your own parsing logic for the Calculations field

The Calculations field evaluates a formula from other field values and shows the result in a read-only input. Formie uses a JavaScript implementation of Symfony Expression Syntax for front-end evaluation. When built-in formatting is not enough — currency display, rounding rules, capping input values before they enter the formula — you can hook into evaluation events and adjust the formula, variables, or result.

## Prerequisites

- A form with at least one [Calculations field](/fields/calculations)
- Formie's front-end assets loaded on the page (included automatically when you use `craft.formie.renderForm()`)

## How evaluation events work

Formie fires two document-level events around each evaluation:

| Event | When it fires | What you can change |
| --- | --- | --- |
| `formie:field:calculations:before-evaluate` | Before the formula runs | `event.detail.formula`, `event.detail.variables` |
| `formie:field:calculations:after-evaluate` | After the formula runs, before the input updates | `event.detail.result` |

Each event's `detail` includes a reference to the Calculations field element. Match the field you care about with `dataset.formieInputId` (the field handle) or `dataset.formieFieldHandle`.

Variables in `event.detail.variables` use keys derived from the referenced fields — typically the field handle or the internal variable name Formie generated for the formula.

## Format results as currency

Suppose you have two Number fields and a Calculations field with the formula `{fieldA} + {fieldB}`. You want the result shown as USD.

Add this script on the page where the form renders:

```javascript
document.addEventListener('formie:field:calculations:after-evaluate', (event) => {
    const { calculations, result } = event.detail;

    if (calculations?.dataset.formieInputId !== 'result') {
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

The field shows `$0.00` on load (rather than `0`) and updates as currency when dependent fields change.

## Round numeric results

The same pattern works for rounding:

```javascript
document.addEventListener('formie:field:calculations:after-evaluate', (event) => {
    const { calculations, result } = event.detail;

    if (calculations?.dataset.formieInputId !== 'result') {
        return;
    }

    if (typeof result === 'number') {
        event.detail.result = Math.round(result);
    }
});
```

Given `1.276` and `7.682` for the two Number fields, the displayed result becomes `9`.

## Cap values before evaluation

Use `before-evaluate` when you want to clamp or transform source values without changing what the user typed in the original fields.

If `fieldA` should contribute at most `20` to the calculation:

```javascript
document.addEventListener('formie:field:calculations:before-evaluate', (event) => {
    const { calculations, variables } = event.detail;

    if (calculations?.dataset.formieInputId !== 'result') {
        return;
    }

    // Variable keys match what Formie generated for your formula — inspect
    // event.detail.variables in the browser console if unsure.
    Object.keys(variables).forEach((key) => {
        if (key.includes('fieldA') && variables[key] > 20) {
            variables[key] = 20;
        }
    });
});
```

The user can still enter values above 20 in the Number field; only the calculation respects the cap.

## Advanced: modify the formula or inject functions

You can replace the formula string or add custom variable objects (including functions) before evaluation:

```javascript
document.addEventListener('formie:field:calculations:before-evaluate', (event) => {
    const { calculations, variables } = event.detail;

    if (calculations?.dataset.formieInputId !== 'result') {
        return;
    }

    event.detail.formula = 'fieldA + fieldB + suffix.myMethod("🔥")';

    variables.suffix = {
        myMethod(string) {
            return ' is ' + string;
        },
    };
});
```

Given `1` and `5`, the result becomes `6 is 🔥`.

## Tips

- Use the **after-evaluate** event for display formatting. Use **before-evaluate** for logic that affects what the expression sees.
- Prefer the Calculations field's built-in prefix, suffix, and decimal settings when they are enough. Custom events are for behaviour the field settings cannot express.
- For formula syntax and built-in functions, see [Calculations field in detail](/guides/fields/calculations-field-in-detail).

## Related

- [Calculations field](/fields/calculations)
- [Calculations field in detail](/guides/fields/calculations-field-in-detail)
- [Calculations module events](/browser/modules/field/calculations)
