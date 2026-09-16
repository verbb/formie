# Save and Continue Later

Long and multi-page forms lose completions when visitors get interrupted. Formie stores incomplete submissions in the database, optionally exposes a save button with resume links, and can restore in-progress state automatically when someone returns. This walkthrough configures the feature end-to-end for a job application or registration form.

## Prepare an Application Form

You need an installed Formie site, permission to edit forms, and access to `config/formie.php` if you want to change retention. Create a form named **Job Application** with handle `jobApplication`. Add Name and Email Address fields to the first page and a Multi-Line Text field named **Experience** to the second. Save it, then create `templates/apply.twig`:

```twig
{{ craft.formie.renderForm('jobApplication') }}
```

Open `/apply` and confirm the first page appears. The steps below configure this form. [Save & Continue Later](/forms/save-continue-later) explains the feature, and [Configuration](/get-started/configuration) lists the retention options.

## Two Related Behaviours

| Feature | What it does | User-visible? |
| --- | --- | --- |
| **Incomplete submissions** | Saves progress as users move through pages | Automatic on multi-page forms |
| **Save button + resume link** | Lets users deliberately save and return via URL | Optional per page |

Both store real submission records marked incomplete until the final step completes.

## Step 1 — Enable Multi-Page or Long Single-Page Form

Save and continue is useful for:

- Multi-page forms (progress saved on each page navigation)
- Long single-page forms with many fields

Ensure **Enable Multi-Page Forms** is on globally unless you intentionally run single-page only (**Settings** or `enableMultiPageForms` in config).

## Step 2 — Automatic Submission State

On the form **Settings** tab, enable **Restore In-Progress Submissions Automatically** when visitors should resume an in-progress submission if they return to the same form URL in the same browser session (without a resume link).

- **Enabled** — Formie restores the incomplete submission on return
- **Disabled** — Form starts fresh unless the user opens a resume link

Automatic state uses database-backed submission state.

## Step 3 — Add a Save Button

On the page where users should be able to save deliberately:

1. Open page settings in the form builder
2. Enable **Show Save Button**
3. Set **Save Button Label** (for example `Save and finish later`)
4. Choose link vs button display if your template supports it

When clicked, Formie creates a **resume link** with a token tied to that in-progress submission. The link works across browsers and devices — treat it like private access to a draft, especially for forms collecting personal data.

> Anyone with a resume link can continue that in-progress submission.

## Step 4 — Configure Retention

Align plugin settings with how long users might wait before returning:

| Setting | Default | Purpose |
| --- | --- | --- |
| `saveResumeTokenTtlDays` | 14 | How long resume links stay valid |
| `maxIncompleteSubmissionAge` | 30 | When incomplete submissions are removed by scheduled cleanup |
| `submissionStateRetentionDays` | 30 | Retention for front-end submission state |
| `maxSavedDraftsPerSession` | 10 | Saved drafts per browser session |

```php [config/formie.php]
return [
    '*' => [
        'saveResumeTokenTtlDays' => 30,
        'maxIncompleteSubmissionAge' => 60,
        'submissionStateRetentionDays' => 60,
    ],
];
```

Also review the form's **Data Retention** settings under form builder **Settings** for completed submission cleanup — separate from incomplete retention.

## Step 5 — Submission Statuses for Incomplete Records

Incomplete submissions appear in the control panel like other submissions, usually with an incomplete flag. Filter and report on them using [Submission Statuses](/submissions/submission-statuses).

Consider a status workflow for teams reviewing abandoned applications — for example, **Incomplete** → **Follow up** → **Complete**.

## Step 6 — Email Notifications

By default, notifications fire on **complete** submissions. Decide whether partial saves should trigger email:

- Usually **no** — wait for final submit
- If partial saves must send an email, implement a custom workflow task for that action. Ordinary notification dispatch skips incomplete submissions before evaluating notification conditions; adding a condition cannot enable partial-save delivery. See [Custom Workflow Tasks](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch).

Avoid spamming admins on every page step unless that is intentional.

## Step 7 — Headless and GraphQL

Save-and-resume works for client-rendered forms when using Formie's client session model. Bootstrap via `formieClientForm`; partial saves flow through `submitFormieClientForm` with incomplete state.

Resume links point at your Craft front-end URL with a token query parameter — ensure your SPA or Twig template loads the form and passes the token to Formie's client bootstrap.

## Step 8 — Security and Privacy

- Resume links are capability URLs — do not log them in analytics or expose them in referrer headers to third parties
- Set TTL short enough for your compliance requirements
- On GDPR-sensitive forms, document save-and-continue in your privacy notice
- Hiding the save button does not prevent partial storage: multi-page navigation still saves incomplete submissions. Automatic restoration controls whether progress is loaded again, not whether it is stored. If answers must only be stored on final submission, use a single-page form without a save button and test its actual submission workflow, including any custom tasks.

## Test Saving and Returning

Enter a test name and email, then move to the Experience page. Check **Formie → Submissions**, including incomplete records: the first page’s answers should be saved even before you use the save button. Enter some experience, save deliberately and copy the resume link.

Open the resume link in a separate browser session. Confirm that the saved answers load, finish the application, and check that the record is complete. Your normal notification should send after completion, not after the intermediate save. Treat the copied link as private and remove your test submission afterward.

If the resume link cannot load the draft, check whether the link expired or retention cleanup removed its submission. Disabling automatic restoration should stop a bare visit from restoring progress, while a valid resume link should still work.
