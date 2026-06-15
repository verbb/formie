# Spam Protection

Spam protection is about reducing junk submissions without making the form harder for real people to use.

Formie gives you a few layers to work with, so you can start with lighter filtering and add stronger checks only where they are needed. Most of the control lives in **Formie → Settings → Spam Protection**.

That page brings together spam handling, keyword rules, built-in submission guards, and captcha provider credentials in one place. Legacy **Settings → Spam** and **Settings → Captchas** routes redirect here.

Captcha failures, keyword matches, submission guard failures, and related signals are evaluated together in the submission workflow’s **`screen`** stage. For how that stage is ordered, how it relates to validation, and how to extend it, see [Submission screening](/forms/submission-screening).

## Spam handling

At the plugin level, you can choose whether spam submissions should still be saved. Saving them is useful when you need to review false positives, understand what kind of spam is hitting the form, or debug a captcha or filtering rule.

If you do save spam, you can also set a limit for how many spam submissions Formie should keep before older ones are pruned. That helps keep the database under control without losing all visibility into what is being blocked.

You can also decide how Formie should respond when a submission is flagged as spam. In many cases, it is better to behave as though the submission was accepted, rather than giving a clear rejection message that helps bots learn what is being blocked. If you prefer, you can instead show an error message.

If you use email notifications, there is also a plugin setting for whether spam submissions should still trigger them.

## Content rules

### Spam keywords

Spam keywords are the simplest built-in screening tool. Formie checks the whole submission, and if it matches your keyword definition, the submission will be marked as spam.

You can match against:

- words or phrases
- combinations such as `AND`, `OR`, and `NOT`
- IP addresses or IP ranges

This makes spam keywords useful for obvious repeat attacks or recurring junk content.

#### Keyword definition

```text
# Flags content containing the word "spam". This will not match "spamming" or "Spam".
[match: spam]

# Flags content containing the exact phrase "cheap ham".
[match: cheap ham]

# Flags content only if both "spam" and "bulk" are present.
[match: spam AND bulk]

# Flags content if either "spam" or "phishing" is present.
[match: spam OR phishing]

# Flags content if it contains either "spam" or "junk" along with "email".
[match: (spam OR junk) AND email]

# Flags content if it does not contain "client".
[match: NOT client]
```

You can define each rule on a new line, and you can use parentheses to group logic when needed.

#### IP addresses

```text
# Flags content if the sender's IP matches. Supports singular, multiple, ranges and CIDR notation.
[ip: 192.168.0.1, 192.168.0.2, 192.168.0.3]
[ip: 10.0.0.1]
[ip: 192.168.0.1-192.168.0.255]
[ip: 192.168.0.0/24]
```

#### Referencing other content

Spam keywords are stored in the runtime spam settings store (with optional project-config defaults), which means they are not always convenient to edit directly on staging or production when those values are project-scoped. If you want content admins to manage them, or you need them to vary by environment, you can reference another field instead.

This is commonly done with a Global Set. For example, if you had a Global Set called `Forms` and a field called `Spam Keywords`, you could reference it in the Formie spam keywords setting with `{forms.spamKeywords}`.

Spam keyword and IP rules are evaluated by `SpamHelper` during the `screen.runSpamChecks` workflow task. That includes `[match:]` logical rules, `[ip:]` ranges and CIDR notation, and field/global references in keyword lines.

### Email rules

Global email rules apply to every **Email Address** field on every form during the **`screen`** stage. They mark matching submissions as spam rather than showing a field validation error.

Configure them under **Formie → Settings → Spam Protection → Content Rules → Email Rules**:

- **Blocked domains** — one domain per line (for example `mailinator.com`)
- **Block free email providers** — rejects addresses from Formie’s maintained free/disposable provider list

Per-field email settings such as **Blocked Domains**, **Block Free Email Providers**, and **Validate Domain (DNS)** still run separately during field validation. Use global rules when you want the same policy everywhere; use per-field settings when only some forms need stricter email checks.

## Submission guards

