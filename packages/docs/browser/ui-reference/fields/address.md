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

Address provider modules resolve sub-field inputs with `findAddressFieldInput()` and `ADDRESS_SELECTORS`. All address-owned hooks use the `data-formie-address-*` namespace:

| Selector | Purpose |
| --- | --- |
| `[data-formie-address-autocomplete-input]` | Provider-owned autocomplete input |
| `[data-formie-address-line1-input]` | Address line 1 |
| `[data-formie-address-line2-input]` | Address line 2 |
| `[data-formie-address-line3-input]` | Address line 3 |
| `[data-formie-address-city-input]` | City |
| `[data-formie-address-state-input]` | State / region (visible control) |
| `[data-formie-address-state-dynamic]` | State / region control when using **Dropdown when available** |
| `[data-formie-address-state-autofill-anchor]` | Persistent password-manager/browser autofill target (`autocomplete="address-level1"`) |
| `[data-formie-address-zip-input]` | Postal code |
| `[data-formie-address-country-input]` | Country |
| `[data-formie-address-location]` | Current-location trigger |

Preserve these selectors if you override the field template.

Older forms may still render legacy bare hooks such as `[data-address1]` or `[data-state]`. Provider modules fall back to those automatically via `ADDRESS_LEGACY_SELECTORS`.

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

## State / province loading

When the State / Province sub-field uses **Dropdown when available**, picking a country fetches subdivision metadata. While that request is in flight:

- the address root is marked with `[data-formie-address-state-fetching]` and the country control shows a spinner
- the state column shows a skeleton placeholder via `[data-formie-address-state-skeleton-active]` so layout does not jump
- cached country lookups skip the loading UI on repeat selections

## State / province autofill

Password managers and browser autofill target a persistent anchor input:

- `[data-formie-address-state-autofill-anchor]` with `autocomplete="address-level1"`
- the visible state control uses `autocomplete="off"` so managers do not race a lazy-loaded input

After subdivisions load, the module reconciles the anchor value onto the visible text input, select, or combobox. Mount sweeps and `-webkit-autofill` animation hooks catch late autofill batches.

Custom Address templates do not need to render the autofill anchor manually. The `address-state` module injects it at runtime when **Dropdown when available** is enabled.

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

### State / province loading

| Class / attribute | Description |
| --- | --- |
| `formie-address-state-skeleton` | Skeleton placeholder wrapper shown while subdivisions load |
| `formie-address-state-skeleton-input` | Skeleton input bar |
| `[data-formie-address-state-fetching]` | Set on the address root while subdivision data is loading |
| `[data-formie-address-state-skeleton-active]` | Set on the state field while the skeleton is visible |

## Validation notes

- Child inputs still validate as normal Formie inputs.
- Required and error handling should stay aligned across parent and child output.
- Heavy markup changes should be tested with real provider population flows.

## Related pages

- [Fields](/browser/ui-reference/fields/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Manual initialization](/browser/behavior/manual-initialization)
