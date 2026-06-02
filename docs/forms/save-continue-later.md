# Save & Continue Later

Formie stores all submissions in the database, including incomplete ones.

That means there are two related things happening when someone does not finish a form in one go. Formie can keep track of in-progress submissions automatically, and you can optionally show a save button that creates a resume link for later.

This is especially useful on longer forms, multi-page forms, and any workflow where someone may need to stop and come back later.

## Incomplete submissions

Incomplete submissions are real saved records, not a separate content type. On multi-page forms, progress is saved as someone moves from one page to the next. Until the final step is completed, the submission stays incomplete.

This happens whether or not you show a save button. It means you can review partial or abandoned submissions in the control panel, and it also gives Formie something to resume later.

If `Automatic Submission State` is enabled on the form, someone returning to that form can continue their in-progress submission automatically. If it is disabled, the form starts fresh unless they open a resume link.

Incomplete submissions are still different from completed submissions, so they are often handled differently for reporting, exports, follow-up, and cleanup. You can manage those records in [Statuses](/submissions/statuses).

## Save button and resume links

If you want someone to deliberately save their place, enable `Show Save Button` on the page where that option should appear. When someone uses it, Formie creates a resume link with a token tied to that in-progress submission.

Opening that link loads the saved submission again so the person can continue where they left off. The link is not limited to the same browser or device, so it can be reopened later elsewhere, or passed to someone else if that is part of your process.

You can also control the save button label and whether it appears as a link or a button in the page settings.

> [!WARNING]
> Anyone with a resume link can continue that in-progress submission. Treat the link like private access to the draft, especially on forms that collect personal or sensitive information.

## Retention and expiry

Resume links expire based on the `saveResumeTokenTtlDays` plugin setting in [Configuration](/get-started/configuration). Incomplete submissions are also affected by the `maxIncompleteSubmissionAge` plugin setting, and the form’s own `Data Retention` settings in [Form Builder](/forms/form-builder) can affect how long submission data is kept.

If someone may come back after days or weeks, make sure those settings allow enough time for both the saved submission and the resume link to still be available.
