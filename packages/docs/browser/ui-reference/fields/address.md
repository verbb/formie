# Address

Address is the address field, made up of multiple child inputs with optional autocomplete and current-location features.

Use this page as the reference for the selectors and parts of the field that need to stay stable when you customize markup.

## Preview

<FormiePreview src="../examples/address.preview.ts" />

## Field structure

In the default output, Address behaves like a parent field that owns a group of child fields:

- the parent field wraps grouped sub-field rows
- each child input is still rendered as a normal field input
- provider modules target the autocomplete child and populate related sub-fields

## Browser selectors

Address provider modules and value mapping rely on these selectors:

| Selector | Purpose |
| --- | --- |
| `[data-formie-address-autocomplete-input]` | Provider-owned autocomplete input |
| `[data-formie-address-line1-input]` | Address line 1 |
| `[data-formie-address-line2-input]` | Address line 2 |
| `[data-formie-address-line3-input]` | Address line 3 |
| `[data-formie-address-city-input]` | City |
| `[data-formie-address-state-input]` | State / region |
| `[data-formie-address-zip-input]` | Postal code |
| `[data-formie-address-country-input]` | Country |
| `[data-formie-address-location]` | Current-location trigger |

Preserve these selectors if you override the field template.

## Built-in provider behavior

Current built-in address providers include:

- `address-finder`
- `google-address`
- `loqate`
- `place-kit`

Address providers mount against the target field, defer setup until the field becomes visible, and can re-check visibility after page navigation or submit results.

## Address events

Provider-specific populate and lifecycle events are documented on [JavaScript events](/browser/behavior/javascript-events), for example:

- `formie:address:place-kit:before-init`
- `formie:address:place-kit:populate`
- `formie:address:address-finder:populate`
- `formie:address:google:populate`

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field input

| Class | Description |
| --- | --- |
| `formie-input` | Shared address input styling and focus treatment |
| `formie-field-nested` | Nested address subfield styling class |

### Sub-field rows

| Class | Description |
| --- | --- |
| `formie-subfield-rows` | Address subfield rows wrapper |
| `formie-subfield-row` | Individual address subfield row |

### Autocomplete

| Class | Description |
| --- | --- |
| `formie-autocomplete-wrapper` | Address autocomplete wrapper styling class |
| `formie-autocomplete-placeholder` | Address autocomplete placeholder styling class |

### Location trigger

| Class | Description |
| --- | --- |
| `formie-address-location` | Current-location trigger styling class |

## Validation notes

- Child inputs still validate as normal Formie inputs.
- Required and error handling should stay aligned across parent and child output.
- Heavy markup changes should be tested with real provider population flows.

## Related pages

- [Fields](/browser/ui-reference/fields/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Manual initialization](/browser/behavior/manual-initialization)
