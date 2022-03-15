# Relations
When submitting a form in your templates, you can optionally choose to relate other elements to that submission, without needing to create an element field to relate to.

For example, you might want to relate a specific entry or product element with a form submission. Whilst you could create an Entry or Product element field, you might not want admin's to be able to edit this relation. Through this approach, you can essentially set a hidden relationship to other elements and the submission.

Let's look at an example.


```twig
{% set entry = craft.entries.id(2242).one() %}
{% set product = craft.products.id(37).one() %}

{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do form.setRelations([entry, product]) %}

{{ craft.formie.renderForm(form) }}
```

Here, we're fetching a specific entry and product and calling `setRelations()` on the form. When the form is submitted, these elements will be recorded against the submission.

You might like to access these relations on your submissions.

```twig
{% set submission = craft.formie.submissions().id(3344).one() %}

{% if submission %}
    Elements related to this submission

    {% for elementRelation in submission.getRelations() %}
        {{ elementRelation.title }}
    {% endfor %}
{% endif %}
```

Or, if you were on an Entry element template, you could list all the Formie Submission elements that are related to that element, as a reverse relation.

```twig
{% set entry = craft.entries.id(2242).one() %}

Submissions related to {{ entry.title }}

{% for elementRelation in craft.formie.getSubmissionRelations(entry) %}
    {{ elementRelation.title }}
{% endfor %}
```
