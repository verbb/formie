# How to manually set the page for a form

Multi-page forms usually progress in order, but sometimes you want to land users on a specific page — for example, skipping introductory questions when they arrive from a campaign link, or offering a "commercial enquiry" entry point alongside a residential one.

## Prerequisites

- A multi-page Formie form
- [Rendering Forms](/templates/rendering-forms) or [Rendering Pages](/templates/rendering-pages) on the front end

## Set the page from the URL

Suppose you have a four-page enquiry form. Pages 1–2 apply to residential customers, page 3 to commercial, and page 4 to both. You could use [page conditions](/forms/conditions), but if you already know the audience from the URL, setting the starting page in Twig is simpler.

```twig
{% set form = craft.formie.forms.handle('enquiry').one() %}
{% set isCommercial = craft.app.request.getParam('commercial') %}

{% if isCommercial %}
    {% set pages = form.getPages() %}
    {% do form.setCurrentPage(pages[2]) %}
{% endif %}

{{ craft.formie.renderForm(form) }}
```

Send residential users to `/enquiry` and commercial users to `/enquiry?commercial=1`. The form opens on page 3 (zero-indexed as `pages[2]`) for commercial visitors.

## Set the page with a button

You might prefer explicit buttons — "I'm a residential customer" and "I'm a commercial customer" — that jump to the right page when clicked. Post to Formie's `set-page` action with the target page ID:

```twig
{% set form = craft.formie.forms.handle('enquiry').one() %}
{% set pages = form.getPages() %}

<form method="post">
    {{ actionInput('formie/submissions/set-page') }}
    {{ hiddenInput('handle', form.handle) }}
    {{ hiddenInput('pageId', pages[2].id) }}
    {{ csrfInput() }}

    <button type="submit">Skip to commercial enquiry</button>
</form>
```

This stores the page navigation state server-side and redirects back to the form. The difference from `setCurrentPage()` in Twig is that this works after a POST, which can be useful when the choice happens on a page that is separate from the form itself.

## When to use page conditions instead

Manual page selection suits fixed entry points — campaign URLs, audience-specific landing pages, or explicit skip buttons. When the starting page should depend on an answer the user gives on the form, [page conditions](/forms/conditions) are usually clearer because the logic lives in the form builder.

## Related

- [Rendering Pages](/templates/rendering-pages)
- [Rendering Forms](/templates/rendering-forms)
- [Conditions](/forms/conditions)
