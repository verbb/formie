# Email Address

Use Email Address when the answer should be a valid email address.

Use Email Address for reply-to addresses, contact details, notification variables, and integration mapping. If the value only looks like an email address but should not be validated as one, use Single-Line Text instead.

## Key settings

- **Placeholder** - Show example text before the user enters a value.
- **Default value** - Pre-fill the field for new submissions.
- **Unique value** - Prevent the same email address from being submitted more than once for the form.
- **Validate domain** - Check DNS records for the submitted email domain.
- **Blocked domains** - Reject email addresses from domains the form should not accept.
- **Block Free Email Providers** - Reject addresses from common free email providers such as `gmail.com` or `hotmail.com`.

## Submitted value

Email Address stores a single email string. Use this field when the value will be mapped into reply-to settings, CRM contacts, notification recipients or other email-aware systems.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Blocking domains

Use **Blocked Domains** when you need to maintain a form-specific deny list. Use **Block Free Email Providers** when the form should reject addresses from common free providers.

The built-in free-provider list is maintained in Formie’s source as [`free-email-domains.csv`](https://github.com/verbb/formie/blob/craft-5/src/data/free-email-domains.csv). The list is long, and developers can adjust it with the [`modifyFreeEmailDomains` event](/developers/events/utility-events#the-modifyfreeemaildomains-event).

## Theme config

The Email Address field can be targeted with the `emailAddress` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        emailAddress: {
            fieldInput: {
                attributes: {
                    class: 'my-email-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the email input markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Single-Line Text](/fields/single-line-text) when the value only looks like an email address but should not be validated as one.
- Use [Recipients](/fields/recipients) when the submitter should choose where a notification is sent.

