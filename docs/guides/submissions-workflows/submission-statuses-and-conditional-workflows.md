# Submission statuses and conditional workflows

Submission **statuses** are team workflow labels on saved submissions. **Conditions** change what the form shows and which notifications send based on answers. Together they let you route work after submit and tailor the form experience before submit.

## Prerequisites

- [Submission Statuses](/submissions/statuses)
- [Conditions](/forms/conditions)

## Statuses — organise submissions after save

Statuses answer "where is this submission in our process?" — separate from system states like complete, incomplete, or spam.

Typical handles:

- `new`
- `in-progress`
- `approved`
- `closed`

### Set up statuses

1. Go to **Formie → Settings → Submission Statuses**.
2. Create statuses with name, handle, colour, and optional description.
3. Mark one status as the **default** for new submissions.
4. Per form, choose which status new submissions receive under form settings.

Statuses sync through project config. You cannot delete the default status while it is default, or remove a status still assigned to submissions.

Add statuses when a team actually needs review or follow-up. If every submission is handled the same way, the default status may be enough.

### Statuses vs system states

| Concept | Examples | Managed by |
| --- | --- | --- |
| **Status** | `new`, `approved` | Your team in Formie settings |
| **Complete / incomplete** | Multi-page progress | Formie submission state |
| **Spam** | Flagged by screening | Formie spam handling |

A submission can be complete and `new`, or complete and `approved`, or marked spam regardless of status.

## Conditions — change the form path

Conditions use three parts: field to check, comparison, and value. Choose whether **all** rules or **any one** rule must match.

Formie supports conditions on:

- **Fields** — show or hide
- **Pages** — skip sections in multi-page forms
- **Page buttons** — control next/previous availability
- **Email notifications** — send or skip
- **Notification recipients** — route to different people

### Example: reveal follow-up fields

Show a "Please specify" text field only when Dropdown `reason` equals `other`:

1. Edit the text field → **Conditions**.
2. Enable conditions.
3. Add rule: `reason` **is** `other`.

Hidden required fields stop being required while hidden — the form is not blocked by fields the user cannot see.

### Example: branch pages

On a multi-page form, add page conditions so enterprise customers skip the consumer pricing page:

1. Edit the page → **Conditions**.
2. Set rules on `companySize` or similar qualifying field.

### Example: conditional notifications

Send a notification to sales only when `budget` is above a threshold:

1. Edit the email notification → **Conditions**.
2. Add rule: `budget` **is greater than** `10000`.

Use recipient conditions when the same notification template should go to different addresses depending on submission content.

## Combining statuses and conditions

Conditions affect **behaviour during and immediately after submit**. Statuses affect **management afterward** in the control panel.

A practical workflow:

1. **Conditions** route urgent enquiries — high budget reveals a priority page and sends a dedicated notification to sales.
2. New submissions land in status `new` (form default).
3. Staff move submissions to `in-progress` while working them, then `closed` when done.

Conditions do not set statuses automatically. If you need status changes based on submission content, use a module that listens for `Submission::EVENT_AFTER_SAVE` or a custom workflow task in the `save` stage.

```php
use craft\events\ModelEvent;
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;

    if ($submission->getFieldValue('priority') === 'urgent') {
        $submission->statusId = Formie::$plugin->getStatuses()->getStatusByHandle('in-progress')->id;
        Craft::$app->elements->saveElement($submission, false);
    }
});
```

Avoid infinite save loops — use `$event->isValid` checks and skip when the status is already correct.

## Control panel editing

When editing submissions in the CP, Formie can apply the same field and page conditions as the front end. Configure the default under **Formie → Settings → Submissions**, or override per form.

## Related

- [Submission Statuses](/submissions/statuses)
- [Conditions](/forms/conditions)
- [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)
- [Email Notifications](/forms/email-notifications)
