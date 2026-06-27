Formie is a user-focused forms plugin that your content editors will love. With over 30 fields available, a drag-and-drop form builder, multi-page support, and more!

## What's new in Formie 4

- **Quiz & Survey fields** — build scored quizzes and surveys with radio, checkbox, dropdown, Likert, rating, and rank question types. View response breakdowns and quiz summaries in a dedicated **Results** tab.
- **Reports & scheduled reports** — saved analytical views over submissions with filters, summary counts, charts, on-demand export, and scheduled email delivery.
- **Form Groups & permissions** — organise forms into groups, set group policies (allowed statuses, field palettes, new-form defaults), and control access with granular permissions — including optional dedicated permissions per form.
- **Form statuses** — label form lifecycle states (active, draft, archived) in the control panel, separate from submission workflow statuses.
- **Multi-site forms** — per-site translation overrides for form copy, site-aware propagation, and headless `siteHandle` support.
- **React form builder** — a refined control panel experience with live preview, field palette, and improved accessibility.
- **Client events** — configure GTM, GA4, Meta, and custom tracking events per page, with field values resolved after submit.
- **Submission metadata** — capture referrer, user agent, tracking cookies, and custom developer data — available in notifications, integrations, and PDFs.
- **Submission limits** — cap submissions per form, IP address, or logged-in user over configurable periods.
- **Modern file uploads** — simple native file inputs, or an advanced upload manager with drag-and-drop, staged uploads, progress, and asset retention policies.
- **Site-scoped integrations** — create and manage integrations on production environments without project config deploys.
- **Headless packages** — `@verbb/formie-react`, `@verbb/formie-vue`, `@verbb/formie-web-components`, and `@verbb/formie-browser` for Twig, React, Vue, and web components.

## Features

- Drag-and-drop form builder, with support for columns.
- Multi-page support for complex forms, or single-page for simple ones.
- Store submissions in the control panel, in case you want to view the users' submission later.
- **Stencils** — A quick and easy way to create new forms. Stencils include your form settings, fields and notifications.
- Multiple options to control how forms submit. Show a success message, redirect to an entry, or stay on the same page.
- Conditions for pages, fields, buttons and email notifications.
- Save incomplete submissions for users to come back to later.
- Switch form submissions to be page-reload (POST), or async (Ajax).
- Spam protection — keyword blocking, submission guards, throttling, email rules, suspicious text detection, and captcha providers.
- Integrations API — Captchas, Address Providers, Elements, Email Marketing, CRM, Webhooks, and more.
- Integration dispatch — run integrations sequentially or queued, with conditions, timing controls, and dispatch result variables.
- Migrate from Solspace Freeform or Sprout Forms with our handy migration assistants.
- Supports importing submissions via Feed Me.

### Fields

- Over 30 fields available
  - Standard fields like text, dropdown, radio, checkboxes.
  - Advanced fields like address, file uploading, name (short and full), signatures, calculations.
  - Complex fields like Repeater, Table and Group.
  - **Quiz & Survey** fields for questionnaires, scored tests, Likert scales, ratings, and ranking.
- Plenty of settings for each field to control their appearance, default values and functionality.
- Customise your submit buttons — even multiple submits for multi-page forms.
- Pick from existing fields with ease.
- For Dropdown/Checkboxes/Radio Buttons — select from over 25 preset options to populate your field, like countries, states, languages, currencies, days, months and more!
- **Synced fields** — Create your fields in one place, then use them everywhere!
- **Conditions** — Hide or show fields based on other fields' values.
- **Visibility** — Show, hide or disable any field from being visible to users.
- **Content Encryption** — Protect sensitive data by encrypting it in the database.
- **Match Field** — Enforce fields to match one another. Perfect for "confirm" fields.
- **Lock Field Settings** and **Editor Notes** — protect important field configuration and leave guidance for content authors.

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
- **Conditions** — Choose to send or prevent sending email notifications depending on field values.
- **Conditional Recipients** — Create logic to send to various recipients, depending on field values.

### Sent Notifications

- Keep track of every email notification sent out from Formie. View the exact email sent.
- Easily resend a sent notification to the same recipient, or nominate a new one.

### Reports

- Saved analytical views over submissions with filters, summary counts, and charts.
- Configure columns, export on demand (CSV, Excel, JSON, XML, text), and queue large exports in the background.
- **Scheduled Reports** — email a summary and export attachment on daily or weekly schedules.

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

- Set how long to keep submissions stored for (hours, days, weeks, months, years).
- When deleting a user associated with a submission, you can choose to delete submissions, or transfer them to another user. Just like entries and other Craft elements.
- Set whether to retain file uploads when deleting a submission.
- Per-field asset retention for file uploads.

