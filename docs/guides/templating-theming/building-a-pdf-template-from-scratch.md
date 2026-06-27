# Building a PDF template from scratch

PDF templates let Formie attach a generated PDF when an email notification sends — useful for invoices, certificates, signed agreements, or printable summaries. This guide walks through creating a PDF template from scratch and attaching it to a notification.

## Prerequisites

- A form with at least one [Email Notification](/forms/email-notifications)
- A PDF library configured in Craft (Dompdf is bundled with Formie)
- [PDF Templates](/templates/pdf-templates) reference

## How PDF attachments work

The flow has three pieces:

1. A **Twig template** that produces the PDF HTML
2. A **PDF Template** record in Formie pointing at that Twig file
3. An **Email Notification** with **Attach PDF Template** enabled

When the notification sends, Formie renders your Twig template to HTML, converts it to PDF, and attaches the file to the email.

## Create the Twig template

Start with a simple layout. PDF renderers work best with straightforward HTML and inline-friendly CSS:

```twig
{# templates/_pdf/submission-summary.html #}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #333; }
        h1 { font-size: 22px; margin-bottom: 8px; }
        .meta { color: #666; margin-bottom: 24px; }
        .field { margin-bottom: 12px; }
        .field-label { font-weight: bold; }
        hr { border: none; border-top: 1px solid #ddd; margin: 24px 0; }
    </style>
</head>
<body>
    <h1>{{ form.title }}</h1>

    <p class="meta">
        Submitted {{ submission.dateCreated|date('j M Y, g:ia') }}
        {% if notification.subject %}
            · {{ notification.subject }}
        {% endif %}
    </p>

    <hr>

    {% for field in submission.getFields() %}
        {% set value = submission.getFieldValueAsString(field.handle) %}

        {% if value %}
            <div class="field">
                <span class="field-label">{{ field.name }}:</span>
                {{ value }}
            </div>
        {% endif %}
    {% endfor %}
</body>
</html>
```

### Available variables

| Variable | Description |
| --- | --- |
| `notification` | The current notification object |
| `submission` | The submission being sent |
| `form` | The form the submission belongs to |
| `contentHtml` | HTML from the notification's email body |

You can build the PDF entirely from your own template, or wrap `contentHtml` when you want the PDF to mirror the email content:

```twig
<h1>Submission receipt</h1>
<hr>
{{ contentHtml|raw }}
```

## Register the PDF Template in Formie

1. Go to **Formie → Settings → PDF Templates**
2. Create a new PDF template
3. Set **HTML Template** to your template path (for example `_pdf/submission-summary`)
4. Save

## Attach to an email notification

1. Edit the form's email notification
2. Under template settings, enable **Attach PDF Template**
3. Choose the PDF template you created
4. Save the form

Submit the form to test. The notification email should include the PDF attachment.

## Improve field output

`getFieldValueAsString()` is a good default for PDF text. For complex fields, choose a method that matches the output you need:

```twig
{# Address as a single line #}
{{ submission.getFieldValueAsString('billingAddress') }}

{# Summary HTML (use carefully in PDF — test rendering) #}
{{ submission.getFieldValueForSummary('billingAddress')|raw }}
```

PDF engines vary in CSS support. Keep layouts simple; avoid flexbox features that Dompdf handles poorly; test with real submission data including file uploads and signatures.

## Add branding

Extend the template with your site identity:

```twig
<header>
    <img src="{{ siteUrl }}assets/logo.png" alt="{{ siteName }}" width="160">
    <p>{{ siteName }} · {{ craft.app.sites.currentSite.baseUrl }}</p>
</header>
```

Use absolute URLs for images — relative paths often fail in PDF generation.

## Filter which fields appear

Skip empty or cosmetic fields:

```twig
{% for field in submission.getFields() %}
    {% if field.includeInEmailFieldSummaries %}
        {% set value = submission.getFieldValueAsString(field.handle) %}
        {% if value %}
            <div class="field">
                <span class="field-label">{{ field.name }}:</span>
                {{ value }}
            </div>
        {% endif %}
    {% endif %}
{% endfor %}
```

Adjust the condition to match your needs — `includeInEmailFieldSummaries`, field type checks, or an allow-list of handles.

## Troubleshooting

**Blank PDF** — Confirm the HTML Template path is correct and the Twig file renders without errors. Check Craft logs after sending.

**Missing images** — Use fully qualified URLs. Verify Dompdf can reach the asset.

**Garbled complex fields** — Switch from `getFieldValue()` to `getFieldValueAsString()` or summary output.

**PDF not attached** — Confirm **Attach PDF Template** is enabled on the notification and the PDF Template record is saved.

## Related

- [PDF Templates](/templates/pdf-templates)
- [Email Notifications](/forms/email-notifications)
- [Submission Content](/developers/submission-content)
- [Building an Email Notification template from scratch](/guides/email-notifications/building-an-email-notification-template-from-scratch)
