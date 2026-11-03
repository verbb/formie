# Pardot

Connect Pardot (Salesforce Account Engagement) to send enquiries to prospects and opportunities, or submit them to a Pardot Form Handler. The API connection requires an OAuth app, an Account Engagement business unit, and a Salesforce user with single sign-on enabled for that business unit. Use an External Client App for a new connection; existing Connected Apps can still be used.

API operations and Form Handler submissions use different connections. API operations use an OAuth token obtained through **Connect**. A Form Handler accepts a separate HTTP POST to its endpoint without that token. The setup below establishes the API connection, then explains both delivery options.

## Step 1. Create the Integration

1. Navigate to **Formie** → **Settings** → **CRM**.
1. Click **New Integration**.
1. Select Pardot as the **Integration Provider**.

<a id="step-2-connect-to-the-pardot-api"></a>

## Step 2. Configure an External Client App

In Salesforce **Setup**, search for **External Client Apps** and open the manager. Create an app for the Pardot integration, choose **Local** distribution for use within your organisation, and enable OAuth. See Salesforce’s [Account Engagement API Quick Start](https://developer.salesforce.com/docs/marketing/pardot/guide/getting-started.html) for the app and user prerequisites.

Copy the **Redirect URI** shown by Formie into the app’s **Callback URL** field. Use the value from the installation you are connecting, including its scheme, hostname and full path. This is not the Form Handler URL. If the app serves more than one environment, retain each required callback URL.

Add these **Selected OAuth Scopes**:

- **Manage Pardot services (pardot_api)**.
- **Perform requests at any time (refresh_token, offline_access)**.

### Authorization Code and Security Settings

For this connection, use the **Authorization Code / Web Server flow**: an authorised user signs in when they click **Connect**. Unlike the separate [Salesforce integration](/integrations/crm/salesforce), Pardot has no grant selector. Do not configure a Client Credentials / **Run As** connection for it.

You can enable **Require Proof Key for Code Exchange (PKCE) extension for Supported Authorization Flows**; Formie sends the challenge and verifier. Keep **Require Secret for Web Server Flow** and **Require Secret for Refresh Token Flow** enabled: Formie supplies the client secret for both exchanges.

Configure the app’s policies to permit the Salesforce user who will authorise the connection. If your organisation requires admin-approved users, have an administrator assign the appropriate access. Keep IP restrictions and other access policies consistent with your organisation’s requirements; connecting Formie does not require a blanket relaxation of those policies.

Save the app. Salesforce advises allowing up to 15 minutes before a new app is available for authentication.

## Step 3. Enter the Credentials in Formie

Open the app’s OAuth settings and copy its **Consumer Key** and **Consumer Secret** into the corresponding fields in the Pardot integration. Store secrets in environment variables where possible; if Formie’s fields reference environment variables, update their values on the correct server.

In Salesforce’s **Marketing Setup**, find **Pardot Account Setup** and copy the **Business Unit ID** for the destination business unit into Formie. This identifies which Account Engagement business unit the API should use, even when the organisation has only one.

If replacing an app, update the existing Pardot integration rather than recreating its forms or changing the credentials of a separate Salesforce integration. Retain the correct Business Unit ID and form settings, and leave the old app available until the new connection is verified and any other consumers have been checked.

<a id="step-3-test-connection"></a>

## Step 4. Test the Connection

Save the integration and click **Connect** in the right-hand sidebar. Sign in to Salesforce using the user with access to the intended Account Engagement business unit, then authorise the app.

Returning to Formie with a **Connected** status confirms that an OAuth token was stored. It does not prove that the user can perform every mapped operation or that submissions reach the intended records; complete the submission test below.

<a id="step-4-form-setting"></a>

## Step 5. Configure the Form

Open the form’s **Integrations** tab, select the Pardot integration and enable it. Choose the delivery option your Account Engagement setup uses.

### API Field Mapping

Enable **Map to Prospect** or **Map to Opportunity**, then map the required destination fields using the variable picker. For a contact enquiry, map the visitor’s email and name to the prospect fields. These operations require the OAuth connection and permissions for the destination records.

### Form Handler

Enable **Enable Form Handler** and enter the handler’s endpoint in **Form Handler URL**. In Account Engagement, configure the handler’s external field names to match the submitted Formie field handles. See Salesforce’s [Form Handler integration instructions](https://help.salesforce.com/s/articleView?id=sf.pardot_form_handlers_integrate_form_handler.htm&language=en_US&type=5) for locating the endpoint.

For example, a Single-Line Text field with the handle `company` is sent as `company`. Single-select Dropdown and Radio fields also include a `.value` alias, so a dropdown named `country` can satisfy either `country` or `country.value` mappings. Multiple selections are sent as repeated parameters with the same field name; you do not need custom comma- or semicolon-joining code for the handler.

For handler-only delivery, leave **Map to Prospect** and **Map to Opportunity** disabled. If you enable API mappings as well, they run before the handler submission; an API failure can prevent that later submission. A handler-only submission does not require an OAuth token, although the integration settings still have required credential and Business Unit fields.

Save the form after configuring either option.

## Troubleshooting

### Redirect URI Mismatch

`redirect_uri_mismatch` means Salesforce rejected the callback URL in the authorisation request. Compare the **Redirect URI** shown in the affected Formie installation with the **Callback URL** registered on the app whose Consumer Key that integration uses. Match the scheme, hostname, path and trailing slash exactly, then save and reconnect.

If they appear identical, compare the decoded `redirect_uri` parameter in the failed authorisation request with those values. Check for environment-specific URL overrides or a Consumer Key belonging to a different app. Creating another app will not resolve the mismatch unless its callback is correct.

### Form Handler Works but the Integration Is Not Connected

**Not connected** describes the OAuth token state, not the availability of the Form Handler endpoint. Prospects can arrive through the handler while API operations remain unavailable. Check the form’s selected delivery options before diagnosing a missing OAuth connection as a failed submission.

### Prospects Are Not Created, or Logs Show API v4 Errors

Formie’s API mappings use the Account Engagement **API v4** endpoints. If the response says `Your account is unable to use version 4 of the API` (error code `89`), ask your Salesforce administrator to confirm that the selected business unit supports API v4. Formie does not switch these operations to API v3.

The hostname `pi.pardot.com` alone does not indicate an unsupported account: Salesforce documents it as the production Account Engagement API host. Check the actual API response, business unit, user access and **Use Sandbox** setting rather than inferring compatibility from the hostname.

Check `storage/logs/formie.log` after a test submission for integration errors.

## Verify a Submission

Save the form, open it on your site and submit recognisable test values. Find the test record in the selected CRM destination. Check the mapped values and whether the integration created a record or updated an existing one as intended.

For multi-select fields, test both one selection and several selections, and verify that every value arrives separately. If Account Engagement syncs prospects into Salesforce, also confirm that the prospect sync completes without errors; its appearance in Pardot alone does not verify that later sync.

If nothing arrives, check whether integration conditions matched, whether the submission was complete and non-spam, and whether Craft’s queue has processed the job. A successful connection check verifies credentials; it does not prove that field mapping and delivery work. See [Integrations Overview](/integrations/overview) for the shared connection, mapping and verification workflow.
