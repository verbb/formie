# Field

A Field object represents one field instance on a form. This is different from the general idea of a field type; here, you are working with the actual field that is being rendered, inspected or processed.

## Properties

::: reference
### `id`

**Type:** `string|int|null`

The ID of this use of the field on a form. Submissions and browser code use this ID; `definitionId` identifies the shared field settings.
:::

::: reference
### `uid`

**Type:** `string|null`

The form-field instance UID. Copies and new imports receive new instance UIDs.
:::

::: reference
### `definitionId`

**Type:** `int|null`

The database ID of the field’s shared settings, called its definition internally. Synced fields share this value. It is not a key for reading submission values.
:::

::: reference
### `definitionUid`

**Type:** `string|null`

The unique identifier (UID) of the field’s shared settings. Stencils and form imports use it to preserve synced field relationships across environments.
:::

::: reference
### `label`

**Type:** `string|null`

The field label.
:::

::: reference
### `handle`

**Type:** `string|null`

The field handle.
:::

::: reference
### `reference`

**Type:** `string|null`

The stable field reference, when available.
:::

::: reference
### `type`

**Type:** `string`

The field type.
:::

::: reference
### `form`

**Type:** `verbb\formie\elements\Form|null`

The [Form](/reference/form) this field belongs to.
:::

::: reference
### `required`

**Type:** `bool`

Whether the field is required.
:::

::: reference
### `enabled`

**Type:** `bool`

Whether the field is enabled.
:::

::: reference
### `instructions`

**Type:** `verbb\formie\models\RichText`

The field instructions.
:::

::: reference
### `placeholder`

**Type:** `string|null`

The field placeholder, where supported.
:::

::: reference
### `defaultValue`

**Type:** `mixed`

The field default value, where supported.
:::

::: reference
### `settings`

**Type:** `array`

The field settings.
:::


## Methods

The methods below help you render fields and read their values. For implementing storage and value handling in your own field type, see [Custom Field](/developers/custom-field/overview#normalised-values-and-projections).


::: reference
### `hasLabel()`

**Returns:** `bool`

Returns whether the field should render a label.
:::

::: reference
### `getHtmlId()`

**Returns:** `string`

Returns the field’s HTML `id` value.
:::

::: reference
### `getHtmlName()`

**Returns:** `string`

Returns the field’s HTML `name` value.
:::

::: reference
### `getContainerAttributes()`

**Returns:** `array`

Returns attributes for the field container.
:::

::: reference
### `getInputAttributes()`

**Returns:** `array`

Returns attributes for the field input, where applicable.
:::

::: reference
### `renderInput(form, value)`

Returns the field's input HTML. This omits the surrounding label, validation rules and error container. Use `craft.formie.renderField(form, field)` when [custom rendering](/theming/custom-rendering) needs a complete field.
:::

::: reference
### `getReferenceBlockHtml()`

**Returns:** `string|bool|null`

Returns the field’s reference-block HTML.
:::

::: reference
### `getParentField()`

**Returns:** `verbb\formie\base\FieldInterface|null`

Returns the parent field for sub-fields and nested fields.
:::

::: reference
### `setParentField()`

Sets the parent field for sub-fields and nested fields.
:::

::: reference
### `getValueAsString()`

**Returns:** `mixed`

Returns a string representation of a submitted value.
:::

::: reference
### `getValueAsData()`

**Returns:** `mixed`

Returns data that can be encoded as JSON: a single value for simple fields, or named parts or selected records for more complex fields.
:::

::: reference
### `getValueForExport()`

**Returns:** `mixed`

Returns the value prepared for export.
:::

::: reference
### `getValueForReport()`

**Returns:** `mixed`

Returns a single readable value for a report cell, including report exports. Call `field.getValueForReport(value, submission, useOptionLabels)` with the normalized field value and its submission. The optional `useOptionLabels` argument defaults to `true`; pass `false` to use stored option values. The default implementation uses the field's string representation. Fields can customize report output independently of structured export data.
:::

Fields implementing `verbb\formie\base\ReportSubfieldsInterface` can also provide direct child columns through `getReportSubfields()`. See [Report Subcolumns](docs:developers/custom-field/overview#report-subcolumns) for the opt-in contract.


::: reference
### `getValueForSummary()`

**Returns:** `mixed`

Returns the value prepared for summary views.
:::

::: reference
### `getValueForReference()`

**Returns:** `mixed`

Returns the value prepared for singular reference contexts.
:::

::: reference
### `getValueForReferenceBlock()`

**Returns:** `mixed`

Returns the value prepared for reference-block rendering.
:::