### Headless

- JavaScript packages for browser, React, Vue, and web components — `@verbb/formie-browser`, `@verbb/formie-react`, `@verbb/formie-vue`, and `@verbb/formie-web-components`.
- Full support for headless implementations, with GraphQL querying and mutations.
- Query forms, fetching all settings, pages, rows, fields and more. Everything you need to create your own forms.
- Query submissions, if you want to show them on your site.
- Create submissions via mutations from your front-end headless form.
- Open source, fully-functioning Vue 3 [Demo Project](https://github.com/verbb/formie-headless) with [Demo Site](https://formie-headless.verbb.io/?form=contactForm).

## Import/Export

- Easily export your forms, including pages, settings, fields, site overrides and more — stored as a JSON file.
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
- Note
- Number
- Password
- Payment
- Phone Number
- Quiz
- Radio
- Recipients
- Repeater
- Section
- Signature
- Single-Line Text
- Summary
- Survey
- Table
- Tags
- Users

## Integrations

Extend Formie's behaviour, and integrate with third-party providers. Easily create your own custom Integrations through the Integrations API.

### Address Providers

Provide autocomplete behaviour for your address fields. Drastically reduce user errors.

- Address Finder (AU/NZ)
- Google Places
- Loqate
- PlaceKit

### Automations

Trigger powerful workflows and integrations when a form is submitted — set it and forget it.

- IFTTT
- Make
- n8n
- Web Request
- Zapier

### Captchas

Protect your site against spam!

- Akismet
- Captcha.eu
- CleanTalk
- Cloudflare Turnstile
- Friendly Captcha
- hCaptcha
- OOPSpam
- Question
- reCAPTCHA v2 (Checkbox and Invisible)
- reCAPTCHA v3
- reCAPTCHA v3 (Enterprise)
- [Snaptcha Plugin](https://plugins.craftcms.com/snaptcha)

### CRM

Build your customer relationship data with ease, mapping form fields to contacts, leads and more.

- 1CRM
- ActiveCampaign
- Agile CRM
- Attio
- Avochato
- Capsule CRM
- CiviCRM
- Copper CRM
- Dotdigital
- Flowlu
- Freshsales
- HubSpot
- Infusionsoft
- Insightly
- Iterable
- Klaviyo
- Marketo
- Maximizer
- Mercury
- Microsoft Dynamics 365
- NoCRM
- Outseta
- Pardot
- Pipedrive
- Pipeliner
- Procurios
- Salesflare
- Salesforce
- Salesmate
- Scoro
- SharpSpring
- SuiteCRM
- SugarCRM
- vCita
- Xero CRM
- Zoho

### Elements

Create elements from form submission data.

- Commerce Products
- [Events Plugin](https://plugins.craftcms.com/events)
- [Solspace Calendar Events](https://plugins.craftcms.com/calendar)
- Entries
- Users

### Email Marketing

Add users who fill out your forms directly to your mailing lists.

- ActiveCampaign
- Adestra
- AWeber
- Beehiiv
- Benchmark
- Brevo (Sendinblue)
- [Campaign Plugin](https://plugins.craftcms.com/campaign)
- Campaign Monitor
- CleverReach
- Constant Contact
- ConvertKit
- Customer.io
- Drip
- Ecomail
- EmailOctopus
- GetResponse
- iContact
- Iterable
- Klaviyo
- Mailchimp
- Mailcoach
- Mailjet
- MailerLite
- Moosend
- Omnisend
- Ontraport
- Ortto (Autopilot)
- Sender
- Vero

### Help Desk

Turn form submissions into support tickets and streamline your customer service.

- Freshdesk
- Front
- Gorgias
- Help Scout
- Intercom
- LiveChat
- Zendesk

### Messaging

Send messages via SMS, chat apps, and more — keep users in the loop wherever they are.

- Discord
- Plivo
- Slack
- Telegram
- Twilio

### Miscellaneous

For any other categories that just don't fit into the above!

- ClickUp
- Google Sheets
- Monday
- Recruitee
- Trello

### Payments

Use your form as a paywall to collect payment from your users.

- BPOINT
- Eway
- GoCardless
- Mollie
- Moneris
- Opayo
- Paddle
- PayPal
- PayWay
- Square
- Stripe

## Documentation

Visit the [Formie Plugin page](https://verbb.io/craft-plugins/formie) for all documentation, guides, pricing and developer resources.

## Support

Get in touch with us via the [Formie Support page](https://verbb.io/craft-plugins/formie/support) or by [creating a Github issue](https://github.com/verbb/formie/issues)
