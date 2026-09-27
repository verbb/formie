# Completion and Redirects

Choose **After Completion** in the form builder to control what a visitor sees when a form finishes. Message displays the success text, Redirect navigates to a URL or entry, Reload reloads the host page, and Reset clears the form. Advancing a page, going back and saving for later do not run these behaviours. Forms requiring payment wait for payment to complete before running the chosen action.

To redirect to an entry, find it in your Twig template and set its URL before rendering. This example uses a `contactForm` form and a `thanks` entry in the `pages` section:

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}
{% set destination = craft.entries.section('pages').slug('thanks').one() %}
{% if destination %}
    {% do form.setRedirectUrl(destination.url) %}
{% endif %}
{{ craft.formie.renderForm(form) }}
```

Redirect settings support [reference tokens](/developers/reference-tokens), such as a submitted field value, but not Twig expressions. Formie allows paths within your site and URLs on your configured Craft sites. To redirect to another site, add its origin in `config/formie.php`:

```php
<?php
return [
    'completionRedirectAllowedOrigins' => ['https://partner.example.com'],
    'completionQueryAllowlist' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'affiliate'],
];
```

An origin includes the scheme, hostname and port, such as `https://partner.example.com`. It must match exactly. Use a path such as `/thanks` or a full HTTP/HTTPS URL; invalid destinations fall back to the completion message.

Formie can carry campaign parameters from the page where the visitor first loads the form to the destination. The five `utm_*` parameters shown above are enabled by default; this example also allows `affiliate`. Set `completionQueryAllowlist` to `[]` to forward none. Parameters already written into the destination URL take priority.

For redirects chosen in PHP, use the [completion redirect event](/developers/events/submission-events#override-the-completion-redirect). The same destination restrictions apply.

When opening the destination in a new tab, a page-reload form displays a Continue link. Ajax and client-rendered forms open the tab directly, subject to the browser’s popup settings.
