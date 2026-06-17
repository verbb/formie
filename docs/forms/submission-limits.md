# Submission Limits

Submission limits control how many entries a form accepts. They are a **business rule** — for contests, registrations, surveys with quotas, and similar cases where you need a hard cap.

Configure them per form under **Formie → Forms → {form} → Settings → Behaviour → Submission Limits**.

Submission limits are separate from [Spam Protection](/forms/spam-protection) throttling, which marks rapid or flood submissions as spam. They are also separate from **Availability** settings on the same Behaviour tab, which control whether the form is shown at all.

## How limits work

Turn on **Limit Submissions**, then choose **Apply Limit To**:

| Scope | What it counts | When the limit is reached |
| --- | --- | --- |
| **All submissions for this form** | Every submission stored for the form | The form closes and shows your limit message |
| **Per IP address** | Submissions from the same IP for this form | The form stays open; the visitor sees a validation error |
| **Per logged-in user** | Submissions linked to the same Craft user for this form | The form stays open; the visitor sees a validation error |

Set **Allow** to the number of submissions permitted for the chosen scope, and choose a period (**all time**, **per day**, **per week**, **per month**, or **per year**).

Use **Message** to customise what visitors see when a limit is reached.

### Per logged-in user limits

Per-user limits require Formie to know who submitted. Enable **Collect User** under **Settings → Privacy** on the form (or preset it in **Formie → Settings → Defaults** or a form group’s **Form Defaults** tab).

Anonymous visitors are not counted toward a per-user limit.

### Per IP address limits

Per-IP limits work best when **Collect IP** is enabled under **Settings → Privacy**, so Formie can reliably attribute submissions. If IP collection is off, behaviour may be less predictable depending on your setup.

## Availability vs submission limits vs throttling

Formie splits “who can submit and how often” across three layers:

| Layer | Where | Purpose | Typical outcome |
| --- | --- | --- | --- |
| **Availability** | Form → Settings → Behaviour | Schedule, manual disable, require login | Form hidden or unavailable message |
| **Submission limits** | Form → Settings → Behaviour | Entry caps by form, IP, or user | Form closes (form scope) or validation error (IP/user scope) |
| **Submission throttling** | **Settings → Spam Protection** | Abuse and flood protection | Submission marked as spam |

Use **Availability** when the form should not be shown yet, or only to certain audiences.

Use **Submission limits** when you need a defined quota — one entry per person, 100 total responses, and so on.

Use **Submission throttling** when you need sitewide or rapid-repeat protection during an attack. Throttling does not replace per-form caps; it complements them.

### IP wait time vs IP count limits

These sound similar but behave differently:

- **IP submission throttling** (Spam Protection) — minimum **wait time** between two submissions from the same IP on the same form. Failed checks are treated as spam.
- **Per IP address** submission limits (form settings) — maximum **count** of submissions from an IP over a period. Failed checks show your limit message as a normal validation error.

You can use both on the same form when it makes sense: throttling slows bots; count limits enforce your entry policy.

## Form groups

[Form groups](/forms/form-groups) do not enforce submission limits across every form in a group. Limits remain **per form**.

Groups can still help teams that rely on limits:

- **Allowed submission statuses** (group **General** tab) restricts which statuses forms in the group may use.
- **Form Defaults** can preset **Collect User** or **Collect IP** so new forms in the group are ready for per-user or per-IP limits.

## Editing existing submissions

Changing or deleting an existing submission does not consume an additional limit slot. Limits apply when **creating** new submissions.

## Related reading

- [Spam Protection — Submission throttling](/forms/spam-protection#submission-throttling)
- [Form groups](/forms/form-groups)
- [Submission Screening](/forms/submission-screening)
