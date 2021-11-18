# Selecting Forms
Formie provides a [Form Element](docs:developers/form) field for you to use in other elements in the control panel. For example, you might like to add a Formie form field to an entry, allowing content authors to select a form to be shown on the page.

When using this field's value, you're dealing with a [Form Query](docs:getting-elements/form-queries).

```twig
{% set form = entry.myFormFieldHandle.one() %}

{{ craft.formie.renderForm(form) }}
```

## Selecting Submissions
Formie provides a [Submission Element](docs:developers/submission) field for you to use in other elements in the control panel. For example, you might like to add a Formie form submission field to an entry, allowing content authors to select a form submission to be shown on the page.

When using this field's value, you're dealing with a [Submission Query](docs:getting-elements/submission-queries).

```twig
{% set submission = entry.mySubmissionFieldHandle.one() %}

{{ submission.title }}
```
