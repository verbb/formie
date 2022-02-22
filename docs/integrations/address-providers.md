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

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Address Providers**.
1. Click the **New Integration** button.
1. Select Google Places as the **Integration Provider**.

### Step 2. Connect to the Google Places API
1. Go to the <a href="https://console.cloud.google.com/project/_/apiui/apis/enabled" target="_blank">Google Cloud Platform Console</a>.
1. Click the **Select a project** button. Either create a new project, or select an existing one.
1. Select **Credentials** from the left side menu, and click **+ Create Credentials**, selecting **API Key**.
1. From the left side menu, select **Library**.
1. From the list of APIs enable both **Places API** and **Maps JavaScript API**.
    - If using the **Show Current Location Button** setting for your field, also add **Geocoding API**.

### Step 3. Field Setting
1. Go to the form you want to enable this integration on.
1. Add an **Address** field to your form.
1. Enable the **Auto-Complete** sub-field option.
1. Select Google Places for the **Auto-Complete Integration**.

You can also provide [Options](https://developers.google.com/maps/documentation/javascript/places-autocomplete#add-autocomplete) in the table field. The `value` content must be valid JSON, so ensure you encase strings in `"` characters. For example, the below might restrict suggested addresses to Australia.

Option | Value
--- | ---
`componentRestrictions` | `{ "country": "au" }`


## Algolia Places
Use [Algolia Places](https://community.algolia.com/places/) to use their service to suggest addresses.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Address Providers**.
1. Click the **New Integration** button.
1. Select Algolia Places as the **Integration Provider**.

### Step 2. Connect to the Algolia Places API
1. Login to your <a href="https://www.algolia.com/apps" target="_blank">Algolia</a> account.
1. In the left-hand sidebar, click **API Keys**.
1. Copy the **Application ID** and enter this in the **App ID** field in Formie.
1. Copy the **Search-Only API Key** and enter this in the **API Key** field in Formie.

### Step 3. Field Setting
1. Go to the form you want to enable this integration on.
1. Add an **Address** field to your form.
1. Enable the **Auto-Complete** sub-field option.
1. Select Algolia Places for the **Auto-Complete Integration**.

You can also provide [Reconfigurable Options](https://community.algolia.com/places/documentation.html#api-options-type) in the table field. The `value` content must be valid JSON, so ensure you encase strings in `"` characters. For example, the below might restrict suggested addresses to Australia.

Option | Value
--- | ---
`countries` | `["AU"]`


## Address Finder
Use [Address Finder](https://addressfinder.com.au/) to use their service to suggest addresses. Address Finder is specifically for Australian and New Zealand addresses, and handles their addresses better than other options.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Address Providers**.
1. Click the **New Integration** button.
1. Select Address Finder as the **Integration Provider**.

### Step 2. Connect to the Address Finder API
1. Login to your <a href="https://portal.addressfinder.net/sessions/login" target="_blank">AddressFinder</a> account.
1. Click the account dropdown in the top-right corner of the screen, and select **Settings**.
1. Under the **Account** section, copy the key into the **API Key** field in Formie.

### Step 3. Field Setting
1. Go to the form you want to enable this integration on.
1. Add an **Address** field to your form.
1. Enable the **Auto-Complete** sub-field option.
1. Select Address Finder for the **Auto-Complete Integration**.

You can also provide [Widget Options](https://addressfinder.com.au/docs/widget_docs) in the table field. The `value` content must be valid JSON, so ensure you encase strings in `"` characters. For example, the below might return the location (street, suburb and city) results.

Option | Value
--- | ---
`show_locations` | `true`


## Loqate
Use [Loqate](https://account.loqate.com/) to use their service to suggest addresses.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Address Providers**.
1. Click the **New Integration** button.
1. Select Loqate as the **Integration Provider**.

### Step 2. Connect to the Loqate API
1. Login to your <a href="https://account.loqate.com" target="_blank">Loqate</a> account.
1. Click the **Add Service** button in the header.
1. Click **API Key** from the provided panes.
1. Copy the **API Key** and enter this in the **API Key** field in Formie.

### Step 3. Field Setting
1. Go to the form you want to enable this integration on.
1. Add an **Address** field to your form.
1. Enable the **Auto-Complete** sub-field option.
1. Select Loqate for the **Auto-Complete Integration**.

You can also provide [Widget Options](https://www.loqate.com/resources/support/setup-guides/advanced-setup-guide/#setting_options) in the table field. The `value` content must be valid JSON, so ensure you encase strings in `"` characters. For example, the below might return the location (street, suburb and city) results.

Option | Value
--- | ---
`show_locations` | `true`
