# Google Places
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
`requestedRegion` | `"au"`
