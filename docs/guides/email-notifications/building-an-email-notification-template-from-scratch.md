# Building an Email Notification template from scratch

When someone submits your form, Formie sends notification emails through Craft's mailer — but the default content is functional, not branded. This walkthrough takes a contact form notification from plain field output to a responsive, on-brand email that matches the rest of your project.

You will create a Formie Email Template record, wire it to a test notification, and build a reusable layout (with optional MJML) that wraps Formie's notification body content.

## Before you start

You need a form with at least one notification enabled — the Contact Form stencil (name, email, message) is a good starting point. Enable the **user-facing** notification for testing if you want to see the styled email in your inbox; admin notifications use the same template machinery.

Create an [Email Template](/forms/email-notifications) record in Formie and point **HTML Template** at a Twig path such as `_forms/email-template`. Assign that template on the notification you are testing.

## Layout with MJML (optional)

[ MJML](https://mjml.io/) simplifies responsive email HTML. Install the [MJML Craft plugin](https://plugins.craftcms.com/mjml) and the MJML CLI globally if you use the `mjmlCli` filter.

You can also write plain HTML — the structure below still applies.

### Email layout

`/templates/_emails/_layout.html`:

```twig
{% set fontSize = '14px' %}
{% set lineHeight = '24px' %}
{% set fontFamily = '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Fira Sans, Droid Sans, Helvetica Neue, sans-serif' %}

{%- filter mjmlCli -%}
<mjml>
    <mj-head>
        <mj-title>{{ emailSubject ?? siteName }}</mj-title>
        <mj-attributes>
            <mj-all font-family="{{ fontFamily }}"></mj-all>
            <mj-text font-weight="400" font-size="{{ fontSize }}" color="#000000" line-height="{{ lineHeight }}"></mj-text>
            <mj-section padding="20px 0"></mj-section>
        </mj-attributes>
    </mj-head>
    <mj-body background-color="#ffffff">
        <mj-section>
            <mj-column>
                {% set logo = craft.rebrand.logo %}
                {% if logo %}
                    <mj-image align="center" src="{{ logo.getDataUrl() }}" width="100px"></mj-image>
                {% else %}
                    <mj-text align="center" font-size="40px">{{ siteName }}</mj-text>
                {% endif %}
            </mj-column>
        </mj-section>
        {% block content %}{% endblock %}
        <mj-section padding="30px 0 0" full-width="full-width">
            <mj-column width="100%">
                <mj-divider padding="0px" border-width="1px" border-color="#e5e5e5"></mj-divider>
                <mj-text padding="15px 0 0 0" color="#999" font-size="14px">
                    If you have any questions, reply to this email or contact us at
                    <a href="mailto:{{ getenv('EMAIL_FROM') }}" style="color: #003E84; text-decoration: none;">{{ getenv('EMAIL_FROM') }}</a>
                </mj-text>
            </mj-column>
        </mj-section>
    </mj-body>
</mjml>
{%- endfilter -%}
```

The `{% block content %}` is extended by per-email templates — same pattern as site Twig layouts.

### Craft system email wrapper

`/templates/_emails/index.html`:

```twig
{% extends '_emails/_layout' %}

{% block content %}
<mj-section>
    <mj-column>
        <mj-raw>
            <div style="font-size: {{ fontSize }}; line-height: {{ lineHeight }}; font-family: {{ fontFamily }};">{{ body }}</div>
        </mj-raw>
    </mj-column>
</mj-section>
{% endblock %}
```

Point **Settings → Email → HTML Email Template** to `_emails` so Craft system mail uses the same shell.

### Formie notification wrapper

`/templates/_forms/email-template/index.html`:

```twig
{% extends '_emails' %}

{% set body %}
    {{ contentHtml }}
{% endset %}
```

Formie injects `contentHtml` the same way Craft injects `body` for system messages.

## Override field HTML in notifications

When you use **All Fields** in a notification, Formie renders each field with small Twig partials. Override them under your email template path.

Example — `/templates/_forms/email-template/fields/email.html`:

```twig
<p>
    <strong>{{ field.name | t('formie') }}</strong><br>
    {% if value %}
        <a href="mailto:{{ value }}">{{ value }}</a>
    {% endif %}
</p>
```

See [Template Overrides](/theming/template-overrides) for the full override map.

## Related

- [Email Notifications](/forms/email-notifications)
- [Template Overrides](/theming/template-overrides)
