# Create a content-managed user registration form with Formie

Formie allows you to content manage [Craft forms](https://verbb.io/craft-plugins/formie/docs/template-guides/craft-forms) as well, like user login, registration, reset password and more. This can be useful if you want to empower your clients to be able to manage the content of the forms from the Formie form builder. To a degree, you are restricted to what fields you can have because they're being saved against a user.

Being able to content manage a user registration form does require a little extra work, which we'll walk through in this guide.

First, create a new form called **User Registration** (with the handle `userRegistration`). To this form, create the following fields (with handles):

- Single-Line Text (`username`)
- Single-Line Text (`firstName`)
- Single-Line Text (`lastName`)
- Email Address (`email`)
- Single-Line Text (`password`)
- Single-Line Text (`newPassword`)

All these fields represent the native User object's properties. If you want to include custom fields, you can also create them, but they **must** match the same type between Formie and Craft fields. It's a bit of duplication, but oh well!

We're going to place all the above fields on the first page of the form, then all custom fields on the second page. We'll show you why shortly!

Next, we'll want to render the form, and instead of using the `craft.form.renderForm()` function, it'll be far easier to custom render the form.

```twig
{% set form = craft.formie.forms.handle('userRegistration').one() %}

<form method="post" accept-charset="UTF-8" data-config="{{ form.configJson }}">
    {{ actionInput('users/save-user') }}
    {{ csrfInput() }}

    {% for page in form.getPages() %}
        {{ craft.formie.renderPage(form, page, {
            fieldNamespace: loop.first ? '' : 'fields',
        }) }}
    {% endfor %}
</form>
```

As per the [documentation](https://verbb.io/craft-plugins/formie/docs/template-guides/craft-forms) we're custom rendering the form, and not using Formie to store submissions. Instead, it acts more of a pass-thru directly to the `users/save-user` action endpoint.

We also loop through each of the pages, which in turn renders each field according to how you've defined it in your Formie form. Notice how we supply a `fieldNamespace` property? For the first page, this is empty (no namespace) but for all other pages it's `fields`. That's because custom fields need to be treated differently than the properties of a User.

The below HTML illustrates the different namespaces required:
```twig
<input type="text" name="username">
<input type="text" name="firstName">
<input type="text" name="lastName">

<input type="text" name="fields[myCustomField]">
```

You'll notice straight away that the custom field requires a `fields[]` property to wrap around the handle of the field, while the other in-built properties don't.

You should now be able to totally manage your user registration forms in Formie.
