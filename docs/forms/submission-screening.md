# Submission screening

Submission screening is Formie’s unified layer for deciding whether a submission should be treated as legitimate before it is saved and dispatched. It runs in the submission workflow’s **`screen`** stage, immediately after field validation and before authorization, persistence, and notifications.

Screening combines **captcha integrations** (provider-backed challenges and token checks) with **server-side spam rules** so you can tune friction for real users while still blocking automated abuse.

## How screening fits the workflow

For a normal submit request, Formie runs the `screen` stage in a fixed order:

1. **`screen.runCaptchaChecks`** — every captcha integration enabled for the form runs in turn. If a captcha fails validation, the submission is marked as spam with a clear reason and the captcha class is recorded for review.
2. **`screen.runSpamChecks`** — plugin-level rules such as spam keywords and IP matching are applied against the submission content.

That ordering means provider-backed checks run first, then broader text and network rules. Draft saves and non-submit actions skip this stage so editors and save-and-continue flows are not blocked by captchas or keyword lists.

For a deeper look at stages, tasks, and extension points, see [Submission Workflow](/developers/submission-workflow).

## Captcha integrations

Captcha integrations cover everything from visible challenges to invisible tokens and third-party spam APIs that classify content without showing a puzzle.

Configure them under **Formie → Settings → Integrations**, then enable the ones you need per form. Provider-specific setup lives under [Captchas](/integrations/captchas/akismet) in the integrations section of these docs.

## Spam keywords and related settings

Spam keywords, IP lists, and related options live under **Formie → Settings → Spam**. They apply across forms after captcha checks, which keeps simple keyword blocking available even when you are not using a third-party provider.

Day-to-day behaviour of those settings (saving spam, user-visible responses, notifications) is described in [Spam Protection](/forms/spam-protection).

## Why a dedicated screening stage

Grouping captchas and spam rules in one workflow stage keeps behaviour predictable for custom code: validation errors surface in the **`validate`** stage, while spam and abuse signals are handled in **`screen`**. If you need to insert extra checks, you can register tasks before or after the built-in screening tasks without replacing the whole submission pipeline.
