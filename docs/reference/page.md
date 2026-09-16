# Page

A Page object represents one page in a form. Single-page forms still have a page object; multi-page forms just make the page sequence more visible.

For a saved form with the handle `contactForm`, retrieve its pages and inspect their labels in Twig:

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}
{% if form %}
    {% for page in form.getPages() %}
        <h2>{{ page.label }}</h2>
    {% endfor %}
{% endif %}
```

Each loop item is a Page. Use its methods below when you need the fields or conditions belonging to that page.

## Properties

::: reference
### `label`

**Type:** `string|null`

The page label.
:::

::: reference
### `handle`

**Type:** `string|null`

The page handle, derived from the label.
:::

::: reference
### `sortOrder`

**Type:** `int|null`

The page order within the form.
:::

::: reference
### `settings`

**Type:** `array`

The page settings, including page button labels and layout options.
:::


## Methods

::: reference
### `getRows()`

**Returns:** `array`

Returns the row objects on this page.
:::

::: reference
### `getFields()`

**Returns:** `array`

Returns the [Field](/reference/field) objects on this page.
:::

::: reference
### `getFieldByHandle()`

**Returns:** `FieldInterface|null`

Pass the required field handle, for example `page.getFieldByHandle('email')`. Returns a field on this page or `null` when it is absent.
:::

::: reference
### `isConditionallyHidden()`

**Returns:** `bool`

Pass the submission being evaluated, for example `page.isConditionallyHidden(submission)`. Returns whether the page is hidden for that submission because of conditions.
:::

::: reference
### `hasConditions()`

**Returns:** `bool`

Returns whether the page has conditions configured.
:::

::: reference
### `getConditions()`

**Returns:** `array`

Returns the page conditions.
:::

::: reference
### `getClientConditions()`

**Returns:** `array`

Returns the conditions in the shape used by Formie’s front-end handling.
:::

::: reference
### `getFieldErrors()`

**Returns:** `array`

Pass the submission being checked, for example `page.getFieldErrors(submission)`. Returns its field errors for this page; the argument may be `null`.
:::
