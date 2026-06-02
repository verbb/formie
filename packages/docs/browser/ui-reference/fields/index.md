# Fields

Field pages document the browser-owned contract for rendered Formie fields.

That includes:

- a visual preview of the default output
- required attributes and which element they belong to when overriding markup
- optional styling classes
- browser hooks and events when a field has browser behavior
- token and accessibility notes for common overrides

For full Twig overrides, start with [Form](/browser/ui-reference/components/form) and [Field](/browser/ui-reference/components/field) before drilling into the field-specific pages.

Normal Formie-rendered output already includes these hooks for you. The attribute tables on these pages matter most when you are overriding templates, auditing generated markup, or mapping the default UI into another rendering surface.

Those requirements often span more than one element: a field wrapper, one or more form controls, and sometimes supporting nodes such as hidden inputs, error containers, or subfield rows. The field pages should call out that ownership explicitly rather than imply everything belongs on a single element.

## Field reference pages

- [Single Line Text](/browser/ui-reference/fields/single-line-text)
- [Multi Line Text](/browser/ui-reference/fields/multi-line-text)
- [Checkboxes](/browser/ui-reference/fields/checkboxes)
- [Radio](/browser/ui-reference/fields/radio)
- [Agree](/browser/ui-reference/fields/agree)
- [Date](/browser/ui-reference/fields/date)
- [Phone](/browser/ui-reference/fields/phone)
- [Address](/browser/ui-reference/fields/address)
- [Categories](/browser/ui-reference/fields/categories)
- [File Upload](/browser/ui-reference/fields/file-upload)
- [Entries](/browser/ui-reference/fields/entries)
- [Signature](/browser/ui-reference/fields/signature)
- [Tags](/browser/ui-reference/fields/tags)
- [Repeater](/browser/ui-reference/fields/repeater)
- [Table](/browser/ui-reference/fields/table)
- [Summary](/browser/ui-reference/fields/summary)
- [Calculations](/browser/ui-reference/fields/calculations)
- [Recipients](/browser/ui-reference/fields/recipients)
- [Hidden](/browser/ui-reference/fields/hidden)
- [Payment](/browser/ui-reference/fields/payment)

## Field categories

The browser package field surface broadly falls into these groups:

- core inputs such as single-line text, multi-line text, radio, checkboxes, and agree
- element-backed choice fields such as categories, entries, and recipients
- enhanced inputs such as date, phone, tags, file upload, and signature
- structural fields such as repeater and table
- derived fields such as summary, calculations, and hidden values
- provider-backed fields such as address and payment

As more field pages are promoted into the public reference set, they should follow the same structure and stay owned by the Browser docs rather than a separate “core” docs area.

## Related pages

- [Form](/browser/ui-reference/components/form)
- [Field](/browser/ui-reference/components/field)
- [Buttons](/browser/ui-reference/components/buttons)
- [Loading](/browser/ui-reference/components/loading)
- [CSS variables](/browser/ui-reference/css-variables)
- [JavaScript events](/browser/behavior/javascript-events)
