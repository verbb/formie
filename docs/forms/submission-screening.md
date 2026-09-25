# Submission Screening

Formie checks valid final submissions for content spam and CAPTCHA before saving and dispatching them. Invalid field values stop in Validate, so visitors can correct mistakes without spending an external CAPTCHA token.

## Screening Order

Screen first runs `screen.evaluateSpam`, including global email rules, link/text rules, spam keywords and IP rules. It then runs `screen.verifyCaptcha` for enabled providers, unless the submission is already known to be spam. A failed CAPTCHA marks the submission as spam and records the provider's reason.

SaveDraft and Revise skip content screening. PaymentReplay relies on the previously validated attempt. For the complete lifecycle and public extension anchors, see [Submission Workflow](/developers/submission-workflow).

## Request Safeguards

Integrity, ownership, replay and rate checks run at the explicit submission boundary before workflow tasks. These safeguards also protect drafts and page-state writes. Browser adapters additionally apply their honeypot, timing and expiration checks; interactive REST and GraphQL still require a signed request token issued by bootstrap or session refresh.

Successful operations retain a bounded durable receipt, so retries recover the saved outcome. Validation failures can reuse their token with corrected input. A reused token with a different operation or payload conflicts. Rate rejection returns 429 for structured HTTP transports; configured fake-success spam behaviour is a separate policy for bot/content rejection.

Configure global checks under **Formie → Settings → Spam Protection**. The [spam protection reference](/forms/spam-protection) explains the available settings.

## CAPTCHA and Content Rules

Configure CAPTCHA credentials under **Settings → Spam Protection → Captchas**, then enable the providers needed by each form. Provider-specific instructions are under [Captchas](/integrations/captchas/).

Content rules run before external verification. This keeps local keyword, email and IP decisions available without requiring a third-party service. The [screening guide](/guides/submissions-workflows/submission-screening-rules-in-practice) shows how to test the rules and review false positives.

## Extending Screening

Register a Submit task before or after `Task::SCREEN_EVALUATE_SPAM` or `Task::SCREEN_VERIFY_CAPTCHA`. Registration declares its unique ID, handler and operations through `TaskDefinition`. Use the [custom task walkthrough](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) for a complete module example; checks that add field errors belong after `validate.submission` so they stop before screening.
