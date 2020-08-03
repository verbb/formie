# Address Providers
Address Providers are one of the provided integrations with Formie, and are used specifically for the [Address](docs:feature-tour/fields#address) field.

Address Providers have settings only at the plugin level, where you should enter API settings, in order to connect to the third-party provider. The specific provider can then be selected on individual Address fields.

For the address fields, these providers will render a single input for the user to type into. It will then suggest geocoded addresses in a structured format. If an address matches, it will populate all additional address fields with the individual parts of the address.

For instance, you might type "1 Infinite Loop", resolving to Apple's HQ. Formie will then populate the following fields with content:

- Address 1 = `1 Infinite Loop`
- City = `Cupertino`
- ZIP / Postal Code = `95014`
- State / Province = `CA`
- Country = `United States`

It should also be mentioned that you don't even have to have all these separate fields enabled. It's up to you and your needs!

Formie provides 3 address providers.

## Google Places
Use [Google Places Autocomplete](https://developers.google.com/maps/documentation/javascript/places-autocomplete) to use their service to suggest addresses.

You can also provide [Options](https://developers.google.com/maps/documentation/javascript/places-autocomplete#add-autocomplete) in the table field. The `value` content must be JSON-compatible, so ensure you encase strings in `"` characters. For example, the below might restrict suggested addresses to Australia.

Option | Value
--- | ---
`componentRestrictions` | `{ "country": "au" }`


## Algolia Places
Use [Algolia Places](https://community.algolia.com/places/) to use their service to suggest addresses.

You can also provide [Reconfigurable Options](https://community.algolia.com/places/documentation.html#api-options-type) in the table field. The `value` content must be JSON-compatible, so ensure you encase strings in `"` characters. For example, the below might restrict suggested addresses to Australia.

Option | Value
--- | ---
`countries` | `["AU"]`


## Address Finder
Use [Address Finder](https://addressfinder.com.au/) to use their service to suggest addresses. Address Finder is specifically for Australian and New Zealand addresses, and handles their addresses better than other options.

You can also provide [Widget Options](https://addressfinder.com.au/docs/widget_docs) in the table field. The `value` content must be JSON-compatible, so ensure you encase strings in `"` characters. For example, the below might return the location (street, suburb and city) results.

Option | Value
--- | ---
`show_locations` | `true`
