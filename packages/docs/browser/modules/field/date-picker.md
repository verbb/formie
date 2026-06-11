# Date picker

Date picker enhances Date fields rendered with the `datePicker` display type by mounting flatpickr on the transport input.

When the field's **Value type** is **Date Range**, the module mounts flatpickr in `range` mode and keeps hidden start/end transport inputs in sync with the selected range.

## Events

#### The `formie:field:date-picker:before-init` event

Triggered before flatpickr is created.

```js
document.addEventListener('formie:field:date-picker:before-init', (event) => {
  // Show a friendlier visible value while keeping the submitted format stable.
  event.detail.options.altInput = true;
  event.detail.options.altFormat = 'F j, Y';
});
```

#### The `formie:field:date-picker:after-init` event

Triggered after flatpickr has been mounted on the field input.

```js
document.addEventListener('formie:field:date-picker:after-init', (event) => {
  const visibleInput = event.detail.datepicker?.altInput;

  if (visibleInput instanceof HTMLInputElement) {
    visibleInput.setAttribute('data-datepicker-ready', 'true');
  }
});
```

The shared module lifecycle also exposes scoped events such as `formie:module:date-picker:after-setup`.

## Related pages

- [Date field](/browser/ui-reference/fields/date)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
