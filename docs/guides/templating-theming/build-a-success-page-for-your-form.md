# Build a success page for your form

Once users have filled out a form, you can configure what action to take. From showing a message, hiding the form, or redirecting to an entry or URL. But you might like to provide users with a "success" page that shows their submission information.

The content on these success pages will be entirely up to you, but we'll show a summary of the users' submission as an example. We'll cover two approaches, either reloading the page or redirecting away to another template.

:::tip
Don't forget you can show users a summary of the form submission **before** they submit the form, using the Summary field.
:::

### On the same page
You might like to keep the user on the same page as the form. However, you probably don't want to show the form again, or even the message. Instead, you'll have total control over what to show the user.

:::warning
This will only work for a form with Page Reload set as their **Submission Method**. This is because the template will need to be rendered server-side. You can achieve similar results using an Ajax-based form, but the bulk of the work will be in JavaScript.
:::

Let's put together an example that reminds the user what they entered. First, create your form. We're going to use the default **Contact Form** stencil containing a name, email and message field.

Next, ensure the **Submission Method** is **Page Reload** in the form settings.

Then, in our templates, let's render the form.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setSettings({
    redirectUrl: craft.app.request.url ~ '?submissionId={id}',
}) %}

{{ craft.formie.renderForm(form) }}
```

You'll notice that we're modifying the `redirectUrl` setting for the form. This will be set to the current URL when viewing the form, with a `submissionId={id}` appended as a query string. This `{id}` placeholder will be swapped out with the Submission ID once the form has been submitted.

Once submitted, the URL will look similar to `https://formie.test/contact-us?submissionId=1234`. Try submitting the form. You'll notice that the form submits and reloads the page with the URL changed — great!

Next, we'll change our template to show a summary of the submission instead of the form.

```twig
{# Keep track of whether we've submitted the form on not, show/hide the form #}
{% set formSubmitted = false %}

{# Get the query string value for `submissionId` #}
{% set submissionId = craft.app.request.getParam('submissionId') %}

{% if submissionId %}
    {# Fetch the submission #}
    {% set submission = craft.formie.submissions.id(submissionId).one() %}

    {% if submission %}
        {% set formSubmitted = true %}

        {# Loop through each field in the submission, showing its content #}
        {% for field in submission.getFieldLayout().getCustomFields() %}
            {# Get the value for the field, from the submission #}
            {% set value = submission.getFieldValue(field.handle) %}

            <p>
                <strong>{{ field.name }}</strong><br>

                {# Render the value as a string (some fields have complex values) #}
                {{ field.getValueAsString(value, submission) }}
            </p><br>
        {% endfor %}
    {% endif %}
{% endif %}

{# Only show the form if we've not submitted it #}
{% if not formSubmitted %}
    {% set form = craft.formie.forms.handle('contactForm').one() %}

    {% do form.setSettings({
        redirectUrl: craft.app.request.url ~ '?submissionId={id}',
    }) %}

    {{ craft.formie.renderForm(form) }}
{% endif %}
```

Now, we're fetching the `submissionId` query param, so we can fetch the Submission element we've just created by filling out the form. It's a good idea to add conditional checks along the way. Then, we loop through each field in the submission, and output it. We should get something similar to:

```
**Your Name**
Peter Sherman

**Email Address**
psherman@wallaby.com

**Message**
Just testing out the success page.
```

Of course, you can make this your own from here!

:::tip
Have a look at [The complete guide to rendering submission content](/guides/templating-theming/the-complete-guide-to-rendering-submission-content) user guide for more ways to output submission content!
:::

### A new template
The above is a great start, and does keep things contained within a single template, but you might find it more maintainable to separate the templates. This approach will also work for Ajax-based forms, as we're redirecting to another page anyway.

Fortunately, we've done the bulk of the work already! We just need to split the templates up. Create the following templates:

```twig
templates/form.html

{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setSettings({
    redirectUrl: '/thanks?submissionId={id}',
}) %}

{{ craft.formie.renderForm(form) }}
```

```twig
templates/thanks.html

{# Get the query string value for `submissionId` #}
{% set submissionId = craft.app.request.getParam('submissionId') %}

{# Ditch any requests that don't supply this #}
{% if not submissionId %}
    {% exit 404 %}
{% endif %}

{# Fetch the submission #}
{% set submission = craft.formie.submissions.id(submissionId).one() %}

{# Ditch any requests that have supplied an invalid submission #}
{% if not submission %}
    {% exit 404 %}
{% endif %}

{# Loop through each field in the submission, showing its content #}
{% for field in submission.getFieldLayout().getCustomFields() %}
    {# Get the value for the field, from the submission #}
    {% set value = submission.getFieldValue(field.handle) %}

    <p>
        <strong>{{ field.name }}</strong><br>

        {# Render the value as a string (some fields have complex values) #}
        {{ field.getValueAsString(value, submission) }}
    </p><br>
{% endfor %}
```

You'll notice this is largely the same with just a few changes. We're redirecting to `/thanks` instead of the same page, and we've added in some security checks in case someone provides an invalid (or none) submission ID. Personally, I think the "exit-early" flow keeps things a bit easier to read, saving logic from being overly nested.

We'll get the same behaviour as before, instead of being taken off to a new template.

### Security/Privacy
One thing to consider is that for both these approaches, we rely on the submission ID being passed via the URL as a query parameter. Through this, we fetch the submission for a given ID. As you might be able to figure out, this may be a privacy concern, as theoretically, anyone could guess the ID of a submission and view another user's submission. While submission IDs aren't always sequential, it'd be trivial for bad actors to loop through your success template with IDs for a given range.

One approach to address this is to instead use the UID of the submission, instead of the ID. This comes in the format `92a47329-67f6-42c6-99e3-eb5b17ed7476` which is next to impossible to guess and is unique and random for every submission.

Swapping over to UID is straightforward, and only really requires two line changes:

```twig
templates/form.html

{% do form.setSettings({
    redirectUrl: '/thanks?submissionId={uid}',
}) %}
```

```twig
templates/thanks.html

{% set submission = craft.formie.submissions.uid(submissionId).one() %}

...
```

Whether this is something that you're concerned with is completely up to your use case.
