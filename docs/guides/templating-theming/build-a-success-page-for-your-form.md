# Build a Success Page for Your Form

A success page confirms that a visitor has finished your form. This guide first creates a public thank-you page that shows no saved answers, then adds an account-only summary for forms completed by signed-in users.

## Create a Public Thank-You Page

Start with an existing form whose handle is `contactForm`. A handle is the name you use to find the form in code. In the form builder, choose a URL redirect as the submit action and set its destination to `/thanks`. Save the form.

Create `templates/contact.twig` in your Craft project:

```twig
{{ craft.formie.renderForm('contactForm') }}
```

Create `templates/thanks.twig`:

```twig
<h1>Thanks for Getting in Touch</h1>
<p>Your enquiry has been submitted. Our team will reply using the details you provided.</p>
```

Open `/contact`, complete the form and submit it. You should arrive at `/thanks`; check **Formie → Submissions** to confirm the saved answers. This works with both Ajax and page-reload submission methods when the submit action redirects.

Anyone can open the thank-you URL. It confirms the normal submission journey, but does not prove that the person viewing it submitted a form. Keep personal information, paid resources and other restricted content off this public page.

## Show Answers to the Signed-In Submitter

For an account area, you can show a saved submission after checking its owner. This example requires Craft user accounts, a working sign-in page and a form completed while the visitor is signed in. Enable **Collect User** under the form’s **Settings → Privacy** (`collectUser`); this records the signed-in Craft user as the submission owner. An Email Address field alone does not establish ownership.

Replace `templates/contact.twig` with:

```twig
{% requireLogin %}
{% header "Cache-Control: private, no-store" %}

{% set form = craft.formie.forms().handle('contactForm').one() %}
{% if not form %}
    {% exit 404 %}
{% endif %}

{% do form.setSettings({
    collectUser: true,
    submitAction: 'url',
    submitActionUrl: '/thanks?submissionUid={submission:uid}',
}) %}

{{ craft.formie.renderForm(form) }}
```

Replace `templates/thanks.twig` with:

```twig
{% requireLogin %}
{% header "Cache-Control: private, no-store" %}
{% header "Referrer-Policy: no-referrer" %}

{% set submissionUid = craft.app.request.getQueryParam('submissionUid') %}
{% if not submissionUid or submissionUid is iterable or not (submissionUid matches '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i') %}
    {% exit 404 %}
{% endif %}

{% set submission = craft.formie.submissions()
    .form('contactForm')
    .siteId(currentSite.id)
    .uid(submissionUid)
    .userId(currentUser.id)
    .isIncomplete(false)
    .isSpam(false)
    .one() %}

{% if not submission %}
    {% exit 404 %}
{% endif %}

<h1>Thanks for Your Enquiry</h1>

{% for field in submission.getFields() %}
    {% set value = submission.getFieldValueAsString(field.handle) %}
    {% if value %}
        <p><strong>{{ field.name }}</strong><br>{{ value }}</p>
    {% endif %}
{% endfor %}
```

The UID identifies the submission; the `userId` and form filters decide whether it belongs in this account page. A missing, invalid or other account’s UID returns 404. Keep Twig escaping enabled and limit the displayed fields if the form collects information that should not appear in an account summary.

Exclude both URLs from any full-page cache or CDN caching. The response headers help browsers and proxies, but an upstream cache must also be configured to bypass these routes. Do not send submission identifiers or answers to analytics.

## Verify the Result

Sign in as a test user and submit the form. Confirm that the summary shows that submission’s answers. Open its URL while signed out; Craft should require sign-in. Sign in as a different user and open the same URL; it should return 404. Repeat with a missing UID and a UID from a different form.

Existing submissions without an owner will not appear through this pattern. Do not infer their owner from a submitted email address. For anonymous forms, use the public confirmation above, or implement a separately reviewed, expiring access-link flow before displaying private answers.

For formatted field output, see [Submission Content](/developers/submission-content). For authorised editing, see [Editing Submissions](/templates/editing-submissions).
