# Privacy & Data

Formie runs entirely inside your own Craft install. Verbb never receives, stores or processes your site’s content or your visitors’ personal data, so you don’t need a data processing agreement with Verbb to use Formie.

Formie doesn’t collect analytics or send usage data to Verbb or anyone else.

Information you share with Verbb directly, such as support requests, is covered by our [privacy policy](https://verbb.io/privacy).

## External Services

Formie only sends data to another service when you set that service up. The data goes straight from your server, or your visitor’s browser, to the service, and that service’s own privacy policy and terms apply.

Formie’s own front-end scripts and styles are served from your site. That includes the libraries behind the phone number, date picker and rich text fields, so a form that doesn’t use any of the services below makes no third-party requests.

### Integrations

When an [integration](/integrations/overview) runs, Formie sends the submission values you’ve mapped to it from your server. That covers the Email Marketing, CRM, Help Desk, Messaging, Automations and Miscellaneous integrations, such as Mailchimp, HubSpot, Salesforce, Zendesk, Slack, Zapier and Google Sheets. Element integrations create entries, users and other elements in your own Craft install, so their data stays on your site.

Email notifications are sent through whichever mail transport your Craft install uses, such as SMTP or an email service.

### Captchas

Captchas only load on forms where you’ve turned them on.

- **reCAPTCHA, hCaptcha and Cloudflare Turnstile** load the provider’s script in the visitor’s browser. When Formie verifies the result, your server also sends the provider the visitor’s IP address.
- **Captcha.eu and Friendly Captcha** run a check in the visitor’s browser with the provider, and your server then confirms the result with them.
- **Akismet, CleanTalk and OOPSpam** have no browser component. Your server sends them the visitor’s IP address, browser user agent and the submission’s values, along with the referring page for Akismet and OOPSpam.
- **Question and Snaptcha** run entirely on your site.

### Address Providers

Google Places, AddressFinder, Loqate and PlaceKit run in the visitor’s browser on forms with an Address field that uses them, and receive what the visitor types. PlaceKit also loads country flag images from `flagcdn.com`. When a visitor fills in their address from their current location with Google Places, your server sends their coordinates to Google’s Geocoding API, so your API key isn’t exposed in the browser.

### Payments

Stripe, PayPal, Square, Paddle, Westpac PayWay and Opayo collect card details with their own scripts in the visitor’s browser, Moneris shows its own secure frame, and Mollie and GoCardless send the visitor to their hosted page. Eway’s script encrypts card details in the browser before your server passes them on to Eway. Your server sends the provider the payment amount and any customer details you’ve mapped.

When a provider sends the visitor back to your site after paying, Formie’s payment status page loads its styles from `cdn.tailwindcss.com`.

### Other Field Types

Fields from the Maps plugin load map tiles from Carto or OpenStreetMap, and send address searches to OpenStreetMap’s Nominatim service. Fields from the Google Maps plugin load Google’s Maps script.

If you set up client events for Google Tag Manager or Google Analytics, Formie adds events to the page’s `dataLayer`. Formie doesn’t load any analytics script itself.

### Verbb’s Development Proxy

While Craft’s `devMode` is on, Formie sends the URLs that Mollie and GoCardless return visitors to through `proxy.verbb.io`, which redirects the visitor’s browser back to your site. Like any web request, it reaches Verbb’s server with the visitor’s IP address and the return URL, which includes a return token. Payment webhooks never go through the proxy. Set [paymentWebhookProxyUrl](/get-started/configuration#paymentwebhookproxyurl) to an empty string to turn this off. Sites with `devMode` off never use the proxy.

The Google Sheets integration has a **Proxy Redirect** option for connecting from a local site. It’s off by default, and when it’s on, only Google’s sign-in redirect passes through `proxy.verbb.io`. No submission data does.

### Support Requests

The support form under **Formie → Settings → Support** sends your request to Verbb only when you submit it. Along with your message, name and email address, it includes your Craft and Formie versions, your Formie licence key, your site’s domain, the form you select with its templates, and any files you attach. **Include Formie log files** is ticked by default, and those logs can contain what visitors entered into your forms, so untick it if you’d rather not send them.

## Stored Data

Everything Formie stores is in your own database and asset volumes.

### Submissions

Submissions hold the values visitors enter, and File Upload fields save files to the asset volume you choose. You can encrypt sensitive fields with [content encryption](/submissions/content-encryption). Password fields are stored as hashes.

Each submission also records the referring page, the page URL and the visitor’s browser user agent. If the visitor’s browser has HubSpot’s `hubspotutk` cookie or Pardot’s `visitor_id` cookies, their values are stored too, for the HubSpot and Pardot integrations to use.

Each form’s builder has these options under **Settings → Privacy**:

- **Collect IP Addresses** stores the visitor’s IP address. It’s off by default.
- **Collect User** stores the logged-in user who submitted the form. It’s off by default.
- **Data Retention** permanently deletes submissions, including incomplete and spam ones, once they’re older than the duration you set. It defaults to **Forever**.
- **File Uploads** decides whether uploaded files are deleted along with a submission that’s permanently deleted. It defaults to **Retain files**.

Each File Upload field also has **Asset Data Retention**, which removes its uploaded files after a set time while keeping the submission. Retention cleanup runs with the [formie/cron/run](/developers/console-commands#formie-cron-run) console command, or with Craft’s garbage collection when that command isn’t scheduled.

### Incomplete Submissions

Incomplete submissions are deleted after 30 days, and progress through a multi-page form is kept for 30 days. Save and continue later links expire after 14 days. You can change these with [maxIncompleteSubmissionAge](/get-started/configuration#maxincompletesubmissionage), [submissionStateRetentionDays](/get-started/configuration#submissionstateretentiondays) and [saveResumeTokenTtlDays](/get-started/configuration#saveresumetokenttldays).

### Spam

Spam submissions are saved by default so you can check for false positives, and older ones are removed once there are more than the limit you set. Both are set in [Spam Protection](/forms/spam-protection#spam-handling).

### Sent Notifications

[Sent Notifications](/forms/sent-notifications) keeps a copy of every email Formie sends, including its recipients and content, along with the IP address and user agent of the request that sent it. Copies are deleted after 30 days and aren’t removed when their submission is deleted. Use [maxSentNotificationsAge](/get-started/configuration#maxsentnotificationsage) to change how long they’re kept, or [sentNotifications](/get-started/configuration#sentnotifications) to stop saving them.

### Integration Deliveries

Formie keeps a record of each integration and email delivery so a failed one can be retried. These records include the data sent and the provider’s response, encrypted with your Craft security key, with passwords, tokens and card details removed. The data is cleared from successful deliveries after 30 days, which you can change with [deliveryEvidenceRetentionDays](/get-started/configuration#deliveryevidenceretentiondays). Failed and pending deliveries keep it until their submission is permanently deleted.

### Payments

Payment records keep the amount, the provider’s reference and response, and the payment’s history. Webhook requests from payment providers are stored encrypted. Neither is cleaned up automatically, and payment records are kept when their submission is deleted.

### Log Files

Formie’s log files include what visitors enter into your forms, which helps when debugging a failed submission. Set [logSubmissionValues](/get-started/configuration#logsubmissionvalues) to `false` to leave the values out. Log files sit outside your database, so data retention doesn’t apply to them.

## Craft Plugin Store

Formie is listed on the Craft Plugin Store, which is run by Pixel & Tonic, the makers of Craft. Craft contacts Pixel & Tonic to check for updates and licences for your installed plugins, and their [privacy policy](https://craftcms.com/privacy) covers that information.
