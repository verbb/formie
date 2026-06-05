# Conditions

Conditions let a form change what happens based on answers already given.

They are useful when the form should react to the person filling it out, instead of showing the same path to everyone. A simple form can show the same fields to everyone, but a longer form often works better when it can reveal, skip, or hold back parts of the flow depending on what someone has already entered.

In Formie, conditions can be added in a few different places:

- fields, to show or hide them
- pages, to skip entire sections of a multi-page form
- page buttons, to control whether someone can move on
- email notifications, to decide whether they should send
- notification recipients, to choose who should receive an email

That makes conditions one of the simplest ways to keep a form focused instead of overwhelming.

Every condition is built from three parts:

1. the field to check
2. the comparison to use
3. the value to compare against

You can then choose whether all rules must match or whether any one of them is enough.

## Common uses

On fields, conditions are usually about relevance. A common example is only showing an `Other` text field when someone selected `Other` earlier in the form.

On pages, conditions are usually about path. This lets one form branch into different routes, skipping pages that do not apply.

Page buttons can also respond to conditions. That is useful when someone should not move to the next page until the form is in the right state, without needing to add another page just to handle that decision.

Conditions are also available on notifications. You can decide whether a notification should send at all, or use recipient conditions when the email should go to different people depending on the submission.

## Hidden required fields

If a required field is hidden by conditions, Formie stops treating it as required while it is hidden.

That avoids the common problem of a form being blocked by a field the person cannot even see.

## Control panel submissions

When editing a submission in the control panel, Formie can apply the same field and page conditions used on the front end. Configure that under **Settings → Submissions** or per form. See [Control panel field conditions](/submissions/cp-field-conditions).
