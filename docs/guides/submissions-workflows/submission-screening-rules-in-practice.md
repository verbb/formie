# Submission screening rules in practice

Submission screening is Formie's unified layer for deciding whether a submission is legitimate before it is saved and dispatched. This guide shows how guards, captchas, and content rules work together in practice — and how to tune them without blocking real users.

## Prerequisites

- [Submission Screening](/forms/submission-screening)
- [Spam Protection](/forms/spam-protection)

## Where screening runs

For a normal `submit` request, screening happens in the workflow **`screen`** stage — after field validation, before authorization and save:

1. **`screen.runSubmissionGuards`** — honeypot, minimum submit time, form submit expiration, replay protection, throttling
2. **`screen.runCaptchaChecks`** — enabled captcha integrations for the form
3. **`screen.runSpamChecks`** — email rules, text rules, spam keywords and IP lists

Draft saves and `editExisting` requests skip screening so editors and save-and-continue flows are not blocked.

After a successful complete submission, **`finalize.consumeReplayToken`** marks the `requestToken` as used so the same token cannot be replayed within 24 hours.

## Layer 1: Submission guards

Guards are global passive checks under **Formie → Settings → Spam Protection → Submission Guards**. They are not captcha integrations and do not appear in the form builder captcha picker.

| Guard | Default | Purpose |
| --- | --- | --- |
| Honeypot | On | Hidden field bots fill in |
| Minimum submit time | On | Rejects instant automated submits |
| Form submit expiration | Off | Rejects stale sessions left open too long |
| Replay protection | On | Prevents duplicate POST with same token |

**Universal guards** (global throttling, IP throttling, replay protection, required request token) apply to browser, client REST, and GraphQL submissions.

**Browser-only guards** (honeypot, minimum submit time, form submit expiration) run only for traditional form posts with `handle` and `submitAction` in the body.

Client REST and GraphQL must include a `requestToken` from `formieClientForm` or `refreshFormieClientSession`.

### Practical tuning

- **Minimum submit time** — start around 2–3 seconds. Too high catches fast legitimate users.
- **Honeypot field name** — change from `formieHoneypot` only if it clashes with a real field handle.
- **Form submit expiration** — enable for high-value forms left open on shared machines (hours/days threshold).

## Layer 2: Captcha integrations

Enable captchas per form when guards and keywords are not enough:

1. Configure provider credentials under **Settings → Spam Protection → Captchas**.
2. Enable providers on the form in the form builder.

Third-party scoring services (Akismet, CleanTalk, OOPSpam) classify content without showing a puzzle — they still run in `screen.runCaptchaChecks`.

See [Captchas](/integrations/captchas/) for provider setup.

## Layer 3: Content rules

### Email rules (global)

Under **Content Rules → Email Rules**:

- **Allowed domains** — allowlist; skips blocked-domain checks for matching addresses
- **Blocked domains** — one domain per line
- **Block free email providers** — rejects disposable/free addresses

These run during `screen.runSpamChecks` and mark submissions as spam — not field validation errors. Per-field email settings still run separately during validation.

### Text rules

- **Suspicious text detection** — keyboard spam and random strings; add **Allowed terms** for product codes that look suspicious
- **Maximum links** — spam when total links across all fields exceed the limit

### Spam keywords

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

## Submission throttling vs submission limits

**Throttling** (under Spam Protection) is abuse protection — caps rapid repeat submits and marks excess as spam.

**Submission limits** (per form in the builder) are business rules — registration caps, contest entry limits, closing the form when full.

Use throttling for floods; use submission limits for quotas.

## Spam handling behaviour

Under **Spam Protection → Spam handling**, choose:

- Whether spam submissions are **saved** (useful for reviewing false positives)
- How Formie **responds** to spammers (fake success vs error — fake success avoids training bots)
- Whether spam triggers **email notifications**
- Prune limit for stored spam

## Example: balanced public contact form

| Layer | Setting |
| --- | --- |
| Guards | Honeypot on, minimum submit time 3s, replay on |
| Captcha | reCAPTCHA v3 or Turnstile on high-traffic forms only |
| Email rules | Block free providers off; blocked domains for known throwaways |
| Keywords | `[match: viagra OR cialis]` plus project-specific terms |
| Throttling | IP wait time 30s on the contact form via submission limits; global throttling off unless under attack |

## Extending screening

Insert custom tasks relative to built-in names:

```php
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, function(RegisterStageTasksEvent $event) {
    if ($event->stage !== 'screen') {
        return;
    }

    $event->insertTaskAfter(Task::SCREEN_RUN_SUBMISSION_GUARDS->value, new MyFraudScoreTask());
});
```

## Related

- [Submission Screening](/forms/submission-screening)
- [Spam Protection](/forms/spam-protection)
- [Submission workflow and stages explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)
- [Spam keywords in detail](/guides/configuration/spam-keywords-in-detail)
