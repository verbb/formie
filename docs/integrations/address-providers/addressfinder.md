# Address Finder
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
