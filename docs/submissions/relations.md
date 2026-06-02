# Relations

Submissions can be related to other elements, not just left as standalone records.

That is helpful when a submission belongs to something else on the site, such as an entry, product, or another business object.

Use submission relations when:

- the submission should be connected to a specific entry or product
- you want to find submissions related to another element later
- the relation should be set by the template, not chosen manually by an editor

You can attach related elements before rendering the form.

```twig
{% set entry = craft.entries.id(2242).one() %}
{% set product = craft.products.id(37).one() %}
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setRelations([entry, product]) %}

{{ craft.formie.renderForm(form) }}
```

When the form is submitted, those elements are stored as relations on the submission.

Once the submission exists, you can read its related elements back out.

```twig
{% set submission = craft.formie.submissions().id(3344).one() %}

{% if submission %}
    {% for element in submission.getRelations() %}
        {{ element.title }}
    {% endfor %}
{% endif %}
```

You can also find submissions related to an existing element.

```twig
{% set entry = craft.entries.id(2242).one() %}

{% for submission in craft.formie.getSubmissionRelations(entry) %}
    {{ submission.title }}
{% endfor %}
```

This feature is about managing stored submission data and how it connects to the rest of your site, even when the examples are template-based.
