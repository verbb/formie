# Submission Screening

Formie checks completed forms for spam before sending notifications or running integrations. It validates the fields first, so visitors can correct mistakes before a CAPTCHA check is used.

## Screening Order

Formie checks email rules, suspicious text, link limits, spam keywords and IP rules first. It then checks the form's enabled CAPTCHA providers, unless the submission has already been identified as spam. A failed CAPTCHA marks the submission as spam and records the reason for review.

Saving a draft or editing an existing submission skips these content checks. A payment returning from its provider continues the already-checked submission.

## Request Safeguards

Formie also checks for automated or repeated requests before processing the form. These safeguards protect draft saves and page changes as well as final submissions. Visitors can correct validation errors and try again; a retry after a lost connection does not create a duplicate successful submission.

Configure these checks under **Formie → Settings → Spam Protection**. [Spam Protection](/forms/spam-protection) explains each setting and what visitors see when a submission is blocked.

## CAPTCHA and Content Rules

Configure CAPTCHA credentials under **Settings → Spam Protection → Captchas**, then enable the providers needed by each form. Provider-specific instructions are under [Captchas](/integrations/captchas/).

Start with the rules your site needs, then submit a few realistic examples to check that legitimate answers get through. The [screening guide](/guides/submissions-workflows/submission-screening-rules-in-practice) shows how to combine rules and investigate false positives.

## Extending Screening

Developers can add a [custom workflow task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) for checks specific to a project. Use validation tasks for messages visitors can correct, and screening tasks for spam decisions. The [workflow reference](/developers/submission-workflow) lists the available positions.
