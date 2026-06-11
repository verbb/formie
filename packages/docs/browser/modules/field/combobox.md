# Combobox

Combobox enhances Dropdown fields and other dropdown display types with a filterable select UI powered by [Tom Select](https://tom-select.js.org/). The native `<select>` remains in the DOM for submission, validation, conditions and calculations.

## When it loads

Formie adds the `combobox` module to a form’s client module manifest when **Use searchable dropdown** is enabled and the field renders a `<select>`:

- [Dropdown](/fields/dropdown)
- Element relation fields when **Display type** is **Dropdown** ([Entries](/fields/entries), [Categories](/fields/categories), [Users](/fields/users), [Products](/fields/products), [Variants](/fields/variants))
- [Recipients](/fields/recipients) when shown as a dropdown

## Markup

Searchable dropdowns output `data-formie-combobox-input` on the native `<select>`. Tom Select wraps the control at runtime and adds classes such as `tomselected`, `ts-hidden-accessible`, and `formie-combobox`.

## Events

#### The `formie:field:combobox:before-init` event

Triggered before Tom Select is created.

```js
document.addEventListener('formie:field:combobox:before-init', (event) => {
  event.detail.options.placeholder = 'Choose one…';
});
```

#### The `formie:field:combobox:after-init` event

Triggered after Tom Select has been mounted on the field.

```js
document.addEventListener('formie:field:combobox:after-init', (event) => {
  const combobox = event.detail.combobox;

  console.log('Combobox ready', combobox);
});
```

The shared module lifecycle also exposes scoped events such as `formie:module:combobox:init` and `formie:module:combobox:destroy`.

## Related pages

- [Dropdown field](/fields/dropdown)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
