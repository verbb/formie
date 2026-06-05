# Control panel field conditions

When you view or edit a submission in the control panel, Formie can honour the same [field and page conditions](/forms/conditions) used on the front end.

That keeps the submission edit screen aligned with what the submitter would have seen, instead of always listing every field regardless of its conditions.

## Where to configure it

### Plugin default

Set the default under **Formie → Settings → Submissions → Control Panel Field Conditions**.

Forms that do not override this setting inherit the plugin default.

### Per-form override

Override the behaviour under **Form → Settings → Submissions → Control Panel Field Conditions**.

Choose **Use Formie default** to inherit the plugin setting.

## Behaviour modes

### Follow field conditions

Conditionally hidden fields are not shown in the control panel. Page conditions are respected as well.

Conditions are re-evaluated live when you change field values in the submission edit screen, so visibility stays in sync with the data you are editing.

When you save from the control panel, values for fields that are hidden by conditions are cleared, matching front-end submit behaviour.

### Follow field conditions (show hidden fields collapsed)

Same as **Follow field conditions**, but fields hidden by conditions appear as a collapsed block with a short label. Click the field label to expand or collapse the input.

This is useful when admins need to peek at or edit conditionally hidden values without switching the whole form to **Show all fields**.

Saving still clears values for fields that are hidden by conditions at save time.

### Show all fields

Legacy behaviour. Every field is always visible in the control panel, regardless of conditions. No live condition evaluation runs on the submission edit screen.

Use this when your team prefers to see the full submission layout at all times.
