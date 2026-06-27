> [!IMPORTANT]
> **Formie 4** for **Craft 5** has some breaking changes. Consult our [Upgrading from v3](https://github.com/verbb/formie/blob/beta/docs/get-started/upgrading-from-v3.md) docs for the details.
>
> Most aspects are backwards compatible and deprecated, but **Custom Fields** and **Custom Integrations** will be affected the most.

<p align="center"><img src="https://assets.verbb.io/plugins/formie/formie-icon.svg" width="100" height="100" alt="Formie icon"></p>
<h1 align="center">Formie for Craft CMS</h1>

Formie is a Craft CMS plugin for building user-friendly forms your content editors will love — with a drag-and-drop builder, 30+ fields, multi-page support, payments, and 100+ integrations.

## What's new in Formie 4

- **Quiz & Survey fields** — scored quizzes, Likert scales, ratings, ranking, and a **Results** tab in the builder.
- **Reports & scheduled reports** — saved analytical views, charts, exports, and emailed report delivery.
- **Form Groups & permissions** — organise forms at scale, with group policies, field palettes, and granular access control.
- **Multi-site forms** — per-site translation overrides, propagation, and headless `siteHandle` support.
- **Modern front-end stack** — [`@verbb/formie-react`](./packages/formie-react), [`@verbb/formie-vue`](./packages/formie-vue), [`@verbb/formie-web-components`](./packages/formie-web-components), and [`@verbb/formie-browser`](./packages/formie-browser) for Twig, React, Vue, and web components.
- **Client events** — GTM, GA4, Meta, and custom tracking templates, resolved after submit.
- **Smarter workflows** — submission metadata, submission limits, integration dispatch, site-scoped integrations, and expanded spam protection.

## Highlights

### Build

- Drag-and-drop form builder with columns, synced fields, stencils, and live preview.
- Multi-page or single-page forms, with save-and-resume and draft submissions.
- Conditions for pages, fields, buttons, and email notifications.
- Over 30 field types, including address, file upload, payment, repeater, table, group, quiz, and survey.

### Submissions, notifications & reports

- Store and manage submissions in the control panel, with PDF downloads and retention controls.
- Multiple email notifications per form, with variable pickers, PDF attachments, preview, and conditional recipients.
- Saved reports with filters, charts, exports (CSV, Excel, JSON, and more), and scheduled email delivery.

### Connect & protect

- 100+ integrations across CRM, email marketing, payments, captchas, automations, help desk, and more — see the [full integration list](.plugin-store/description.md#integrations).
- Spam protection with keyword rules, submission guards, throttling, and captcha providers.
- Migrate from Solspace Freeform or Sprout Forms, and import submissions via Feed Me.

### Render & theme

- Out-of-the-box Twig templates, or full template overrides for forms, pages, fields, and emails.
- Theme Config for utility CSS frameworks like [Tailwind](https://tailwindcss.com/) and [Bootstrap](https://getbootstrap.com/) — with [ready-to-go themes](https://github.com/verbb/formie-theme-configs).
- Headless support via GraphQL and JavaScript packages — see [`packages/`](./packages/README.md) and the [Vue 3 demo project](https://github.com/verbb/formie-headless).

## Documentation

Visit the [Formie plugin page](https://verbb.io/craft-plugins/formie) for documentation, guides, pricing, and developer resources.

Full plugin store sales copy lives in [`.plugin-store/description.md`](.plugin-store/description.md).

## Support

Get in touch via the [Formie support page](https://verbb.io/craft-plugins/formie/support) or by [creating a GitHub issue](https://github.com/verbb/formie/issues).

<h2></h2>

<a href="https://verbb.io" target="_blank">
    <img width="101" height="33" src="https://verbb.io/assets/img/verbb-pill.svg" alt="Verbb">
</a>
