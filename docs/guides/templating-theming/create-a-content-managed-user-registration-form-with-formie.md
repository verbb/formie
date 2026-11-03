# Create a Content-Managed User Registration Form with Formie

Formie can content-manage Craft-native forms — login, registration, password reset, and profile updates — so clients can edit labels, instructions, and field order in the form builder. Registration is a common starting point.

The tradeoff: the form posts to Craft's `users/save-user` action, not Formie's submission pipeline. Formie will not save a submission, send email notifications, or run integrations unless you add that separately (see the [User integration](/integrations/elements/user) if you need both).

## Prerequisites

- User registration enabled in Craft
- [Craft Forms](/templates/craft-forms) pattern

## Create the Formie Form

Create a form called **User Registration** (handle `userRegistration`) with fields whose handles match Craft user properties:

| Field type | Handle |
| --- | --- |
| Single-Line Text | `username` |
| Single-Line Text | `firstName` |
| Single-Line Text | `lastName` |
| Email Address | `email` |
| Password | `password` |

If your site uses `useEmailAsUsername`, you can omit `username`.

Match field types sensibly — use Email Address for `email`, Password for `password`, and so on. Custom user fields can be added too, but they must mirror Craft field types and use the `fields[...]` namespace (covered below).

Disable captchas on this Formie form. These examples load CSS only and use native HTML submission; Formie’s JavaScript widgets, conditions and captcha validation do not run. If you need captcha protection, follow [Captcha Validation](/templates/craft-forms#captcha-validation) before using this form publicly.

## Simple Single-Page Registration

For core user attributes only, follow the [Craft Forms](/templates/craft-forms) registration example:

```twig
{% set form = craft.formie.forms.handle('userRegistration').one() %}

{{ craft.formie.formAssets(form, { includeJs: false }) }}

<form method="post" accept-charset="UTF-8">
    {{ actionInput('users/save-user') }}
    {{ csrfInput() }}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

The critical setting is `fieldNamespace: ''`. By default Formie names inputs under `fields[...]`, but Craft expects names like `email` and `password` directly:

```html
<input type="email" name="email">
<input type="password" name="password">
```

Without an empty namespace you would get `fields[email]`, which Craft's user actions do not read.

<a id="adding-custom-user-fields-on-a-second-page"></a>

## Adding Custom User Fields

Custom user fields belong to Craft's `fields[handle]` namespace. Render them individually with that namespace while keeping core user attributes at the root. For example, add a Single-Line Text field with the handle `jobTitle` to the Formie form and a matching Plain Text field to Craft’s user field layout. Replace the `renderPage()` call above with:

```twig
{% for field in form.getFields() %}
    {{ craft.formie.renderField(form, field, {
        fieldNamespace: field.handle == 'jobTitle' ? 'fields' : '',
    }) }}
{% endfor %}
<button type="submit">Register</button>
```

The result contains `name="email"` for the core email attribute and `name="fields[jobTitle]"` for the custom user field. Keep all fields on one page: Craft’s user action does not advance Formie pages or keep partial Formie submissions.

Test registration with a new email address, then check the user’s profile in Craft. Also submit an invalid email and a duplicate account to confirm your template displays Craft’s validation errors. Passwords should remain empty when redisplaying a failed submission.

## When to Use the User Integration Instead

This pass-through pattern is ideal when you want Craft to own user creation and do not need Formie submissions. If you want Formie to save the submission, send notifications, and *also* create or update a user, use the [User integration](/integrations/elements/user) instead.
