# Submission Screening

Submission screening is Formie’s unified layer for deciding whether a submission should be treated as legitimate before it is saved and dispatched. It runs in the submission workflow’s **`screen`** stage, immediately after field validation and before authorization, persistence, and notifications.

Screening combines **submission guards**, **captcha integrations**, and **server-side spam rules** so you can tune friction for real users while still blocking automated abuse.

## How Screening Fits the Workflow

For a normal submit request, Formie runs the `screen` stage in a fixed order:

1. **`screen.runSubmissionGuards`** — built-in passive checks: honeypot, minimum submit time, form submit expiration, and replay protection. These are configured globally under **Settings → Spam Protection → Submission Guards**. If a guard fails, the submission is marked as spam with a clear `spamReason`.
2. **`screen.runCaptchaChecks`** — every captcha integration enabled for the form runs in turn. If a captcha fails validation, the submission is marked as spam with a clear reason and the captcha class is recorded for review.
3. **`screen.runSpamChecks`** — global email rules (when enabled), then plugin-level spam keyword and IP rules against submission content.

That ordering means lightweight built-in checks run first, then provider-backed captchas, then broader text and network rules. Draft saves and non-submit actions skip this stage so editors and save-and-continue flows are not blocked by guards, captchas, or keyword lists.

Replay protection has a second workflow touchpoint. After a successful, complete submission, **`finalize.consumeReplayToken`** marks the `requestToken` as used so the same token cannot be replayed. Failed or incomplete submissions do not consume the token.

For a deeper look at stages, tasks, and extension points, see [Submission Workflow](/developers/submission-workflow).

## Submission Guards

Submission guards are global, built-in checks — not captcha integrations. They replaced the legacy **Honeypot**, **Javascript**, and **Duplicate** captcha types from earlier major versions.

| Task | Stage | Purpose |
| --- | --- | --- |
| `screen.runSubmissionGuards` | `screen` | Validate honeypot, minimum submit time, and replay token state |
| `finalize.consumeReplayToken` | `finalize` | Consume the request token after successful processing |

Guards run for every final submit. **Universal guards** (global throttling, IP throttling, replay protection, and a required request token) apply to browser, client REST, and GraphQL submissions. **Browser-only guards** (honeypot, minimum submit time, and form submit expiration) run only for traditional form posts that include `handle` and `submitAction` in the POST body.

Client REST and GraphQL submissions must include a `requestToken` issued by a bootstrap call such as `formieClientForm` or `refreshFormieClientSession`. Drive-by API posts without a token are rejected.

Day-to-day configuration lives in [Spam Protection](/forms/spam-protection#submission-guards). If you are upgrading from Formie 3 and used the old built-in captchas, see [Removed legacy captchas](/get-started/upgrading-from-v3#removed-legacy-captchas) in the upgrade guide.

## Captcha Integrations

Captcha integrations cover everything from visible challenges to invisible tokens and third-party spam APIs that classify content without showing a puzzle.

Configure provider credentials under **Formie → Settings → Spam Protection → Captchas**, then enable the ones you need per form. Provider-specific setup lives under [Captchas](/integrations/captchas/) in the integrations section of these docs.

## Spam Keywords and Related Settings

Spam keywords, IP lists, spam handling behaviour, and submission guard toggles live under **Formie → Settings → Spam Protection**. Keyword and IP rules apply across forms after guards and captcha checks, which keeps simple keyword blocking available even when you are not using a third-party provider.

Day-to-day behaviour of those settings (saving spam, user-visible responses, notifications) is described in [Spam Protection](/forms/spam-protection).

## Why a Dedicated Screening Stage

Grouping guards, captchas, and spam rules in one workflow stage keeps behaviour predictable for custom code: validation errors surface in the **`validate`** stage, while spam and abuse signals are handled in **`screen`**. If you need to insert extra checks, register tasks before or after the built-in screening tasks without replacing the whole submission pipeline.

### Extending screening

Use `SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS` to insert work relative to the built-in task names:

- `screen.runSubmissionGuards`
- `screen.runCaptchaChecks`
- `screen.runSpamChecks`

For example, insert a custom fraud-score task after guards but before captcha checks:

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

The `SubmissionGuards` service (`Formie::$plugin->getSubmissionGuards()`) owns guard validation logic if you need to call it from custom tasks or tests.
