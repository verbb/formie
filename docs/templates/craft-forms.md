# Craft Forms

Formie can also manage the fields for certain Craft-native forms, even when the final submission should go to Craft instead of Formie.

This is useful when you want content-managed labels and fields for common flows such as login, registration, or profile editing.

## How It Works

Use a single-page form with ordinary text, email, password and checkbox inputs. Disable captchas for this Formie form in its settings. The helper below adds browser validation and inline errors. JavaScript widgets, conditions, multi-page navigation, submit-button gating and AJAX submission are outside this pattern.

The basic pattern is:

1. create a Formie form to manage the fields
2. render the page or fields, not the full Formie submission flow
3. point the form at the Craft action you actually need
4. remove Formie’s default field namespace when Craft expects raw field names

```twig
{% set form = craft.formie.forms.handle('login').one() %}

{{ craft.formie.formAssets(form) }}

<form method="post" accept-charset="UTF-8" {{ attr(craft.formie.craftFormAttributes(form)) }}>
    {{ actionInput('users/login') }}
    {{ csrfInput() }}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

The asset call loads Formie’s CSS and JavaScript. `craftFormAttributes()` adds the render ID, the form’s theme classes and styles, and configuration for browser validation. Submit an empty required field to see its inline error. Once the fields are valid, the browser posts the form normally to Craft, which handles server-side validation and redirects.

Keep `data-formie` and `data-formie-form` off the outer form. The helper uses `data-formie-craft-form` to initialize validation without attaching Formie’s submission workflow. You do not need Formie request tokens or a JavaScript bundle of your own.

## Browser Validation

The helper follows the form’s **Validate on Focus** and **Validate on Submit** settings, field validation messages, translated messages and error theme classes. It focuses the first invalid field after submission and updates errors as the visitor corrects them. It does not change the Craft action, CSRF input, redirect or submit-button values.

Craft still validates the request on the server. For example, an incorrect password is an authentication error, so display Craft’s `errorMessage` as shown in the login example below. Registration and profile templates must also display the errors returned by Craft; they are not automatically mapped into Formie’s field containers.

Use ordinary HTML inputs on a single page. Formie’s CAPTCHA, payment, upload and other widget modules do not initialize through this helper. A `formnovalidate` submit button skips browser validation; an existing `novalidate` attribute on the form also opts out of submit-time checks. If JavaScript does not load, the form retains native HTML validation and can still submit to Craft.

`craftFormAttributes(form, renderOptions)` accepts the form object or handle and returns an attribute array for Craft’s `attr()` function. When using render-time `theme` or `themeConfig` overrides, pass the same options to this helper, `formAssets()` and `renderPage()` or `renderField()`. The helper does not change field namespaces. Render separate form instances with distinct render IDs when showing the same form more than once.

For CSS-only rendering, keep `formAssets(form, { includeJs: false })` and omit `craftFormAttributes()`. That retains browser validation popups instead of Formie’s inline errors. Existing CSS-only templates continue to work.

If you manage startup yourself, pass `{ initJs: false }` to the helper and remove `data-formie-init="false"` when the form is ready. Your application can then call `craftForms()` from `@verbb/formie-browser`. The shipped assets initialize Craft forms automatically and observe inserted or removed forms when the browser observer is enabled.

## Why `fieldNamespace` Matters

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

which is what Craft's user actions expect. The option applies to root fields; nested Group and Repeater fields keep their child paths.

To control the names one field at a time, pass the option directly to `renderField()`:

```twig
{% for field in form.getFields() %}
    {{ craft.formie.renderField(form, field, { fieldNamespace: '' }) }}
{% endfor %}
<button type="submit">Log in</button>
```

Use this loop in place of `renderPage()` when you want to supply your own submit button.

## Login Form

For a login form, create a Formie form with fields that match what Craft expects, such as:

- `loginName`
- `password`
- `rememberMe`

If your site uses email addresses as usernames, making `loginName` an Email Address field can be a better fit.

Use a Password field for `password`. Its value stays empty when the form is redisplayed.

You can also add normal Craft concerns around the form, such as error messages and redirect handling:

```twig
{% set form = craft.formie.forms.handle('login').one() %}

{{ craft.formie.formAssets(form) }}

<form method="post" accept-charset="UTF-8" {{ attr(craft.formie.craftFormAttributes(form)) }}>
    {{ actionInput('users/login') }}
    {{ csrfInput() }}

    {% if errorMessage is defined %}
        <p role="alert">{{ errorMessage }}</p>
    {% endif %}

    {% if form.getRedirectUrl() %}
        {{ redirectInput(form.getRedirectUrl()) }}
    {% endif %}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

## Registration Form

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

<form method="post" accept-charset="UTF-8" {{ attr(craft.formie.craftFormAttributes(form)) }}>
    {{ actionInput('users/save-user') }}
    {{ csrfInput() }}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

If you want Formie to save a submission and then create or update the user, look at the [User integration](/integrations/elements/user).

## Profile Forms

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

<form method="post" accept-charset="UTF-8" enctype="multipart/form-data" {{ attr(craft.formie.craftFormAttributes(form)) }}>
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

## Captcha Validation

Posting directly to `users/login` or `users/save-user` bypasses Formie’s server-side captcha validation, spam checks and field validation. Enabling a provider on the Formie form or displaying its widget does not protect these Craft actions.

Formie does not provide a standalone captcha-verification action for Craft’s user controllers. To protect login, use an integration that supports Craft authentication or custom module code that verifies the provider’s response on the server before authentication and rejects missing or invalid tokens. For reCAPTCHA, follow [Google’s server-side verification requirements](https://developers.google.com/recaptcha/docs/verify). A separate browser verification request is not enough unless the login action also enforces its result.

For registration that should use Formie’s submission validation and captchas, submit through Formie and configure the [User integration](/integrations/elements/user). That integration creates or updates users; it is not a replacement for password authentication.

## Related Forms

The same overall pattern can be used for password reset and set-password forms, as long as the field handles and action endpoint match what Craft expects.

## Good Use Cases

This approach can work well for:

- login forms
- registration forms
- profile update forms
- password reset flows

## When Not to Use It

The main tradeoff is that this is no longer a normal Formie submission flow. Formie will not save a submission, email notifications will not send, and integrations will not run, because the form is being posted to Craft instead.

That can still be the right choice for login, registration, and profile flows, but it is a significant tradeoff. If you want Formie to keep its submission handling and still create or update users, consider using the [User integration](/integrations/elements/user) instead.

If the Craft form is small and stable, a normal hand-written template may also be simpler.
