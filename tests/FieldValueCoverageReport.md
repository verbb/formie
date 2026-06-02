# Field Value Coverage Report

Generated from:
- `tests/Fields/FieldValueConversionExhaustiveMatrixTest.php`
- `tests/Integrations/IntegrationFieldMappingMatrixTest.php`

## Scope Summary

- Exhaustive field conversion matrix test:
  - `it('covers getValueAs* and integration conversion contracts for all form factory field types with empty and populated values')`
- HubSpot provider mapping matrix:
  - `it('resolves {field} value for IntegrationField type {type}')` (8 field families x 9 integration types)
  - `it('resolves static value for each IntegrationField type')`

## Integration Types Covered

- `string`
- `number`
- `float`
- `boolean`
- `date`
- `datetime`
- `dateclass`
- `array`
- `phone`

## Exhaustive Field Matrix (All Factory Builders Classified)

Notes:
- Empty case is always: **field omitted from submission payload** (`submission->with([...])` does not include that field handle).
- Populated case is the exact value passed in `with([$handle => $value])`.
- Assertions in this matrix are contract-oriented (type/shape), not exact value equality for every output path.

| Field Builder | Handle | Empty Input | Populated Input | Tested Paths |
|---|---|---|---|---|
| `singleLineTextField` | `singleValue` | omitted | `"Text Value"` | `getValueAsString`, `getValueAsArray`, `getValueAsArray`, summary/export/condition/email/variable + all integration types |
| `multiLineTextField` | `multiValue` | omitted | `"Longer text body"` | same as above |
| `emailField` | `emailValue` | omitted | `"person@example.test"` | same as above |
| `addressField` | `addressValue` | omitted | `{"address1":"123 Main St","city":"Melbourne","state":"VIC","zip":"3000","country":"AU"}` | same as above |
| `agreeField` | `agreeValue` | omitted | `true` | same as above |
| `calculationsField` | `calcValue` | omitted | `"42"` | same as above |
| `categoriesField` | `categoriesValue` | omitted | `[1]` | same as above |
| `checkboxesField` | `checkboxValue` | omitted | `["one","two"]` | same as above |
| `numberField` | `numberValue` | omitted | `"42"` | same as above |
| `dateField` | `dateValue` | omitted | `"2026-02-01"` | same as above |
| `dropdownField` | `dropdownValue` | omitted | `"one"` | same as above |
| `entriesField` | `entriesValue` | omitted | `[1]` | same as above |
| `fileUploadField` | `fileValue` | omitted | `[]` | same as above |
| `groupField` | `groupValue` | omitted | `{"innerText":"Nested Group"}` | same as above |
| `headingField` | `headingValue` | omitted | `null` | same as above |
| `hiddenField` | `hiddenValue` | omitted | `"Hidden Value"` | same as above |
| `htmlField` | `htmlValue` | omitted | `null` | same as above |
| `nameField` | `nameValue` | omitted | `"Full Name"` | same as above |
| `passwordField` | `passwordValue` | omitted | `"MySecret123"` | same as above |
| `paymentField` | `paymentValue` | omitted | `{"amount":"10.00","currency":"USD"}` | same as above |
| `phoneField` | `phoneValue` | omitted | `"0400000000"` | same as above |
| `productsField` | `productsValue` | omitted | `[1]` | same as above |
| `radioField` | `radioValue` | omitted | `"one"` | same as above |
| `recipientsField` | `recipientsValue` | omitted | `"one"` | same as above |
| `repeaterField` | `repeaterValue` | omitted | `[{"innerText":"Nested Repeater"}]` | same as above |
| `sectionField` | `sectionValue` | omitted | `null` | same as above |
| `signatureField` | `signatureValue` | omitted | `"data:image/png;base64,Zm9v"` | same as above |
| `summaryField` | `summaryValue` | omitted | `null` | same as above |
| `tableField` | `tableValue` | omitted | `[{"col1":"row1"}]` | same as above |
| `tagsField` | `tagsValue` | omitted | `[1]` | same as above |
| `usersField` | `usersValue` | omitted | `[1]` | same as above |
| `variantsField` | `variantsValue` | omitted | `[1]` | same as above |

### Unsupported Builders (Explicitly Classified)

These are still included in the coverage-classification check, but not executed in the exhaustive matrix run:

- `formsField`
  - Reason: FormFactory maps to a class that does not implement `FieldInterface`.
- `missingField`
  - Reason: Placeholder/recovery field cannot be instantiated as a concrete field in this harness.
- `submissionsField`
  - Reason: FormFactory maps to a class that does not implement `FieldInterface`.

## Contract Assertions Used

### `getValueAs*` and wrappers

For both empty and populated submissions per field:
- `getValueAsString(...)` is a string.
- `getValueAsArray(...)` is an array.
- `getValueAsArray(...)` equals `getValueAsArray(...)`.
- `getValueForSummary(...)` is a string.
- Wrapper methods on submission follow same shape checks.

Additional calls made (invoked for execution coverage):
- `getValueForEmail`, `getValueForEmailPreview`, `getValueForVariable`
- `getValueForExport`, `getValueForCondition`
- submission wrappers for export/condition/email/variable

### Integration conversion contract

For each integration type:
- `string` => string
- `number` => `null|int`
- `float` => `null|float|int`
- `boolean` => `null|bool`
- `date` => `null|string`
- `datetime` => `null|string`
- `dateclass` => `null|DateTimeInterface`
- `array` => array
- `phone` => `null|string`

## HubSpot Provider Matrix Coverage

File: `tests/Integrations/IntegrationFieldMappingMatrixTest.php`

Field families covered:
- `singleLineTextField` (`"Hello"`)
- `numberField` (`42`)
- `emailField` (`"test@example.com"`)
- `agreeField` (`true`)
- `dropdownField` (`"one"`)
- `checkboxesField` (`["a","b"]`)
- `dateField` (`"2026-01-15"`)
- `phoneField` (`"0400000000"`)

For each field above:
- All 9 integration types are exercised through real mapping resolution:
  - `Integration::getMappedFieldValue(References::field($ref), $submission, $integrationField)`

Static conversion check also covered:
- `string`, `number`, `float`, `boolean`, `date`, `datetime`

## Latest Run Status

- Command:
  - `./vendor/bin/pest --bootstrap tests/bootstrap-craft.php -c phpunit.craft.xml tests/Fields/FieldValueConversionExhaustiveMatrixTest.php tests/Integrations/IntegrationFieldMappingMatrixTest.php`
- Result:
  - `74 passed`
  - `0 failed`

