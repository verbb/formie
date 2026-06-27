# Create a gated download page for your form

Creating a "gated" form is pretty common when you want to protect a resource from users by requiring them to complete the form first. You might want users to submit their email and name before accessing a PDF e-book (which you could also combine with a Payment field to collect payment), or other resources you don't want publicly accessible.

:::tip
Before diving in, have a read through our similar guide on [Build a success page for your form](/guides/templating-theming/build-a-success-page-for-your-form).
:::

### Assembling the pieces
To begin with, we'll need a form for the user to fill out. We're going to use the default **Contact Form** stencil containing a name, email and message field.

Secondly, we'll want to redirect to _something_. This could be a single asset, a page on your website, or an external link. What matters is that this is kept confidential until the user fills out the form. For this example, we'll redirect off to a success page to show the resource for the user to download.

Let's render the form first:

```twig
templates/form.html

{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setSettings({
    redirectUrl: '/download?submissionId={uid}',
}) %}

{{ craft.formie.renderForm(form) }}
```

Another alternative is to redirect to the asset directly.

```twig
{% do form.setSettings({
    submitActionUrl: craft.assets.id(10839).one().url,
    submitAction: 'url',
    submitActionTab: 'new-tab',
}) %}
```

Here, we're fetching an asset with the ID `10839` and modifying the settings for the form to redirect to that asset in a new tab. However, I personally don't like this approach as it redirects the user off to the asset suddenly without any indication that the form was successful (or to just thank them for filling out the form). As such, it's quite jarring — but it may very well suit your use case.

On the next template, which will be loaded when the form has successfully submitted:

```twig
templates/download.html

{# Get the query string value for `submissionId` #}
{% set submissionId = craft.app.request.getParam('submissionId') %}

{# Ditch any requests that don't supply this #}
{% if not submissionId %}
    {% exit 404 %}
{% endif %}

{# Fetch the submission #}
{% set submission = craft.formie.submissions.uid(submissionId).one() %}

{# Ditch any requests that have supplied an invalid submission #}
{% if not submission %}
    {% exit 404 %}
{% endif %}

Thanks for filling out the form! Here are your resources:

{% set asset = craft.assets.id(10839).one() %}

{{ asset.link }}
```

Firstly, we're using the UID here because we don't want people to be able to guess a submission (read more about this in [Build a success page for your form](/guides/templating-theming/build-a-success-page-for-your-form)) and denying all access otherwise. 

You might also like to put some more checks in place, such as if the user is a Craft user, has paid for access, and more.

### Enforcing users
Maybe you have a requirement that not _anyone_ who fills out the form can access the resources. Maybe they need to be Craft users. We can add this check, thanks to the user providing an email address for us to check against a Craft user with.

```twig
{# Get the email address from the submission #}
{% set emailAddress = submission.getFieldValue('emailAddress') %}

{# Find a Craft user with the same email address #}
{% set user = craft.users.email(emailAddress).one() %}

{# Ditch any requests if no user found #}
{% if not user %}
    {% exit 404 %}
{% endif %}
```

### Checking payment
Let's take it a step further and add payment functionality to our form, which is pretty commonplace for selling e-books. You want to capture user data (particularly for marketing purposes), but also protect the e-book from being publicly accessible.

Add a Payment field to your form - we'd recommend Stripe.

Then, we can add some checks — _just_ in case the user has filled out the form, but for some reason, the actual payment has failed. The form submission should prevent this, but you can never be too careful! 

```twig
{# Assume unpaid until we check #}
{% set paid = false %}

{# Fetch all payments for this submission and check they were successful #}
{% for payment in submission.getPayments() %}
    {% if payment.status == 'success' %}
        {% set paid = true %}
    {% endif %}
{% endfor %}

{# Ditch any requests if unpaid #}
{% if not paid %}
    {% exit 404 %}
{% endif %}
```
