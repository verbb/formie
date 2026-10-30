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

To send visitors to different external sites based on their answers, use conditional redirect rules with a fixed URL for each destination.

Formie can carry campaign parameters from the page where the visitor first loads the form to the destination. The five `utm_*` parameters below are enabled by default; this example also allows `affiliate` in `config/formie.php`:

```php
<?php
return [
    'completionQueryAllowlist' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'affiliate'],
];
```

Set `completionQueryAllowlist` to `[]` to forward none. Parameters already written into the destination URL take priority.

For redirects chosen in PHP, use the [completion redirect event](/developers/events/submission-events#override-the-completion-redirect). Your handler can choose an external HTTP/HTTPS URL. Keep that choice in server-side code rather than copying a visitor-supplied URL into the event.

When opening the destination in a new tab, a page-reload form displays a Continue link. Ajax and client-rendered forms open the tab directly, subject to the browser’s popup settings.
