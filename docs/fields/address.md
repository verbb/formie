# Address

Use the Address field when you need a structured postal address rather than one free-text answer.

Use Address when the value needs to be mapped to integrations, exported, queried, or displayed in parts. If you only need a casual location answer, Single-Line Text is usually simpler.

## Key settings

- **Enabled sub-fields** - Choose which address parts are shown, such as address lines, city, state, postcode and country.
- **Required sub-fields** - Require the specific address parts that matter for your workflow.
- **Autocomplete** - Use a configured address provider to search for and populate address details.
- **Current location** - Allow location-based lookup when the selected provider supports it.
- **Country handling** - Restrict or preselect countries when the form should only accept certain regions.

## Submitted value

Address stores a structured value made from its sub-fields. This is useful when templates, exports or integrations need separate address parts instead of one free-text string.

For GraphQL mutations, Address fields use a generated input object for the field handle. Query the form’s `formFields` and include `inputTypeName`, or see [Create Submissions](/graphql/create-submissions#name-and-address-fields).

## Auto-complete and address providers

Address auto-complete is configured in two places:

1. Create and configure an address provider in **Formie** → **Settings** → **Address Providers**.
2. Edit the Address field, enable the **Auto-Complete** sub-field, then choose the provider for **Auto-Complete Integration**.

Provider setup is documented on the address provider integration pages:

- [Address Finder](/integrations/address-providers/addressfinder)
- [Google Places](/integrations/address-providers/google-places)
- [Loqate](/integrations/address-providers/loqate)
- [PlaceKit](/integrations/address-providers/placekit)

Current-location support depends on the selected provider. If you are building a custom provider, see [Address Provider Integration](/developers/custom-integration/address-provider-integration).

## Theme config

The Address field can be targeted with the `address` theme config key.

See [Address Field theme config](/theming/theme-config#address-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        address: {
            subFieldRows: {
                attributes: {
                    class: 'my-address-rows',
                },
            },
            fieldInput: {
                attributes: {
                    class: 'my-address-input',
                },
            },
        },
    },
}) }}
```

Some address sub-fields can also be targeted by theme config, such as `address1`, `address2`, `addressCity`, `addressState`, `addressZip`, `addressCountry` and `addressAutoComplete`.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

Autocomplete and current-location behavior depend on the configured address provider and Formie’s browser assets. Custom rendering should preserve the autocomplete sub-field and the manual address sub-fields that need to be populated.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Address](/browser/ui-reference/fields/address)

## Related fields

- Use [Single-Line Text](/fields/single-line-text) for informal location answers.
- Use [Group](/fields/group) if you need a different custom set of structured fields.

