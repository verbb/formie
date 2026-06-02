# PDF Templates

PDF templates are used when a notification should attach a generated PDF.

The control-panel choice to attach a PDF belongs with [Email Notifications](/forms/email-notifications). PDF templates cover the Twig template that produces the PDF itself.

## Creating a PDF template

Start by creating a Twig template for the PDF output. For example, you might create `templates/_pdf/index.html` with:

```twig
<h1>Thanks for your email enquiry. The below email was sent from {{ siteName }}.</h1>

{{ contentHtml }}
```

Then go to `Formie → Settings → PDF Templates` and create a new PDF template. Point its `HTML Template` setting at the folder or template path you want Formie to use.

Once that is saved, edit the email notification that should attach the PDF. In the notification's template settings, enable `Attach PDF Template` and choose the PDF template you want to use.

When the notification is sent, Formie will render the Twig template and attach the resulting PDF to the email.

## Available variables

Your PDF template has access to the following variables:

| Variable | Description |
| --- | --- |
| `notification` | The current [Notification](/reference/notification) object. |
| `submission` | The current [Submission](/reference/submission) object. |
| `form` | The current [Form](/reference/form) object. |
| `contentHtml` | The HTML generated from the notification's email content. |

This gives you two common approaches:

- build the PDF entirely from your own Twig template
- use `contentHtml` as the base and wrap it with your own heading, footer, or layout

## Example template

```twig
<h1>{{ form.title }}</h1>

<p>Submitted on {{ submission.dateCreated|date('j M Y, g:ia') }}</p>

{% if notification.subject %}
    <p><strong>Notification:</strong> {{ notification.subject }}</p>
{% endif %}

<hr>

{% for field in submission.getFields() %}
    {% set value = submission.getFieldValueAsString(field.handle) %}

    {% if value %}
        <p><strong>{{ field.name }}:</strong> {{ value }}</p>
    {% endif %}
{% endfor %}
```
