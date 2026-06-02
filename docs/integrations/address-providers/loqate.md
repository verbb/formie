# Loqate
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
