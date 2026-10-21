# Build a Custom Validator

See [Validation](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation) in the Formie developer documentation for PHP rules, browser definitions, and JavaScript registration. It also links to a worked guide that builds a custom field and tests both validation paths.

If you need extra browser logic on an ordinary field, start with [Add a Check to an Existing Field](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#add-a-check-to-an-existing-field). A custom field type is useful when the requirement should travel with a reusable field. Use [submission hooks](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#choose-how-to-add-a-check) for workflow coordination; `abort(message)` produces a form-level message, without adding a validation rule for an input.

For server-rendered forms, use the [DOM validator registration](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#register-the-browser-validator). React, Vue, Web Components and custom clients using the shared value validator use [core rule registration](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#client-rendered-forms-and-shared-value-rules).

The same page covers [validator context](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#browser-validator-context), [displaying a field error](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#show-a-field-error-manually) and [remote checks before submit](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#run-a-remote-check-before-submit). For existing rule names and payload options, use [Built-in Rules](/browser/validation/built-in-rules).
