# Overview

Integrations connect your forms to other services and to Craft elements. You can send newsletter sign-ups to a mailing platform, create contacts in a CRM, notify a messaging channel or create an entry from a submission. Other integrations provide features within the form itself, such as address suggestions, spam protection and payments.

Choose a provider from the categories in this section for its account requirements and setup instructions. The settings and checks depend on what the integration does.

## Connect a Provider

For integrations that require an account, open **Formie → Integrations** and select the relevant category. Choose **New Integration**, or open the arrow beside it and choose **New project integration**, as explained below. Select the provider and enter the credentials or connect the account as described on the provider’s page. Give the integration a name that identifies the account or purpose, especially if you use more than one connection to the same service.

These settings belong to the connection and can be reused by multiple forms. A successful **Refresh** or **Connect** action confirms access to the provider; it does not prove that a form’s values will be accepted or delivered to the intended destination.

### Integrations and Project Integrations

Both options have the same provider settings and work the same way when used by a form. The choice controls storage, deployment and editing permissions. An **instance** is a Craft installation, such as development, staging or production, rather than an individual site in a multisite installation.

**New Integration** creates an instance-level connection stored in that installation’s database. It is not included in project config, so you configure it separately in each environment. Users with permission to access integrations can create, edit and delete it even when Craft’s `allowAdminChanges` is `false`. Choose this option when a client needs to manage a connection directly on the live site, such as updating their newsletter account.

**New project integration** stores the connection’s configuration in Craft’s project config so you can commit and deploy it with your project. This option is available when admin changes are enabled; creating, editing and deleting project integrations also requires the relevant Formie permissions. Choose it when your development team wants to configure a CRM connection locally, review it in Git and deploy it to staging and production. When admin changes are disabled, existing project integrations continue to work but cannot be edited or deleted through the control panel.

In the integration list, project integrations display **Project** in the **Scope** column; instance-level integrations leave it blank.

Project config sync leaves instance-level integrations unchanged, including those in the trash. Updates and removal in project config apply only to project integrations.

For project integrations, use environment variable references such as `$MAILCHIMP_API_KEY` in credential fields that support them, and set the corresponding value in each environment. The reference is stored in project config; entering a literal credential can put that value in project config instead. OAuth connections must be authorised in each environment; deploying the configuration does not transfer the connected account’s tokens.

The choice applies to the reusable connection. Each form’s enabled integrations, destinations and field mappings are still saved with the form. See [Project Config, Environment, and Control Panel Settings](/guides/configuration/project-config-environment-and-control-panel-settings) for how connection configuration and form content move between environments.

### Captcha Provider Settings

Configure visitor-verification providers under **Formie → Spam Protection → CAPTCHAs**, and Akismet, CleanTalk and OOPSpam under **Spam Filtering Services**. Users with **Manage spam protection providers** can configure and save each provider independently, including on production with admin changes disabled.

Provider settings are saved in the current installation’s database and are not included in project config. Configure production credentials on production. Environment-variable references are supported where indicated, and each installation must supply the referenced values. See [Setting Up Production](/forms/spam-protection#setting-up-production) for the workflow.

A database copy can replace local credentials, so check them and submit a test form afterward. Enabling the provider on an individual form is saved with that form.

## Configure an Individual Form

For integrations that send submission data, open the form’s **Integrations** tab, select the connection and enable it. Choose the destination, such as a mailing list, CRM object or messaging channel, then configure any required values. Save the form when you finish.

Where field mapping is available, it connects answers in your form to fields in the receiving system. For example, map your form’s Email Address field to the provider’s email field using the variable picker. Typing a field handle as plain text sends that literal text instead of the visitor’s answer. Map the destination’s required fields before adding optional values.

Use integration conditions when the action should depend on an answer. A newsletter integration might run only when a visitor selects an opt-in field. The form can still complete when that condition is not met; the condition controls whether that integration runs.

Address providers, CAPTCHAs and payment integrations have their own field or form settings and verification steps. Follow their provider pages for those workflows.

## Verify the Result

For integrations that send submission data, submit the form through your site with recognisable test values. Check the saved submission in **Formie → Submissions**, then inspect the resulting record or message in the destination. Confirm that the values arrived in the correct account, list or channel. If you added a condition, also submit values that should not trigger the integration and confirm that no action occurs.

If the result is missing, check the form’s integration settings, required mappings and conditions. For queued integrations, check whether Craft’s queue has processed the job; the [troubleshooting guide](/get-started/troubleshooting) explains queue setup. A saved submission and a successful account connection are separate from successful delivery.

For a complete worked example, follow [Connect a Newsletter Form to Beehiiv](/guides/integrations/connect-a-newsletter-form-to-beehiiv). The guide takes you through creating the form, mapping its email field, adding an opt-in condition and checking the resulting subscriber.
