# Address

Use the Address field when you need a structured postal address rather than one free-text answer.

Use Address when the value needs to be mapped to integrations, exported, queried, or displayed in parts. If you only need a casual location answer, Single-Line Text is usually simpler.

## Key settings

- **Enabled sub-fields** - Choose which address parts are shown, such as address lines, city, state, postcode and country.
- **Required sub-fields** - Require the specific address parts that matter for your workflow.
- **Autocomplete** - Use a configured address provider to search for and populate address details.
- **Default country (Google Places)** - Bias Google Places suggestions toward a selected country, similar to the Phone field.
- **Current location** - Allow location-based lookup when the selected provider supports it.
- **Country handling** - Restrict or preselect countries when the form should only accept certain regions.
- **Preselect Country from IP** — pre-fill the Country sub-field from the visitor’s IP when no default value is set. Uses CDN geo headers (for example `CF-IPCountry`) via `actions/formie/address/country-from-ip`. Also available on Phone fields with the country selector enabled.
- **State / Province input mode** - Choose **Text** or **Dropdown when available** on the State / Province sub-field.
  - **Text** — always renders a plain text input.
  - **Dropdown when available** — loads subdivisions for the selected country via Formie’s browser assets.
- **Hide When Not Used** — hides the state/province field until a country is selected, and hides it again for countries that do not use administrative areas in their address format.
- **Use Searchable Dropdown** — when subdivision data is available, enhances the state/province control with type-to-filter behaviour (recommended for long lists such as US states).
- **Use Datalist Suggestions** — only applies when subdivision data is unavailable and the field falls back to a text input.
- **Option Label** and **Option Value** — choose whether subdivision options use full names or short codes.

Place **Country** before **State / Province** in the sub-field layout when using **Dropdown when available**. New Address fields default to this order.

While subdivisions load, the country field shows a loading indicator and the state column shows a skeleton placeholder so the layout does not jump. Password managers and browser autofill target a persistent hidden `address-level1` input; Formie reconciles that value onto the visible control once subdivision data is ready.

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

## Developer hooks

Plugins can modify subdivision option data before it is returned to the front end:

```php
use verbb\formie\events\ModifyAddressSubdivisionsEvent;
use verbb\formie\Formie;
use verbb\formie\services\Countries;
use yii\base\Event;

Event::on(
    Countries::class,
    Countries::EVENT_MODIFY_ADDRESS_SUBDIVISIONS,
    function(ModifyAddressSubdivisionsEvent $event) {
        // $event->countryCode — ISO 3166-1 alpha-2 code
        // $event->subdivisions — option rows with label, value, name, and short keys
        // $event->field — optional field context when available
    }
);
```

The subdivisions endpoint is `actions/formie/address/subdivisions` and accepts `country`, `optionLabel`, and `optionValue` query parameters.

IP-based country preselect uses `actions/formie/address/country-from-ip`, which returns `{ countryCode, countryName }` when a supported geo header is present on the request.

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

