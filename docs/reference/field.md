# Field

A Field object represents one field instance on a form. This is different from the general idea of a field type; here, you are working with the actual field that is being rendered, inspected or processed.

## Properties

::: reference
### `id`

**Type:** `string|int|null`

The field ID.
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
### `getFrontEndInputHtml()`

Returns the field’s front-end input HTML.
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
### `getValueAsArray()`

**Returns:** `mixed`

Returns an array representation of a submitted value.
:::

::: reference
### `getValueForExport()`

**Returns:** `mixed`

Returns the value prepared for export.
:::

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
