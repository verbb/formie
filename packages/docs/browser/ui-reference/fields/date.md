# Date

Date fields support four display types: `calendar`, `datePicker`, `dropdowns`, and `inputs`. Each one can collect a date, a time, or both.

Use this page to see the default markup for each display type and preserve the extra attribute used by the `date-picker` module.

## Preview

<FormiePreview src="../examples/date.preview.ts" />

## Display types

Date can render as:

- `calendar` for native date/time controls
- `datePicker` for the flatpickr-enhanced picker
- `dropdowns` for split select-based date/time parts
- `inputs` for split text-input date/time parts

Across those display types, the field can be configured as:

- date only
- time only
- date + time

When **Display type** is `datePicker`, the field can also collect a **date range** (`collectMode: range`). Range fields render a single Flatpickr input in range mode and submit hidden start/end transport inputs.

## Attributes

Date fields can render as one input or as several subfields, depending on the display type.

### Field

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation, conditions, calculations, and error rendering | Required |
| `data-formie-field-type="date"` | Field identity marker on the outer wrapper | Recommended |

### Field input

| Attribute | Description | Importance |
| --- | --- | --- |
| `name` | Submission payload key for `datetime` or individual date/time parts | Required |
| `data-formie-input` | Generic Formie input marker included in normal output | Recommended |
| `data-formie-input-id` | Stable input identity for the rendered control | Recommended |

### Date-picker input

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-date-datepicker-input` | Picker selector used by the `date-picker` module | Required for `datePicker` |

### Date-range inputs

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-date-range-start-input` | Hidden transport input for the range start value | Required for `datePicker` range fields |
| `data-formie-date-range-end-input` | Hidden transport input for the range end value | Required for `datePicker` range fields |
| `data-formie-date-range-input` | Optional marker on the visible picker input when range mode is active | Recommended for `datePicker` range fields |

### Sub-field rows

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-subfield-rows` / `data-formie-subfield-row` | Shared subfield layout attributes used by split date/time layouts | Required for split-field layouts |

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field layout

| Class | Description |
| --- | --- |
| `formie-date-field-layout` | Date field layout surface for split-field variants |
| `formie-subfield-fieldset` | Fieldset styling used by grouped subfields |
| `formie-date-field-label` | Date-specific label styling class |

### Field input

| Class | Description |
| --- | --- |
| `formie-input` | Shared control styling and focus treatment |
| `formie-select` | Select styling used by dropdown subfields |
| `formie-field-nested` | Nested subfield wrapper styling |
| `formie-input-error` | Error-state styling class |

### Sub-field rows

| Class | Description |
| --- | --- |
| `formie-subfield-rows` | Subfield rows wrapper |
| `formie-subfield-row` | Individual subfield row |

## Behavior

Date always preserves one field identity, but its rendered controls vary by display type:

- `calendar` renders native browser date/time inputs
- `datePicker` renders a single transport input that the browser package enhances with flatpickr
- `datePicker` range fields also render hidden start/end transport inputs and mount flatpickr in `range` mode
- `dropdowns` and `inputs` render subfields for year/month/day and optional time parts

When the `date-picker` module is present, Formie:

- mounts flatpickr onto the input
- copies accessibility and data attributes onto flatpickr's visible `altInput`
- supports dynamic min/max dates and allowed weekdays

## Events

Date-picker-enhanced fields emit field events in addition to the broader events documented on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:field:date-picker:before-init` event

Triggered before flatpickr is created. Use this to adjust the picker options before the instance mounts.

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
  const { datepicker } = event.detail;
  const visibleInput = datepicker?.altInput;

  // Flatpickr may create a separate visible input when altInput is enabled.
  if (!(visibleInput instanceof HTMLInputElement)) {
    return;
  }

  // Mark the visible control once the picker has finished mounting.
  visibleInput.setAttribute('data-datepicker-ready', 'true');
});
```

## Related pages

- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
- [CSS variables](/browser/ui-reference/css-variables)
