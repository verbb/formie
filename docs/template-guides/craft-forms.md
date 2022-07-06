# Craft Forms
You can also use Formie to content manage a few common Craft-centric forms for your site. While you'd typically create the following in your templates as static forms, or content managed through Global Sets, you can use Formie to content manage the form fields, labels and more. This allows you to provide your clients with a central area for managing all forms on your site.

## User Login
Firstly, follow the instructions on the [Front-End User Accounts](https://craftcms.com/knowledge-base/front-end-user-accounts) article, until you reach the "Login Form" section. This is where Formie comes in.

Then, create a new form called **Login** (with the handle `login`). To this form, create the following fields (with handles):

- Single-Line Text (`loginName`)
- Single-Line Text (`password`)
- Agree (`rememberMe`)

:::tip
**Hot tip!** If you have [`useEmailAsUsername`](https://craftcms.com/docs/3.x/config/config-settings.html#useemailasusername) config setting enabled for your site, it'd be a good idea to make the `loginName` field an **Email Address** field, to get the `type="email"` attribute for free.
:::

Be sure to set the **Input Attributes** for the password field to **Name** `type`, **Value** `password` to ensure your field is typed as a password input for security purposes.

Next, we'll begin templating. Fetch the form first:

```twig
{% set form = craft.formie.forms.handle('login').one() %}
```

And then instead of using `craft.formie.renderForm()`, we'll want to modify this to render just the first page of forms, containing our fields, but set the action input to the Craft `users/login` action endpoint

```twig
<form method="post" accept-charset="UTF-8" data-fui-form="{{ form.configJson }}">
    {{ actionInput('users/login') }}
    {{ csrfInput() }}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

There's a few important things to note here. Firstly, we're using the `users/login` action endpoint, instead of `formie/submissions/submit`. This is because we don't want to submit the form to Formie and save a submission each time this form is filled out - like you would any other form. Instead, we want to log in users. In this way, we're purely using Formie for content management of the form fields, and some settings.

Secondly, we're not rendering the entire form, but calling `craft.formie.renderPage()` which will render just the first page and all its fields. You'll notice we're passing in the `fieldNamespace` [render option](docs:theming/render-options#base-options) with an empty string. This is because Formie will namespace the fields with `fields` by default, but for the login form, we don't want that.

To illustrate, rendering the form without this setting would produce:

```twig
<input type="text" name="fields[loginName]">
```

However, Craft's action controller won't be able to use that. Instead, by passing in an empty string `''` we can remove the namespace, producing:

```twig
<input type="text" name="loginName">
```

The finalised form, with some error-handling and redirects would look something like:

```twig
{% set form = craft.formie.forms.handle('login').one() %}

{% do craft.formie.registerAssets(form) %}

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

## User Registration
Firstly, follow the instructions on the [Front-End User Accounts](https://craftcms.com/knowledge-base/front-end-user-accounts) article, until you reach the "Registration Form" section. This is where Formie comes in.

Then, create a new form called **Register** (with the handle `register`). To this form, create the following fields (with handles):

- Single-Line Text (`username`)
- Single-Line Text (`firstName`)
- Single-Line Text (`lastName`)
- Email Address (`email`)
- Single-Line Text (`password`)

:::tip
You can skip the username field if you’ve enabled the [`useEmailAsUsername`](https://craftcms.com/docs/3.x/config/config-settings.html#useemailasusername) config setting.
:::

Be sure to set the **Input Attributes** for the password field to **Name** `type`, **Value** `password` to ensure your field is typed as a password input for security purposes.

The form should be pretty simple to put together, which renders the page of fields, again with no namespace for fields.

```twig
{% set form = craft.formie.forms.handle('register').one() %}

{% do craft.formie.registerAssets(form) %}

<form method="post" accept-charset="UTF-8" data-fui-form="{{ form.configJson }}">
    {{ actionInput('users/save-user') }}
    {{ csrfInput() }}

    {{ craft.formie.renderPage(form, null, {
        fieldNamespace: '',
    }) }}
</form>
```

:::tip
**Hot Tip!** You can also use Formie's [User Element Integration](docs:integrations/element-integrations#user) if you'd like Formie to make a submission when someone registers. After the submission is received, Formie will create (or update) the user for you through its integration.
:::

## User Password Reset
The **Reset Password Form** and **Set Password Form** follow the same process as the above forms. Follow the instructions on the [Front-End User Accounts](https://craftcms.com/knowledge-base/front-end-user-accounts) article.

## User Profile
Firstly, follow the instructions on the [Front-End User Accounts](https://craftcms.com/knowledge-base/front-end-user-accounts) article, until you reach the "User Profile Form" section. This is where Formie comes in.

Then, create a new form called **Profile** (with the handle `profile`). To this form, create the following fields (with handles):

- Single-Line Text (`username`)
- Single-Line Text (`firstName`)
- Single-Line Text (`lastName`)
- Email Address (`email`)
- Single-Line Text (`password`)
- Single-Line Text (`newPassword`)

:::tip
You can skip the username field if you’ve enabled the [`useEmailAsUsername`](https://craftcms.com/docs/3.x/config/config-settings.html#useemailasusername) config setting.
:::

Be sure to set the **Input Attributes** for the password fields to **Name** `type`, **Value** `password` to ensure your field is typed as a password input for security purposes.

The form should be pretty simple to put together, which renders the page of fields, again with no namespace for fields.

```twig
{% set form = craft.formie.forms.handle('profile').one() %}

{% do craft.formie.registerAssets(form) %}

{% set user = user ?? currentUser %}

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

There's a few things different, compared to the **Register** form. We're using `{% set user = user ?? currentUser %}` to either fetch the `user` variable if validation fails, or falling back on `currentUser`.

With this user variable, we can populate the form with values from the user's profile.

```twig
{% do craft.formie.populateFormValues(form, {
    username: user.username,
    email: user.email,
    firstName: user.firstName,
    lastName: user.lastName,
}) %}
```

This will populate all the fields in the form. We're also passing `{{ hiddenInput('userId', user.id) }}` to let the form know we're updating a user.

### User Photos
While using a File Upload field would do the trick in allowing you to upload a new photo, it won't handle managing existing ones. This is because the File Upload field uses a `<input type="file">` element, which cannot be populated with an existing image.

For that reason, we highly recommend adding this separately to the form template:

```twig
{% if user.photo %}
    <label>Photo</label>
    {{ user.photo.getImg({width: 150, height: 150}) | attr({
        id: 'user-photo',
        alt: user.friendlyName,
    }) }}

    <label for="delete-photo">
        {{ input('checkbox', 'deletePhoto', '1', {
            id: 'delete-photo',
        }) }}
        Delete photo
    </label>
{% endif %}

<label for="photo">Upload a new photo</label>
{{ input('file', 'photo', null, {
    id: 'photo',
    accept: 'image/png,image/jpeg',
}) }}
```

### User Custom Fields
The above profile form does a great job of updating all the core user attributes, but it's also common to have custom fields attached to your user profiles. Rather than duplicating every field you have attached to your user elements in Formie, we'd recommend to template these as normal.

```twig
{{ craft.formie.renderPage(form, null, {
    fieldNamespace: '',
}) }}

{% namespace 'fields' %}
    <label for="bio">Bio</label>
    {{ tag('textarea', {
        text: user.bio,
        id: 'bio',
        name: 'bio',
    }) }}
{% endnamespace %}
```

Here, we render the core fields (`username`, `firstName`, `email`, etc.) first, which are managed by Formie. Then, we wrap all our custom fields in a `{% namespace 'fields' %}` tag, which automatically sets the `name` attributes to `fields[myNameAttribute]` and output each custom field for users.

There's nothing stopping you from duplicating fields in Formie though, if that's what you'd like to do!

## Entry Form
You could also use Formie to manage an [Entry Form](https://craftcms.com/knowledge-base/entry-form), but you'd also be duplicating a lot of fields in Formie to the ones you've already attached to the entry.

For this reason, we recommend you stick with the custom [Entry Form](https://craftcms.com/knowledge-base/entry-form).

:::tip
**Hot Tip!** You can also use Formie's [Entry Element Integration](docs:integrations/element-integrations#entry) if you'd like Formie to make a submission when someone fills out a form. After the submission is received, Formie will create (or update) the entry for you through its integration.
:::
