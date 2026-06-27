# Create a content-managed user registration form with Formie

Formie can content-manage Craft-native forms — login, registration, password reset, and profile updates — so clients can edit labels, instructions, and field order in the form builder. Registration is a common starting point.

The tradeoff: the form posts to Craft's `users/save-user` action, not Formie's submission pipeline. Formie will not save a submission, send email notifications, or run integrations unless you add that separately (see the [User integration](/integrations/elements/user) if you need both).

## Prerequisites

- User registration enabled in Craft
- [Craft Forms](/templates/craft-forms) pattern

## Create the Formie form

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

## Simple single-page registration

For core user attributes only, follow the [Craft Forms](/templates/craft-forms) registration example:

```twig
{% set form = craft.formie.forms.handle('userRegistration').one() %}

{{ craft.formie.formAssets(form) }}

<form method="post" accept-charset="UTF-8" data-fui-form="{{ form.configJson }}">
    {{ actionInput('users/save-user') }}
    {{ csrfInput() }}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

The critical setting is `fieldNamespace: ''`. By default Formie names inputs under `fields[...]`, but Craft expects names like `email` and `password` directly:

```html
<input type="text" name="email">
<input type="password" name="password">
```

Without an empty namespace you would get `fields[email]`, which Craft's user actions do not read.

## Adding custom user fields on a second page

Custom user fields belong to Craft's `fields[handle]` namespace. A practical pattern is to put native user properties on page 1 (no namespace) and custom fields on page 2 (`fields` namespace):

```twig
{% set form = craft.formie.forms.handle('userRegistration').one() %}

{{ craft.formie.formAssets(form) }}

<form method="post" accept-charset="UTF-8" data-fui-form="{{ form.configJson }}">
    {{ actionInput('users/save-user') }}
    {{ csrfInput() }}

    {% for page in form.getPages() %}
        {{ craft.formie.renderPage(form, page, {
            fieldNamespace: loop.first ? '' : 'fields',
        }) }}
    {% endfor %}
</form>
```

That produces the expected mix:

```html
<input type="text" name="firstName">
<input type="text" name="lastName">
<input type="email" name="email">

<input type="text" name="fields[myCustomField]">
```

## When to use the User integration instead

This pass-through pattern is ideal when you want Craft to own user creation and do not need Formie submissions. If you want Formie to save the submission, send notifications, and *also* create or update a user, use the [User integration](/integrations/elements/user) instead.

## Related

- [Craft Forms](/templates/craft-forms)
- [User integration](/integrations/elements/user)
- [Rendering Pages](/templates/rendering-pages)
