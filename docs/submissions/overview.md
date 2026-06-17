# Submissions

Submissions are the saved records created when someone interacts with a Formie form.

They can represent a completed submission, a partially saved draft, or a submission that has been marked as spam. Once a submission exists, you can review it in the control panel, organize it with statuses, export its data, relate it to other elements, or render it back into the front end for editing.

## Submission States

Formie tracks a few built-in submission states:

- **complete** submissions have finished the normal submission flow
- **incomplete** submissions are partially saved, such as save-and-continue drafts or multi-page progress
- **spam** submissions have been screened and marked as spam

These are system states. They describe what happened during submission processing.

Custom [Statuses](/submissions/statuses) are different. A status is your team's workflow label on top of the saved submission, such as `new`, `approved`, or `closed`.

## Common Ways to Work With Submissions

### Organize Them With Statuses

Use [Statuses](/submissions/statuses) when your team needs labels for review, follow-up, or internal processing after a submission has been saved.

### Export Submission Data

Use [Exporting](/submissions/exporting) when you need spreadsheets, reporting output, or a portable snapshot of saved submission data.

### Relate Submissions to Other Elements

Use [Relations](/submissions/relations) when a submission belongs to another Craft element such as an entry or product, or when you want to look up related submissions later.

### Select Submissions From Other Elements

Use [Element Field](/submissions/element-field) when you want another Craft element to choose and store a reference to a Formie submission.

### Edit Submissions in the Control Panel

When you view or edit a submission in the control panel, Formie can honour the same [field and page conditions](/forms/conditions) used on the front end.

Set the default under **Formie → Settings → Submissions → Control Panel Field Conditions**. Override per form under **Form → Settings → Submissions** — choose **Use Formie default** to inherit the plugin setting.

| Mode | Behaviour |
| --- | --- |
| **Follow field conditions** | Conditionally hidden fields are omitted. Conditions re-evaluate as you edit; values for hidden fields are cleared on save. |
| **Follow field conditions (show hidden fields collapsed)** | Same as above, but hidden fields appear collapsed so admins can expand them when needed. |
| **Show all fields** | Every field is always visible; no live condition evaluation. |

Use **Show all fields** when your team prefers the full layout at all times.

### Let Users Edit Saved Submissions

If you need to render an existing submission back into the front end so a user can update it, see [Editing Submissions](/templates/editing-submissions).

## Developer Access

If you are working with submissions in templates or custom code:

- use [Submission Queries](/getting-elements/submission-queries) to fetch submissions in Twig or PHP
- use [Submission Workflow](/developers/submission-workflow) when you need to hook into submission processing stages or tasks
- use [Submission Content](/developers/submission-content) when you need the right value format for output, exports, summaries, emails, or integrations
- use [Submission](/reference/submission) for the public properties and methods available on the element itself

