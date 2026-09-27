# Submission Workflow and Stages Explained

A visitor clicking Next, saving a draft and completing a form need different processing. Formie describes the requested operation explicitly, then runs the applicable tasks in a fixed sequence. This explains why invalid input doesn't spend a CAPTCHA token, why drafts don't send confirmation emails and why a pending payment keeps a submission incomplete.

Before changing a submission, Formie checks that the visitor can access it and that it has not been updated since the form was loaded. This prevents an older browser tab from overwriting newer answers.

## Follow a Submission Through the Stages

| Stage | What Happens |
| --- | --- |
| Preflight | Interpret navigation, apply defaults, clear hidden values, enforce progression and select the next visible page. |
| Validate | Check fields, the form and questionnaire retake eligibility. Invalid input stops here. |
| Screen | Evaluate content spam, then verify CAPTCHA when the spam decision is not already known. |
| Persist | Save the record, process applicable payments and recalculate questionnaire results. |
| Dispatch | Start applicable notifications and integration work. |
| Finalize | Finish page and upload tracking, apply the configured spam response and return the result. |

Formie then returns the result to the page or application. If payment is required, the saved submission stays incomplete until payment succeeds. It may ask the visitor to complete another payment step or wait for the provider.

## Understand the Operations

**Submit** handles a visitor moving forward through the form or completing the final page. It also handles a resumed form. Visitors can go back without validating the current page, but cannot jump over pages that still need answers.

**SaveDraft** saves progress without requiring valid answers, checking content for spam, running CAPTCHA or sending notifications and integrations. Access and rate-limit checks still apply. See [Save and Continue Later](/guides/submissions-workflows/save-and-continue-later) for setup.

**Revise** updates an existing submission. It validates the answers and runs integrations or status-change notifications configured for edits. Creating a submission in the control panel validates the whole record but does not charge payments or automatically send completion notifications.

**PaymentReplay** continues a saved submission after the payment provider confirms its result. It uses the accepted answers without asking the visitor to validate or complete CAPTCHA again. A successful payment allows completion notifications and integrations to proceed.

## Choose an Extension Point

For code that reacts to an accepted page or a completed submission, use the [page and completion events](/guides/submissions-workflows/run-custom-code-on-page-submit-or-form-submit). For an audit trail around a named phase, use [workflow observation events](/guides/submissions-workflows/using-submission-workflow-events).

For an ordered check that can stop processing, register a [custom task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) inside a fixed stage. Registration must declare its applicable operations. Stages cannot be added or reordered.

Saving a submission directly through Craft’s element service does not run this workflow or automatically send notifications and integrations. If custom code needs that processing, use the [submission workflow API](/developers/submission-workflow).
