# Relations between submissions and entries

Submissions can be related to Craft elements — entries, categories, products, users — so form data stays connected to the content it belongs to. Relations are set in Twig before render, not chosen manually by editors in the form builder.

## Prerequisites

- [Relations reference](/submissions/relations)

## When to use relations

- A contact form on an entry page should link submissions to that entry.
- Product enquiry forms should relate to the product being viewed.
- You need to query "all submissions for this entry" later in templates or integrations.

Relations complement **Entries fields** inside the form (where the user picks an entry). Template-set relations attach context the user did not choose — the current page, a related product, a campaign entry.

## Attach relations before render

```twig
{% set entry = craft.entries.slug(craft.app.request.getSegment(2)).one() %}
{% set product = craft.commerce.products.slug(craft.app.request.getParam('product')).one() %}
{% set form = craft.formie.forms.handle('enquiryForm').one() %}

{% if not entry or not form %}
    {% exit 404 %}
{% endif %}

{% set relations = [entry] %}
{% if product %}
    {% set relations = relations|merge([product]) %}
{% endif %}

{% do form.setRelations(relations) %}

{{ craft.formie.renderForm(form) }}
```

When the form submits, those elements are stored as relations on the submission.

## Read relations from a submission

```twig
{% set submission = craft.formie.submissions.id(3344).one() %}

{% if submission %}
    {% for element in submission.getRelations() %}
        <p>Related: {{ element.title ?? element.name }}</p>
    {% endfor %}
{% endif %}
```

## Find submissions for an element

```twig
{% set entry = craft.entries.id(2242).one() %}

{% for submission in craft.formie.getSubmissionRelations(entry) %}
    {{ submission.title }} — {{ submission.dateCreated|date }}
{% endfor %}
```

Use this on entry templates to show enquiry history, or in the CP via custom modules.

## Pattern: entry contact form

A common setup:

1. Create a `contactForm` form used site-wide.
2. On each entry template, load the form and call `setRelations([entry])`.
3. In the CP or a custom report, filter submissions related to an entry.

The form definition stays generic; the template supplies context per page.

## Pattern: product enquiry with hidden context

Combine relations with visible product fields:

- Relation stores the canonical Commerce product element.
- A read-only or calculated field shows the product title for the submitter's confirmation email.

Integrations can map relation element IDs to CRM product fields.

## Relations and editing

When editing a submission on the front end, relations set at original render are preserved unless your edit template calls `setRelations()` again. See [Editing submissions on the front end](/guides/submissions-workflows/editing-submissions-on-the-front-end).

## Relations vs Entries fields

| | Template relations | Entries field |
| --- | --- | --- |
| Who sets it | Developer in Twig | Form user |
| Visible in form | No | Yes |
| Use case | Page context | User picks from a list |

Use both when needed — relation for the page the user was on, Entries field when they must pick from a broader list.

## Cached pages

`setRelations()` runs when Twig renders the form. On statically cached pages, relations are fixed from the render that built the cache. Vary the cache by entry/product context, or avoid full-page caching on relation-heavy forms. See [Cached Forms](/frontend/cached-forms).

## Related

- [Relations](/submissions/relations)
- [Editing submissions on the front end](/guides/submissions-workflows/editing-submissions-on-the-front-end)
- [Entries field](/fields/entries)
