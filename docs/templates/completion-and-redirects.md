# Completion and Redirects

Choose **After Completion** in the form builder to control what a visitor sees when a form finishes. Message displays the success text, Redirect navigates to a URL or entry, Reload reloads the host page, and Reset clears the form. Advancing a page, going back and saving for later do not run these behaviours. Payment continuation has its own trusted result while the submission remains incomplete.

For a content-managed destination, resolve the entry in your Twig template and use the stable override before rendering:

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}
{% set destination = craft.entries.section('pages').slug('thanks').one() %}
{% if destination %}
    {% do form.setRedirectUrl(destination.url) %}
{% endif %}
{{ craft.formie.renderForm(form) }}
```

Redirect settings support Formie reference tokens with URL-component encoding. They do not execute arbitrary Twig. Formie validates the final URL after trusted template and PHP event overrides. Safe project-relative paths and configured site origins are allowed. Add deliberate external destinations in `config/formie.php`:

```php
<?php
return [
    'completionRedirectAllowedOrigins' => ['https://partner.example.com'],
    'completionQueryAllowlist' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'affiliate'],
];
```

Origins are exact scheme, host and port matches. Scheme-relative URLs, credentials in URLs, unsafe schemes, control characters and encoded CRLF are rejected. An invalid final target falls back to the completion message. New-tab browser navigation uses `noopener,noreferrer`.

The default forwarded parameters are `utm_source`, `utm_medium`, `utm_campaign`, `utm_term` and `utm_content`. An empty `completionQueryAllowlist` forwards nothing. Allowed scalar values are captured at journey start. Explicit parameters in the destination win, including explicit empty values. Nested data, credentials, Formie tokens and Craft security parameters are never forwarded by default.

HTML/Ajax and client-rendered REST/GraphQL results use the same completion outcome: `behavior`, `url`, `target`, `message` and `hideForm`. Custom adapters apply that result after completion; they must not infer completion from a missing next page. The PHP `CompletionResolver::EVENT_RESOLVE_COMPLETION` event receives a `SubmissionEvent`; set `redirectUrl` to override the target. Final validation applies afterward.

For native HTML submissions, a new-tab redirect displays a Continue link with `target="_blank"` and `rel="noopener noreferrer"`. Ajax and client-rendered adapters request the new window directly; browser popup policy still applies.
