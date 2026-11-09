# HubSpot
Connect HubSpot to send enquiries to the records your team manages. You need access to configure the form in Craft and credentials for the destination account. The steps below establish the connection; finish by sending a test submission to verify the result.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click the **New Integration** button.
1. Select HubSpot as the **Integration Provider**.

### Step 2. Connect to the HubSpot API
1. Go to <a href="https://www.hubspot.com/" target="_blank">HubSpot</a> and login to your account.
1. Click on the settings icon on the top-right of the screen.
1. In the left-hand sidebar menu, click on **Integrations** → **Private Apps**.
1. Click the **Create a private app** button.
1. Fill out the details, and click the **Scopes** tab. Select the following scopes:
    - `crm.lists.read`
    - `crm.objects.companies.read`
    - `crm.objects.companies.write`
    - `crm.objects.contacts.read`
    - `crm.objects.contacts.write`
    - `crm.objects.deals.read`
    - `crm.objects.deals.write`
    - `crm.schemas.custom.read`
    - `tickets`
    - `forms`
1. Click the **Create App** button in the top right.
1. In the dialog box, review the info about your app's access token, then click **Continue creating**.
1. Copy the **Access Token** from HubSpot and paste in the **Access Token** field in Formie.

### Step 3. Disable Automatic Form Collection
1. In HubSpot, click on the **Settings** cog icon in the top right-hand of the screen.
1. In the left-hand sidebar menu, click on **Marketing** → **Forms**.
1. Click the **Non-Hubspot Forms** button.
1. For **Collect data from website forms** ensure that this is switched to **Off**.

### Step 4. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 5. Form Setting

1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Choose the data objects (record types) you want to use, and enable their mapping options where available.
1. Map the required destination fields using the variable picker. Follow the requirements shown for each selected record type.
1. Enable the integration.
1. Click **Save** to save the form.

For a contact enquiry, map the visitor’s email and name where those fields are available.

### Custom Objects (Enterprise)

When using **Map to Form** with HubSpot custom object properties, Formie fetches your portal’s custom object schemas when you refresh HubSpot forms. Form fields are mapped using the correct `objectTypeId` (for example `2-21479350`) automatically.

Ensure your private app includes the `crm.schemas.custom.read` scope, and refresh HubSpot forms after creating or changing custom objects in HubSpot.

## Supported Field Mappings

Choose a HubSpot property that can store the value collected by your Formie field. A property is a field on a HubSpot record, such as a contact’s email address or a company’s name. Formie supports writable HubSpot properties with text, enumeration (a fixed set of choices), date, date/time, phone number, boolean and number types. Read-only and calculated properties are excluded from property mapping.

These are common mappings you can configure using the variable picker:

| Formie Field | HubSpot Destination | Mapping Behaviour |
| --- | --- | --- |
| Single-Line Text or Multi-Line Text | Text property | Sends the entered text. |
| Email Address or Phone Number | Corresponding email or phone property | Sends the field’s text value. |
| Number | Number property | Sends a whole-number value; decimal values are converted to integers. |
| Date | Date picker property | Sends the date in HubSpot’s timestamp format. |
| Agree | Single checkbox property | Sends `true` or `false`. |
| Dropdown or Radio Buttons | Dropdown or radio select property | Sends the selected option’s value. |
| Checkboxes or a multi-select Dropdown | Multiple checkboxes property | Sends the selected values separated by semicolons. |
| Table | Text property | Sends all cell values as one comma-separated string, as explained below. |

For choice fields, match the Formie option values to the internal values of the options in HubSpot. Matching the visible labels alone is not enough. A multiple-choice property accepts its configured options, so it is not a destination for arbitrary Table rows.

### Table Fields

Map the whole Table field to a HubSpot text property when you only need a text record of its contents. Formie reads each row in order, takes the cell values in column order, and joins them with a comma and a space. For example, consider a Table with Product and Quantity columns:

| Product | Quantity |
| --- | --- |
| Product A | 2 |
| Product B | 5 |

Formie sends the following value to HubSpot:

```text
Product A, 2, Product B, 5
```

Column headings, row boundaries and table formatting are not included. This is a plain-text summary, so a multi-line text property does not preserve the table’s layout either. Mapping a Table does not create separate HubSpot records or line items for its rows. If you need to retain that structure or create one record per row, a developer will need to implement custom integration logic.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Find the test record in the selected CRM destination. Check the mapped values and whether the integration created a record or updated an existing one as intended.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Integrations Overview](/integrations/overview) for the shared connection, mapping and verification workflow.
