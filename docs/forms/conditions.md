# Conditions

::: tip
For statuses plus conditions in one workflow story, see [Submission statuses and conditional workflows](/guides/submissions-workflows/submission-statuses-and-conditional-workflows).
:::

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

## Common Uses

On fields, conditions are usually about relevance. A common example is only showing an `Other` text field when someone selected `Other` earlier in the form.

On pages, conditions are usually about path. This lets one form branch into different routes, skipping pages that do not apply.

Page buttons can also respond to conditions. That is useful when someone should not move to the next page until the form is in the right state, without needing to add another page just to handle that decision.

Conditions are also available on notifications. You can decide whether a notification should send at all, or use recipient conditions when the email should go to different people depending on the submission.

## Hidden Required Fields

If a required field is hidden by conditions, Formie stops treating it as required while it is hidden.

That avoids the common problem of a form being blocked by a field the person cannot even see.

## Control Panel Submissions

When editing a submission in the control panel, Formie can apply the same field and page conditions used on the front end. Configure the default under **Formie → Settings → Submissions**, or override per form. See [Submissions](/submissions/submissions#edit-submissions-in-the-control-panel).

## Choosing Sources and Comparisons

New field rules use earlier fields or preceding siblings. Page visibility uses previous pages; page-button rules can use the whole current page. The comparison menu follows the selected field's value type. Text comparisons are case-sensitive and text ordering is alphabetical; Number fields compare numeric values. Options match their complete stored values.

Formie clears hidden and disabled answers before validating or saving, including values inside Groups and Repeaters. Changing an answer can therefore remove content that is no longer relevant. An invalid show rule keeps its field hidden, while an invalid hide rule keeps it visible. Invalid navigation, notification and integration rules block that operation and record a diagnostic. Correct missing references or dependency cycles in the builder before publishing the form.

Visitors cannot bypass page requirements by changing a posted page target. A blocked Next action stays on the current page and shows an error; Back and Save Draft do not validate the page. Validation messages point to the exact control, including the affected repeater row.
