# Fields

Use these pages to inspect the markup, styling and browser behaviour of each field type.

That includes:

- a visual preview of the default output
- required attributes and which element they belong to when overriding markup
- optional styling classes
- browser hooks and events when a field has browser behaviour
- token and accessibility notes for common overrides

For full Twig overrides, start with [Form](/browser/ui-reference/components/form) and [Field](/browser/ui-reference/components/field) before drilling into the field-specific pages.

Normal server-rendered output already includes these hooks for you. The attribute tables on these pages matter most when you are overriding templates, auditing generated markup, or recreating the default markup in your own renderer.

Those requirements often span more than one element: a field wrapper, one or more form controls, and sometimes supporting nodes such as hidden inputs, error containers, or subfield rows. Check which element each attribute belongs to when writing an override.

## Field Reference Pages

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

## Field Categories

Fields are grouped by their main behaviour:

- core inputs such as single-line text, multi-line text, radio, checkboxes, and agree
- element-backed choice fields such as categories, entries, and recipients
- enhanced inputs such as date, phone, tags, file upload, and signature
- structural fields such as repeater and table
- derived fields such as summary, calculations, and hidden values
- provider-backed fields such as address and payment
