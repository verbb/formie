# Submission Screening Rules in Practice

Use Formie's spam settings to block automated submissions while allowing legitimate visitors to finish their forms. This guide combines built-in safeguards, CAPTCHA providers and content rules for a public contact form.

## Prerequisites

- [Submission Screening](/forms/submission-screening)
- [Spam Protection](/forms/spam-protection)

## Where Screening Runs

Formie checks completed forms for spam after field validation. It checks content rules first, then CAPTCHA unless a rule has already identified the submission as spam. Built-in request safeguards, such as throttling and honeypots, run before these checks.

Draft saves and edits to existing submissions skip content screening. Draft saves and page changes still have request safeguards. Visitors with invalid field values can correct them before a CAPTCHA check is used.

## Layer 1: Submission Guards

Guards are global passive checks under **Formie → Settings → Spam Protection → Submission Guards**. They are not captcha integrations and do not appear in the form builder captcha picker.

| Guard | Default | Purpose |
| --- | --- | --- |
| Honeypot | On | Hidden field bots fill in |
| Minimum submit time | On | Rejects instant automated submits |
| Form submit expiration | Off | Rejects stale sessions left open too long |
| Replay protection | On | Prevents duplicate POST with same token |

Formie’s standard form rendering includes the fields and tokens these safeguards need. For a custom REST or GraphQL client, use the session returned when you [load the form](/graphql/rendering-forms).

### Practical Tuning

- **Minimum submit time** — start around 2–3 seconds. Too high catches fast legitimate users.
- **Honeypot field name** — change from `formieHoneypot` only if it clashes with a real field handle.
- **Form submit expiration** — enable for high-value forms left open on shared machines (hours/days threshold).

## Layer 2: Captcha Integrations

Enable captchas per form when guards and keywords are not enough:

1. Configure provider credentials under **Settings → Spam Protection → Captchas**.
2. Enable providers on the form in the form builder.

Services such as Akismet, CleanTalk and OOPSpam check content without showing visitors a puzzle. Configure them in the same Captchas area.

See [Captchas](/integrations/captchas/) for provider setup.

## Layer 3: Content Rules

### Email Rules (Global)

Under **Content Rules → Email Rules**:

- **Allowed domains** — allowlist; skips blocked-domain checks for matching addresses
- **Blocked domains** — one domain per line
- **Block free email providers** — rejects disposable/free addresses

These rules mark a submission as spam. Email validation configured on an individual field instead shows a field error that the visitor can correct.

### Text Rules

- **Suspicious text detection** — keyboard spam and random strings; add **Allowed terms** for product codes that look suspicious
- **Maximum links** — spam when total links across all fields exceed the limit

### Spam Keywords

Keyword and IP rules apply across all forms:

```text
[match: viagra OR casino]
[match: (spam OR junk) AND email]
[ip: 192.168.0.0/24]
```

Reference another field or global set for environment-specific lists:

```text
[match: {forms.spamKeywords}]
```

See [Spam keywords in detail](/guides/configuration/spam-keywords-in-detail) for full syntax.

## Submission Throttling vs Submission Limits

**Throttling** (under Spam Protection) blocks requests that exceed the configured rate, helping protect the site from floods of submissions.

**Submission limits** (per form in the builder) are business rules — registration caps, contest entry limits, closing the form when full.

Use throttling for floods; use submission limits for quotas.

## Spam Handling Behaviour

Under **Spam Protection → Spam handling**, choose:

- Whether spam submissions are **saved** (useful for reviewing false positives)
- How Formie **responds** to spammers (fake success vs error — fake success avoids training bots)
- Whether spam triggers **email notifications**
- Prune limit for stored spam

## Example: Balanced Public Contact Form

| Layer | Setting |
| --- | --- |
| Guards | Honeypot on, minimum submit time 3s, replay on |
| Captcha | reCAPTCHA v3 or Turnstile on high-traffic forms only |
| Email rules | Block free providers off; blocked domains for known throwaways |
| Keywords | `[match: viagra OR cialis]` plus project-specific terms |
| Throttling | IP wait time 30s on the contact form via submission limits; global throttling off unless under attack |

## Test Your Form

Submit a realistic enquiry and check that it reaches the expected success page and notification recipient. Then try a keyword that your rules block and confirm the configured spam response. Review saved spam for false positives before tightening the rules.

If a project needs a custom spam check, a developer can add a [workflow task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch). The [workflow reference](/developers/submission-workflow#stages-and-public-anchors) lists where it can run.
