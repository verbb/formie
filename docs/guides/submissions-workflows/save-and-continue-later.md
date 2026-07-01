# Save and continue later

Long and multi-page forms lose completions when visitors get interrupted. Formie stores incomplete submissions in the database, optionally exposes a save button with resume links, and can restore in-progress state automatically when someone returns. This walkthrough configures the feature end-to-end for a job application or registration form.

## Prerequisites

- [Save & Continue Later](/forms/save-continue-later)
- [Configuration](/get-started/configuration) — retention and token settings

## Two related behaviours

| Feature | What it does | User-visible? |
| --- | --- | --- |
| **Incomplete submissions** | Saves progress as users move through pages | Automatic on multi-page forms |
| **Save button + resume link** | Lets users deliberately save and return via URL | Optional per page |

Both store real submission records marked incomplete until the final step completes.

## Step 1 — Enable multi-page or long single-page form

Save-and-continue shines on:

- Multi-page forms (progress saved on each page navigation)
- Long single-page forms with many fields

Ensure **Enable Multi-Page Forms** is on globally unless you intentionally run single-page only (**Settings** or `enableMultiPageForms` in config).

## Step 2 — Automatic submission state

On the form **Settings** tab, enable **Automatic Submission State** when visitors should resume an in-progress submission if they return to the same form URL in the same browser session (without a resume link).

- **Enabled** — Formie restores the incomplete submission on return
- **Disabled** — Form starts fresh unless the user opens a resume link

Automatic state uses database-backed submission state.

## Step 3 — Add a save button

On the page where users should be able to save deliberately:

1. Open page settings in the form builder
2. Enable **Show Save Button**
3. Set **Save Button Label** (for example `Save and finish later`)
4. Choose link vs button display if your template supports it

When clicked, Formie creates a **resume link** with a token tied to that in-progress submission. The link works across browsers and devices — treat it like private access to a draft, especially for forms collecting personal data.

> Anyone with a resume link can continue that in-progress submission.

## Step 4 — Configure retention

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

## Step 5 — Submission statuses for incomplete records

Incomplete submissions appear in the control panel like other submissions, usually with an incomplete flag. Filter and report on them using [Submission Statuses](/submissions/statuses).

Consider a status workflow for teams reviewing abandoned applications — for example, **Incomplete** → **Follow up** → **Complete**.

## Step 6 — Email notifications

By default, notifications fire on **complete** submissions. Decide whether partial saves should trigger email:

- Usually **no** — wait for final submit
- If yes — add a notification with a condition on submission completeness (or a custom workflow task)

Avoid spamming admins on every page step unless that is intentional.

## Step 7 — Headless and GraphQL

Save-and-resume works for client-rendered forms when using Formie's client session model. Bootstrap via `formieClientForm`; partial saves flow through `submitFormieClientForm` with incomplete state.

Resume links point at your Craft front-end URL with a token query parameter — ensure your SPA or Twig template loads the form and passes the token to Formie's client bootstrap.

## Step 8 — Security and privacy

- Resume links are capability URLs — do not log them in analytics or expose them in referrer headers to third parties
- Set TTL short enough for your compliance requirements
- On GDPR-sensitive forms, document save-and-continue in your privacy notice
- Disable save buttons on forms where partial data must not persist (use automatic page progress only, or single-page with no save)

## Related

- [Save & Continue Later](/forms/save-continue-later)
- [Configuration](/get-started/configuration)
- [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)
