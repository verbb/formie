# Building an Email Notification template from scratch

Craft gives you the ability to set a template for all emails that are sent through the main Craft settings. Because Formie's email notifications use Craft's mailer service, we can make use of that too. The result is styled, and on-brand email templates that fit in with the rest of your project's emails.

### Email Template
To use specific templates for an email, we'll need to create an **Email Template**. This is all covered in the [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/email-templates), so once you've followed those steps, we'll pick it up from there.

We're going to set the **HTML Template** setting to `/templates/_forms/email-template`.

### Create your form
First thing's first, we'll need a form. We're going to use the default **Contact Form** stencil containing a name, email and message field. The form will need to have at least one email notification, and the stencil will contain 2. Let's just enable the **User Notification** for testing, and that'll likely be the one we'll want to style anyway (as opposed to the admin email notification). Be sure to set the **Email Template** setting in the notification to the custom Email Template you created in the previous step.

With those pieces set up, we can jump into the code.

### Template
We're going to use [MJML](https://mjml.io/) to construct our email templates, as it makes creating responsive, cross-browser templates a breeze. There's an [MJML Craft Plugin](https://plugins.craftcms.com/mjml) available to install, so download and install that. You'll also need to have MJML available on the command line, as a globally-installed NPM package.

You can also write regular HTML if you prefer, this guide will still work regardless.

We'll create two files, a layout and an index file. This will be completely compatible with Craft's own system emails, and we'll show how we can make use of that with Formie.

```twig
/templates/_emails/_layout.html

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

                <mj-text padding="15px 0 0 0" color="#999" font-size="14px">If you have any questions, reply to this email or contact us at <a href="mailto:{{ getenv('EMAIL_FROM') }}" style="color: #003E84; text-decoration: none;">{{ getenv('EMAIL_FROM') }}</a></mj-text>
            </mj-column>
        </mj-section>
    </mj-body>
</mjml>

{%- endfilter -%}
```

Here, we're creating a basic layout for our email using MJML syntax. Using the `mjmlCli` will spin up the command line instance of the MJML compiler and convert the template at runtime, so there's no need to compile it as a separate step.

You'll notice that we include a `{% block content %}`, which we can use in other templates that extend this layout. This should look incredibly familiar to your regular Twig templates!

Now onto the email template that we'll use for system emails.

```twig
/templates/_emails/index.html

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

Short and sweet! We extend from the layout we've created and in the `content` block, include the body of content to show. This is compatible with Craft's system emails, which inject a `body` variable to this template.

As such, you can navigate to **Settings** → **Email** and use this template for the **HTML Email Template** setting (set to `_emails`).

Now that Craft is using our template, we'll ensure Formie does too.

```twig
/templates/_forms/email-template/index.html

{% extends '_emails' %}

{% set body %}
    {{ contentHtml }}
{% endset %}
```

Just as easy! Formie will do the same thing as Craft's system emails, by injecting a `contentHtml` variable to the template. We can then use that to set the `body` variable that our `_emails/index.html` file uses.

Now we've got all our forms playing the same game!

### Field Templates
Now that we've got the outer style of the email looking good, we might want to tweak the inner content that's produced by Formie. The body content of an email notification is already HTML when you write it in the settings, but we can also control the HTML used by fields.

For example, when using the variable picker to select **All Form Fields**, Formie will use HTML templates for each field to generate the content so it works well in an email format. But you can override these templates.

Outlined in the [custom templates](https://verbb.io/craft-plugins/formie/docs/template-guides/email-templates#custom-templates) docs, let's add a template to override the Email field.

Create the following Twig template:

```twig
/templates/_forms/email-template/fields/email.html

<p>
    <strong>{{ field.name | t('formie') }}</strong><br>

    {% if value %}
        <a href="mailto:{{ value }}">{{ value }}</a>
    {% endif %}
</p>
```

You're free to manipulate this template however you like, but we've added an anchor tag with a `mailto` link for the value of the email.
