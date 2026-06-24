# Password

Use Password when a form needs a password-style input, most commonly for registration or account-related flows.

Password values are not useful as normal submission content. Do not use Password for general private notes or sensitive data collection.

## Key settings

- **Placeholder** - Show example text before the user enters a value.
- **Minimum Length** - Set a minimum character count for the password.
- **Require Uppercase**, **Require Lowercase**, **Require Special Character** - Enforce individual complexity rules.
- **Match field** - Require this value to match another password field for confirmation flows.
- **Required** - Force a password value before the form can be submitted.

## Submitted value

Password is handled as password data, not normal submitted content. Formie stores the value as a hash and does not output the raw password in email content.

Avoid using Password for general secrets or private notes. If the submitted value needs to be read later, choose a field that matches that storage requirement and handle the security model separately.

## Theme config

The Password field can be targeted with the `password` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        password: {
            fieldInput: {
                attributes: {
                    class: 'my-password-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the password input markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Single-Line Text](/fields/single-line-text) for non-password short values.
- Use [Email Address](/fields/email-address) alongside Password in registration-style flows.

