# Integrations

Connecting forms to CRMs, marketing tools, automations, and custom providers.


##### [Building a CRM integration from scratch](/guides/integrations/building-a-crm-integration-from-scratch)

Formie ships with dozens of CRM integrations, but you can register your own when your provider is not covered. This walkthrough builds a complete multi-object CRM connector from a Craft module — connection settings, field mapping, and sending payloads — step by step.

##### [Building an Automation integration from scratch](/guides/integrations/building-an-automation-integration-from-scratch)

Formie ships automation integrations out of the box, including Web Request, Zapier, and Make. When you need your own endpoint or payload shape, this walkthrough builds a custom automation integration — URL per form, test payload from the builder, and POST on submit.

##### [Building an Email Marketing integration from scratch](/guides/integrations/building-an-email-marketing-integration-from-scratch)

Formie ships with dozens of email marketing integrations, but you can register your own when your provider is not covered. This walkthrough builds a complete list-and-mapping connector from a Craft module — API connection, list picker, field mapping, and subscribe on submit — step by step.

##### [Creating OAuth integrations with Formie](/guides/integrations/creating-oauth-integrations-with-formie)

OAuth is awkward to implement yourself — token storage, refresh flows, provider quirks, and scope handling add up quickly. Formie uses [Verbb Auth](https://github.com/verbb/auth) under the hood. This walkthrough registers a custom OAuth integration in a Craft module, connects it in the control panel, and calls the provider API from your code.

##### [Re-run failed integrations from the control panel](/guides/integrations/re-run-failed-integrations-from-the-control-panel)

Integrations can fail when a provider is down, credentials expire, or mapping sends invalid data. Formie lets operators manually re-trigger an integration for an existing submission from the control panel — without resubmitting the form or writing custom code.

##### [Integration dispatch and policies](/guides/integrations/integration-dispatch-and-policies)

Formie separates **what** an integration does (credentials, mapping, API calls) from **when and how** it runs on each submission. The form builder’s Integrations tab controls dispatch behaviour — order, queue vs immediate execution, re-run policies, and per-integration conditions — without changing your integration PHP.

##### [Using Guzzle clients from Formie integrations in your own code](/guides/integrations/using-guzzle-clients-from-formie-integrations-in-your-own-code)

Formie supports more than 50 integrations — Mailchimp, HubSpot, Salesforce, Google Sheets, and many others. Each one configures a Guzzle client with the correct authentication, base URI, and error handling. You can reuse that work in your own PHP or Twig code without reimplementing OAuth or API credentials.