Submission guards are built-in passive checks that run **before** captcha integrations and keyword rules. They replace the old **Honeypot**, **Javascript**, and **Duplicate** captcha integrations that shipped in earlier major versions.

Configure them under **Formie → Settings → Spam Protection → Submission Guards**.

| Guard | What it does | Replaces |
| --- | --- | --- |
| **Honeypot** | Renders a hidden field that legitimate users should leave empty. Submissions that fill it in are marked as spam. | Legacy **Honeypot** captcha |
| **Minimum submit time** | Requires a minimum delay between the form loading and submission. | Legacy **Javascript** captcha (including its minimum submit time option) |
| **Form submit expiration** | Rejects submissions when too much time has passed since the form was first loaded. | — |
| **Replay protection** | Prevents duplicate submissions from reusing the same `requestToken`. | Legacy **Duplicate** captcha |

Honeypot, minimum submit time, and replay protection are enabled by default. Form submit expiration is off by default.

### How guards run in the workflow

Guards are not captcha integrations. They do not appear in the form builder’s captcha picker, and they do not use provider credentials.

Instead, Formie runs them through dedicated workflow tasks:

1. **`screen.runSubmissionGuards`** — runs during the **`screen`** stage, before captcha and keyword checks. If a guard fails, the submission is marked as spam and `spamReason` is set.
2. **`finalize.consumeReplayToken`** — runs during the **`finalize`** stage after a successful, complete submission. Replay protection only consumes the token once the submission has finished processing successfully, so failed or incomplete submissions can retry with the same token.

Guards only run for normal browser form posts that include `handle` and `submitAction` in the POST body. GraphQL submissions, headless API usage, and other non-browser flows skip guards automatically.

The honeypot field and `formStartedAt` timestamp are rendered automatically for browser forms. You do not need to add them manually in templates.

### Honeypot field name

The default honeypot input name is `formieHoneypot`. You can change it under **Honeypot Field Name** if you need to avoid a clash with a real field on your forms.

If you customize the name, make sure it does not match any field handle or name your forms already use.

### Minimum submit time

The browser package records when a form instance was first mounted and sends that value as `formStartedAt`. Formie compares it against the configured minimum (in seconds) on the server.

Very fast automated submissions are flagged as spam. Real users who submit immediately after the page loads may also be caught if the minimum is set too high, so tune this value for your forms.

### Form submit expiration

Form submit expiration uses the same `formStartedAt` timestamp as minimum submit time. If the elapsed time exceeds the configured maximum (in seconds), the submission is marked as spam.

Use this when you want to reject stale form sessions left open for hours or days. It is disabled by default.

### Replay protection

Replay protection uses the existing per-render `requestToken` that Formie already issues for browser forms. Formie stores a short-lived cache entry when a token is successfully consumed, and rejects reuse within 24 hours.

This is separate from save-and-continue draft tokens and static-cache refresh behaviour. It targets repeat POST abuse, not legitimate multi-page navigation.

For the full screening order and extension points, see [Submission screening](/forms/submission-screening).

## Captchas

Formie also supports captcha integrations when you need a stronger challenge layer.

Configure provider credentials under **Formie → Settings → Spam Protection → Captchas**, then enable the providers you need per form in the form builder.

Use captchas when keyword matching and submission guards are not enough, or when the form is a common attack target. Third-party spam scoring services such as Akismet, CleanTalk, and OOPSpam are configured here too, even though they classify content rather than show a visible challenge.

See [Captchas](/integrations/captchas/) for provider-specific setup guides.

## Runtime-managed settings

Spam handling, keyword rules, submission guards, and captcha provider credentials are stored in Formie’s runtime settings tables rather than `config/project` plugin settings. That lets privileged admins edit production values when `allowAdminChanges` is `false`.

Project-scoped rows can still deploy through project config. Site-scoped rows remain environment-local. Legacy `plugins.formie.settings` spam and captcha keys are stripped on save and migrated into the new stores automatically while compatibility mode is enabled.

If you previously kept spam keywords or captcha keys in `config/project/project.yaml`, move ongoing management to **Settings → Spam Protection** after upgrading.
