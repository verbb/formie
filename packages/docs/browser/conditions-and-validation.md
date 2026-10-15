# Conditions and Validation

Formie’s PHP implementation and browser packages work together to evaluate conditions and validate answers. The developer documentation provides the shared explanation and worked examples:

- [Conditions](https://verbb.io/craft-plugins/formie/docs/v4/developers/conditions) covers the condition model, operators, browser compilation and visibility behaviour.
- [Validation](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation) covers PHP rules, their browser definitions, JavaScript registration, validation scopes and submission errors.

For browser controls, use the [validation overview](/browser/validation/) to configure interaction and inspect the rule payload, and [Built-in Rules](/browser/validation/built-in-rules) for the available rule definitions. Custom clients must preserve the server’s full nested error paths and treat messages as plain text, as described under [Submission Errors and Messages](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#submission-errors-and-messages).
