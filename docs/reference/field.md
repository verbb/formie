# Field

A Field object represents one field instance on a form. This is different from the general idea of a field type; here, you are working with the actual field that is being rendered, inspected or processed.

## Properties

Property | Description
--- | ---
`id` | The field ID.
`label` | The field label.
`handle` | The field handle.
`reference` | The stable field reference, when available.
`type` | The field type.
`form` | The [Form](/reference/form) this field belongs to.
`required` | Whether the field is required.
`enabled` | Whether the field is enabled.
`instructions` | The field instructions.
`placeholder` | The field placeholder, where supported.
`defaultValue` | The field default value, where supported.
`settings` | The field settings.

## Methods

Method | Description
--- | ---
`hasLabel()` | Returns whether the field should render a label.
`getHtmlId()` | Returns the field’s HTML `id` value.
`getHtmlName()` | Returns the field’s HTML `name` value.
`getContainerAttributes()` | Returns attributes for the field container.
`getInputAttributes()` | Returns attributes for the field input, where applicable.
`getFrontEndInputHtml()` | Returns the field’s front-end input HTML.
`getReferenceBlockHtml()` | Returns the field’s reference-block HTML.
`getParentField()` | Returns the parent field for sub-fields and nested fields.
`setParentField()` | Sets the parent field for sub-fields and nested fields.
`getValueAsString()` | Returns a string representation of a submitted value.
`getValueAsArray()` | Returns an array representation of a submitted value.
`getValueForExport()` | Returns the value prepared for export.
`getValueForSummary()` | Returns the value prepared for summary views.
`getValueForReference()` | Returns the value prepared for singular reference contexts.
`getValueForReferenceBlock()` | Returns the value prepared for reference-block rendering.
