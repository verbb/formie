# PDF attachments with Email Notifications

Email notifications can attach a generated PDF — useful for invoices, application summaries, signed agreements, or printable copies of what the user submitted. Formie renders a Twig PDF template at send time and attaches the result. This walkthrough creates the template, links it to a notification, and covers layout options.

## Prerequisites

- [Email Notifications](/forms/email-notifications) on at least one form
- [PDF Templates](/templates/pdf-templates)
- A working Craft mail transport

## Overview

Three pieces work together:

1. **PDF template** — Twig that produces the PDF HTML (`Formie → Settings → PDF Templates`)
2. **Email notification** — who receives the email and what the message body says
3. **Attach setting** — notification enables **Attach PDF Template** and picks the template

Global PDF defaults (`pdfPaperSize`, `pdfPaperOrientation`) live in [Configuration](/get-started/configuration).

## Step 1 — Create the Twig template

Create a template file, for example `templates/_pdf/application-summary.html`:

```twig
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12pt; color: #222; }
        h1 { font-size: 18pt; margin-bottom: 0.5em; }
        .meta { color: #666; margin-bottom: 1.5em; }
        hr { border: none; border-top: 1px solid #ccc; margin: 1.5em 0; }
    </style>
</head>
<body>
    <h1>{{ form.title }}</h1>

    <p class="meta">
        Submitted on {{ submission.dateCreated|date('j M Y, g:ia') }}
        {% if notification.subject %}
            · {{ notification.subject }}
        {% endif %}
    </p>

    <hr>

    {% for field in submission.getFields() %}
        {% set value = submission.getFieldValueAsString(field.handle) %}
        {% if value %}
            <p><strong>{{ field.name }}:</strong> {{ value }}</p>
        {% endif %}
    {% endfor %}
</body>
</html>
```

Available variables:

| Variable | Description |
| --- | --- |
| `notification` | Current notification model |
| `submission` | Submission element |
| `form` | Form element |
| `contentHtml` | HTML from the notification's email content field |

## Step 2 — Register the PDF template in Formie

1. Go to **Formie → Settings → PDF Templates**
2. Create a new template (for example `Application Summary`)
3. Set **HTML Template** to your Twig path (`_pdf/application-summary` or folder)
4. Save

Configure paper size and orientation in the template settings or rely on global `pdfPaperSize` / `pdfPaperOrientation` in `config/formie.php` (`letter` / `portrait` by default).

## Step 3 — Enable attachment on the notification

1. Open the form in the form builder
2. Edit the email notification (admin copy or user confirmation)
3. In template settings, enable **Attach PDF Template**
4. Select **Application Summary**
5. Save the form

When the notification sends, Formie renders the PDF template and attaches the file.

## Step 4 — Use contentHtml for shared body copy

If the PDF should mirror the email body, wrap `contentHtml`:

```twig
<h1>{{ form.title }} — Submission copy</h1>

<p>Submitted {{ submission.dateCreated|date('j M Y') }}</p>

<hr>

{{ contentHtml|raw }}
```

The email content field stays the single source for message copy; the PDF wraps it with print-friendly layout.

## Step 5 — Set notification defaults (optional)

Pre-enable PDF attachment for new notifications project-wide:

```php [config/formie.php]
return [
    '*' => [
        'notificationDefaults' => [
            'attachPdf' => true,
            'pdfTemplateId' => null, // authors pick template in CP
        ],
    ],
];
```

Form [groups](/forms/form-groups) can override notification defaults per team.

## Step 6 — Test send

1. Open the notification **Preview** tab for a quick render check
2. Use **Send test email** to verify attachment size, fonts, and mail client behaviour
3. Submit a real test entry on the front end

Large file upload fields increase PDF generation time — keep `useQueueForNotifications` enabled in production.

## File uploads in PDFs

File Upload field values in PDFs are typically filenames or links depending on field summary settings. For images embedded in PDFs, custom Twig referencing asset URLs may be needed — test with your PDF renderer and mail provider attachment limits.

For arbitrary extra files (not generated PDFs), see [Attaching extra assets to Email Notifications](/guides/email-notifications/attaching-extra-assets-to-email-notifications).

## Branded PDF layout

For headers, footers, and brand colours shared across PDFs, use a base Twig layout and extend it in each PDF template — same pattern as [Building an Email Notification template from scratch](/guides/email-notifications/building-an-email-notification-template-from-scratch) for HTML email.

## Troubleshooting

**PDF not attached**

- Confirm **Attach PDF Template** is enabled on the notification
- Verify the PDF template HTML path exists (`validateCustomTemplates` may block invalid paths)
- Check queue jobs if notifications are queued — failed jobs log in Craft queue

**Empty PDF**

- Ensure submission has field values at send time (notifications fire after save)
- Check field visibility and `includeInEmailFieldSummaries` if iterating selectively

**Wrong site language in PDF**

- Submissions load forms with site override merge — PDF uses submission `siteId`
- For multi-site copy, see [Multi-site notification content](/guides/email-notifications/multi-site-notification-content)

## Related

- [PDF Templates](/templates/pdf-templates)
- [Email Notifications](/forms/email-notifications)
- [Building an Email Notification template from scratch](/guides/email-notifications/building-an-email-notification-template-from-scratch)
- [Attaching extra assets to Email Notifications](/guides/email-notifications/attaching-extra-assets-to-email-notifications)
