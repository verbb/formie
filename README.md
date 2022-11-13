# Formie Plugin for Craft CMS

> Looking to make the switch to Formie? Read our [blog post](https://verbb.io/blog/introducing-formie) on why we built Formie.

<img width="500" src="https://verbb.imgix.net/plugins/formie/formie-social-card_2022-11-13-232259_qost.png?auto=format&amp;crop=focalpoint&amp;domain=verbb.imgix.net&amp;fit=crop&amp;fp-x=0.5&amp;fp-y=0.5&amp;h=630&amp;ixlib=php-3.3.1&amp;q=100&amp;w=1200">

Formie is a Craft CMS plugin for creating user-friendly forms that your content editors will love. With over 30 fields available, a drag-and-drop form builder, multi-page support, and more!

<img width="800" src="https://verbb.io/uploads/plugins/formie/formie-form-builder.png" style="box-shadow: 0 4px 16px rgba(0,0,0,0.08); border-radius: 4px; border: 1px solid rgba(0,0,0,0.12);">

## Features
- Drag-and-drop form builder, with support for columns.
- Multi-page support for complex forms, or single-page for simple ones.
- Store submissions in the control panel, in case you want to view the users' submission later.
- **Stencils** - A quick and easy way to create new forms. Stencils include your form settings, fields and notifications.
- Multiple options to control how forms submit. Show a success message, redirect to an entry, or stay on the same page.
- Conditions for pages, fields, buttons and email notifications.
- Save incomplete submissions for users to come back to later.
- Switch form submissions to be page-reload (POST), or async (Ajax).
- Spam protection - Fight spam with our in-built keyword blocking and submission behaviour control.
- Integrations API - Captchas, Address Providers, Elements, Email Marketing, CRM, Webhooks, Miscellanous.
- Migrate from Solspace Freeform or Sprout Forms with our handy migration assistants. 
- Supports importing submissions via Feed Me.

### Fields
- Over 30 fields available
  - Standard fields like text, dropdown, radio, checkboxes.
  - Advanced fields like address, file uploading, name (short and full), signatures, calculations.
  - Complex fields like Repeater, Table and Group.
- Plenty of settings for each field to control their appearance, default values and functionality.
- Customise your submit buttons - even multiple submits for multi-page forms.
- Pick from existing fields with ease.
- For Dropdown/Checkboxes/Radio Buttons - select from over 25 preset options to populate your field, like countries, states, languages, currencies, days, months and more!
- **Synced fields** - Create your fields in one place, then use them everywhere!
- **Conditions** - Hide or show fields based on other fields' values.
- **Visibility** - Show, hide or disable any field from being visible to users.
- **Content Encryption** - Protect sensitive data by encrypting it in the database.
- **Match Field** - Enforce fields to match one another. Perfect for "confirm" fields.

### Email Notifications
- Multiple email notifications per-form. Notify your staff and customers at the same time about their submissions.
- User-friendly variable pickers. No more Twig in field settings for your users to wrangle!
- Full-range of email settings including multiple recipients, reply-to, cc, bcc and more.
- Add user-uploaded attachments to your email notifications.
- Attach custom PDF templates automatically to emails.
- Auto plain text conversion of HTML emails.
- Preview your emails, so you're 100% certain how they'll look.
- Send test emails, for delivery troubleshooting and real-world previews.
- Re-trigger email notifications from any submission, in case some were missed!
- **Conditions** - Choose to send or prevent sending email notifications depending on field values.
- **Conditional Recipients** - Create logic to send to various recipients, depending on field values.

### Sent Notifications
- Keep track of every email notification sent out from Formie. View the exact email sent.
- Easily resend a sent notification to the same recipient, or nominate a new one.

### Templates
- Out-of-the-box templates, including CSS styles and JS functionality. Show great-looking forms that are user-friendly and follow best-practices with a single line of Twig.
- Custom templates for everything! Take full control over how forms, pages and field render. Even change how fields look in email notifications.

### Theming
- Easily theme your forms without touching custom templates!
- Configure each component of a form from the `<form>` element, individual fields, submit buttons and more.
- Total control over the HTML tags and attributes.
- Perfect for utility CSS frameworks like [Tailwind](https://tailwindcss.com/) or [Bootstrap](https://getbootstrap.com/).
- Ready-to-go themes for popular frameworks for you to easily extend and modify:
    - [Tailwind](https://github.com/verbb/formie-theme-configs/blob/main/tailwind/index.html)
    - [Bootstrap](https://github.com/verbb/formie-theme-configs/blob/main/bootstrap/index.html)

### Privacy & Data Retention
- Set how long to keep submissions stored for (hours, days, weeks, month, years)
- When deleting a user associated with a submission, you can choose to delete submissions, or transfer them to another user. Just like entries and other Craft elements.
- Set whether to retain file uploads when deleting a submission.

### Headless
- Full support for headless implementations, with GraphQL querying and mutations.
- Query forms, fetching all settings, pages, rows, fields and more. Everything you need to create your own forms.
- Query submissions, if you want to show them on your site.
- Create submissions via mutations from your front-end headless form.
- Open source, fully-functioning Vue 3 [Demo Project](https://github.com/verbb/formie-headless) with [Demo Site](https://formie-headless.verbb.io/?form=contactForm).

## Import/Export
- Easily export your forms, including pages, settings, fields and more - stored as a JSON file.
- Import forms on the same install, or on another environment entirely. Moving forms between environments is a breeze!

## Support
- Dedicated support area to submit to Verbb support crew.
- Bundles everything we need to know about helping you with form issues.

## Available Fields
- Address
- Agree
- Calculations
- Categories
- Checkboxes
- Commerce Products
- Commerce Variants
- Date/Time
- Dropdown
- Email
- Entries
- File Upload
- Group
- Heading
- Hidden
- Html
- Multi-Line Text
- Name
- Number
- Password
- Payment
- Phone Number
- Radio
- Recipients
- Repeater
- Section
- Signature
- Single-Line Text
- Summary
- Table
- Tags
- Users

### Integrations
Extend Formie's behaviour, and integrate with third-party providers. Easily create your own custom Integrations through the Integrations API.

#### Captchas
Protect your site against spam!

- reCAPTCHA v2 (Checkbox and Invisible)
- reCAPTCHA v3
- reCAPTCHA v3 (Enterprise)
- hCaptcha
- [Snaptcha Plugin](https://plugins.craftcms.com/snaptcha)
- Duplicate
- Honeypot
- Javascript

#### Address Providers
Provide autocomplete behaviour for your address fields. Drastically reduce user errors.

- Google Places
- Algolia Places
- Address Finder (AU/NZ)
- Loqate

#### Elements
Create elements from form submission data.

- Entries
- Users

#### Email Marketing
Add users who fill out your forms directly to your mailing lists.

- ActiveCampaign
- Adestra
- Autopilot
- AWeber
- Benchmark
- [Campaign Plugin](https://plugins.craftcms.com/campaign)
- Campaign Monitor
- Constant Contact
- ConvertKit
- Drip
- EmailOctopus
- GetResponse
- iContact
- Klaviyo
- Mailchimp
- MailerLite
- Moosend
- Omnisend
- Ontraport
- Sender
- Sendinblue

#### CRM
Build your customer relationship data with ease, mapping form fields to contacts, leads and more.

- ActiveCampaign
- Agile CRM
- Avochato
- Capsule CRM
- Copper CRM
- Freshdesk
- Freshsales
- HubSpot
- Infusionsoft
- Insightly
- Klaviyo
- Maximizer
- Mercury
- Microsoft Dynamics 365
- Pardot
- Pipedrive
- Pipeliner
- Salesflare
- Salesforce
- Scoro
- SharpSpring
- SugarCRM
- vCita
- Zoho

#### Payments
Use your form as a paywall to collect payment from your users.

- Stripe (single and subscription)
- PayPal (single)

#### Webhooks
Send form data to URLs or Webhook providers for processing on their end.

- Generic Webhook
- Zapier

#### Miscellaneous
For any other categories that just don't fit into the above!

- Google Sheets
- Monday
- Recruitee
- Slack
- Trello
 
## Documentation

Visit the [Formie Plugin page](https://verbb.io/craft-plugins/formie) for all documentation, guides, pricing and developer resources.

## Support

Get in touch with us via the [Formie Support page](https://verbb.io/craft-plugins/formie/support) or by [creating a Github issue](https://github.com/verbb/formie/issues)

<h2></h2>

<a href="https://verbb.io" target="_blank">
  <img width="100" src="https://verbb.io/assets/img/verbb-pill.svg">
</a>
