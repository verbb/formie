# Craft Forms

Formie can also manage the fields for certain Craft-native forms, even when the final submission should go to Craft instead of Formie.

This is useful when you want content-managed labels and fields for common flows such as login, registration, or profile editing.

## How it works

The basic pattern is:

1. create a Formie form to manage the fields
2. render the page or fields, not the full Formie submission flow
3. point the form at the Craft action you actually need
4. remove Formie’s default field namespace when Craft expects raw field names

```twig
{% set form = craft.formie.forms.handle('login').one() %}

{{ craft.formie.formAssets(form) }}

<form method="post" accept-charset="UTF-8" data-fui-form="{{ form.configJson }}">
    {{ actionInput('users/login') }}
    {{ csrfInput() }}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

## Why `fieldNamespace` matters

By default, Formie names inputs under `fields[...]`.

That is right for normal Formie submissions, but many Craft actions expect names like `email`, `username`, or `password` directly.

That is why `fieldNamespace: ''` is the key setting in this pattern.

Without it, Formie would render a field such as:

```html
<input type="text" name="fields[loginName]">
```

With `fieldNamespace: ''`, that becomes:

```html
<input type="text" name="loginName">
```

which is what Craft's user actions expect.

## Login form

For a login form, create a Formie form with fields that match what Craft expects, such as:

- `loginName`
- `password`
- `rememberMe`

If your site uses email addresses as usernames, making `loginName` an Email Address field can be a better fit.

For the password field, make sure the input type is set to `password` so it renders as a password input.

You can also add normal Craft concerns around the form, such as error messages and redirect handling:

```twig
{% set form = craft.formie.forms.handle('login').one() %}

{{ craft.formie.formAssets(form) }}

<form method="post" accept-charset="UTF-8" data-fui-form="{{ form.configJson }}">
    {{ actionInput('users/login') }}
    {{ csrfInput() }}

    {% if errorMessage is defined %}
        <p>{{ errorMessage }}</p>
    {% endif %}

    {% if form.getRedirectUrl() %}
        {{ redirectInput(form.getRedirectUrl()) }}
    {% endif %}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

## Registration form

For registration, the same pattern applies. Create a Formie form with fields such as:

- `username`
- `firstName`
- `lastName`
- `email`
- `password`

If your site uses `useEmailAsUsername`, you may not need a separate `username` field.

```twig
{% set form = craft.formie.forms.handle('register').one() %}

{{ craft.formie.formAssets(form) }}

<form method="post" accept-charset="UTF-8" data-fui-form="{{ form.configJson }}">
    {{ actionInput('users/save-user') }}
    {{ csrfInput() }}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

If you want Formie to save a submission and then create or update the user, look at the [User integration](/integrations/elements/user).

## Profile forms

Profile forms follow the same idea, but they usually need prefilled values from the current user and a hidden `userId` field for Craft.

```twig
{% set form = craft.formie.forms.handle('profile').one() %}
{% set user = user ?? currentUser %}

{{ craft.formie.formAssets(form) }}

{% do craft.formie.populateFormValues(form, {
    username: user.username,
    email: user.email,
    firstName: user.firstName,
    lastName: user.lastName,
}) %}

<form method="post" accept-charset="UTF-8" enctype="multipart/form-data" data-fui-form="{{ form.configJson }}">
    {{ actionInput('users/save-user') }}
    {{ hiddenInput('userId', user.id) }}
    {{ csrfInput() }}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

This works well for core user attributes such as name and email.

For user photos and custom user fields, it is usually better to template those separately rather than duplicating everything in Formie. File inputs cannot be pre-populated with an existing photo, and custom user fields already belong to Craft's normal `fields[...]` namespace.

## Related forms

The same overall pattern can be used for password reset and set-password forms, as long as the field handles and action endpoint match what Craft expects.

## Good use cases

This approach can work well for:

- login forms
- registration forms
- profile update forms
- password reset flows

## When not to use it

The main tradeoff is that this is no longer a normal Formie submission flow. Formie will not save a submission, email notifications will not send, and integrations will not run, because the form is being posted to Craft instead.

That can still be the right choice for login, registration, and profile flows, but it is a significant tradeoff. If you want Formie to keep its submission handling and still create or update users, consider using the [User integration](/integrations/elements/user) instead.

If the Craft form is small and stable, a normal hand-written template may also be simpler.
