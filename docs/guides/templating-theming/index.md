# Templating

Twig templates, theme config, success pages, and front-end rendering patterns.


## [Add Floating Labels to Your Form Fields](/guides/templating-theming/add-floating-labels-to-your-form-fields)

Floating labels sit inside the input and animate out of the way when the user focuses or enters a value — similar to [Bootstrap's form-floating pattern](https://getbootstrap.com/docs/5.3/forms/floating-labels/). This guide adds floating labels as an opt-in label position editors can choose per field, without forking every field template.

## [Build a Success Page for Your Form](/guides/templating-theming/build-a-success-page-for-your-form)

After submit, Formie can show a message, hide the form, or redirect elsewhere. A dedicated success page lets you thank the user and show what they entered — useful for confirmations, gated content, or support requests.

## [Building a PDF Template from Scratch](/guides/templating-theming/building-a-pdf-template-from-scratch)

PDF templates let Formie attach a generated PDF when an email notification sends — useful for invoices, certificates, signed agreements, or printable summaries. This guide walks through creating a PDF template from scratch and attaching it to a notification.

## [Create a Content-Managed User Registration Form with Formie](/guides/templating-theming/create-a-content-managed-user-registration-form-with-formie)

Formie can content-manage Craft-native forms — login, registration, password reset, and profile updates — so clients can edit labels, instructions, and field order in the form builder. Registration is a common starting point.

## [Create a Gated Download Page for Your Form](/guides/templating-theming/create-a-gated-download-page-for-your-form)

Create a private resource download for signed-in visitors. The walkthrough checks submission ownership in a controller and streams a file from private storage.

## [Custom Templating for Repeater Fields](/guides/templating-theming/custom-templating-for-repeater-fields)

Repeater fields let users add and remove rows of nested fields. Most projects can style them with [theme config](/reference/theme-tag-reference#repeater-field), but when you need different markup — a card layout, a custom add/remove control, or tighter integration with your design system — template overrides are the right tool.

## [How to Conditionally Redirect Users Based on Their Input](/guides/templating-theming/how-to-conditionally-redirect-users-based-on-their-input)

Sometimes the thank-you destination should depend on what the user submitted — send opted-in users to one URL and everyone else to another, route by department, or branch on a quiz score. Formie supports this natively with **Redirect Rules**; the patterns below cover cases where you need more control.

## [How to Manually Set the Page for a Form](/guides/templating-theming/how-to-manually-set-the-page-for-a-form)

Multi-page forms usually progress in order, but sometimes you want to land users on a specific page — for example, skipping introductory questions when they arrive from a campaign link, or offering a "commercial enquiry" entry point alongside a residential one.

## [The Complete Guide to Rendering Submission Content](/guides/templating-theming/the-complete-guide-to-rendering-submission-content)

Querying submissions, field-type recipes, and common mistakes when outputting stored values. Method reference and Twig/PHP examples live in [Submission Content](/developers/submission-content).

## [Theme Config for a Design System](/guides/templating-theming/theme-config-for-a-design-system)

If your site uses a design system — Tailwind utilities, Bootstrap components, or shared BEM classes — theme config is usually the best way to align Formie forms with it. You define HTML tags and attributes once, then reuse that configuration across every form.
