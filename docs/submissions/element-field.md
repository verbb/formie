# Element Field

Formie includes an element field you can add to other Craft elements so editors can choose a submission.

That lets an entry, category, or other element decide which submission should appear alongside its content.

The field returns a Submission query, so you will usually call `.one()` before reading values.

```twig
{% set submission = entry.mySubmissionFieldHandle.one() %}

{% if submission %}
    {{ submission.title }}
{% endif %}
```
