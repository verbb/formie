# Editing Submissions
You can allow your users to edit their submissions from the front end with the following Twig template code. Feel free to alter to your needs!

First, let's get a collection of submissions for the `contactForm` form. We'll want to restrict submissions to the current users' own submissions - we don't want them being able to edit other people's form submissions!

```twig
{% set submissions = craft.formie.submissions.form('contactUs').email(currentUser.email).all() %}

{% for submission in submissions %}
    <a href="account/form/{{ submission.id }}">Edit {{ submission.title }}</a>
{% endfor %}
```

In this example, our `contactForm` form has a field with a handle `email` - your form may be different. You might like to link off to an individual template for each submission, which we've shown above. It'll be up to you to set up your template structure. 

In our example, we have a route setup to handle `account/form/{{ submission.id }}`.

On this template, we fetch the submission from the provided submission ID in the URL

```twig
{% set submissionId = craft.app.request.getSegment(3) %}
{% set submission = craft.formie.submissions.id(submissionId).one() %}

{% if not submission %}
    {% exit 404 %}
{% endif %}
```

Then, to actually output the form for the user to edit - the easiest option is to use `renderForm()`, like you would output a normal form.

```twig
{# Tell Formie we're editing a submission #}
{% do submission.form.setSubmission(submission) %}

{{ craft.formie.renderForm(submission.form) }}
```

This will output your form just as it would for a regular form submission, including CSS/JS and all other configurations, but with the bonus of your form content being populated on the form. The `setSubmission()` call tell's the Form that you're editing a submission, instead of creating a new one, so integrations like Captchas are disabled. There's no need to protect from spam if the submission has already been made.

You can also take complete control over the form's HTML if you wish.

```twig
{# Tell Formie we're editing a submission #}
{% do submission.form.setSubmission(submission) %}

{# Register any CSS/JS for the form, as we aren't using `renderForm()` #}
{% do craft.formie.registerAssets(submission.form) %}

<form method="post" data-fui-form="{{ submission.form.configJson }}">
    {{ hiddenInput('action', 'formie/submissions/save-submission') }}
    {{ hiddenInput('handle', submission.form.handle) }}
    {{ hiddenInput('submissionId', submission.id) }}
    {{ hiddenInput('editingSubmission', true) }}
    {{ hiddenInput('submissionEditToken', submission.form.getSubmissionEditToken()) }}
    {{ hiddenInput('siteId', submission.siteId) }}
    {{ csrfInput() }}

    {% for field in submission.form.getFields() %}
        {{ craft.formie.renderField(submission.form, field) }}
    {% endfor %}

    <button type="submit" data-submit-action="submit">Save</button>
</form>
```

## Deleting Submissions

Logged-in users can delete their **own** submissions from the front end without Formie control panel delete permissions. The form must have **Collect User** enabled so submissions are tied to the user.

Always query submissions for the current user — never trust a raw ID from the request alone in your template:

```twig
{% set submission = craft.formie.submissions
    .id(submissionId)
    .user(currentUser)
    .isIncomplete(true)
    .one() %}

{% if submission %}
    <form method="post">
        {{ csrfInput() }}
        {{ actionInput('formie/submissions/delete-submission') }}
        {{ hiddenInput('submissionId', submission.id) }}
        {{ redirectInput('account/submissions') }}

        <button type="submit">Delete</button>
    </form>
{% endif %}
```

`delete-submission` only allows the delete when the submission’s `userId` matches the current user (or the user has Formie delete permissions). Other users’ submissions cannot be deleted this way.
