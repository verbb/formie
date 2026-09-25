# Submission Workflow and Stages Explained

A visitor clicking Next, saving a draft and completing a form need different processing. Formie describes the requested operation explicitly, then runs the applicable tasks in a fixed sequence. This explains why invalid input doesn't spend a CAPTCHA token, why drafts don't send confirmation emails and why a pending payment keeps a submission incomplete.

Before processing starts, the submission boundary resolves the form and optional saved record, checks the caller's authority and applies request integrity and rate safeguards. It locks the operation and submission resource, then checks the caller's expected version before applying submitted values. A stale page cannot silently overwrite a newer submission.

## Follow a Submission Through the Stages

| Stage | What Happens |
| --- | --- |
| Preflight | Interpret navigation, apply defaults, clear hidden values, enforce progression and select the next visible page. |
| Validate | Check fields, the form and questionnaire retake eligibility. Invalid input stops here. |
| Screen | Evaluate content spam, then verify CAPTCHA when the spam decision is not already known. |
| Persist | Save the record, process applicable payments and recalculate questionnaire results. |
| Dispatch | Start applicable notifications and integration work. |
| Finalize | Apply outward spam policy, progression, upload bookkeeping and the terminal outcome. |

The HTML, AJAX, REST or GraphQL adapter then maps that outcome to its response. A payment requiring another action or waiting on a provider is an expected outcome. The first persisted payment submission is incomplete; it becomes complete only after the required payment succeeds.

## Understand the Operations

Submit accepts the current page or attempts final completion. Continuing an incomplete submission remains Submit. Back and Target describe navigation separately from the operation; a target cannot skip an intervening page that still needs validation.

SaveDraft persists progress without field validation, content spam checks, CAPTCHA or dispatch. It still requires valid request integrity, ownership and rate safeguards. [Save and continue later](/guides/submissions-workflows/save-and-continue-later) explains the visitor-facing flow.

Revise edits an existing record without visitor progression. It validates the record, recalculates questionnaire results and applies configured edit integration and status-change notification policies. Control panel creation uses an explicit administrative Submit policy: whole-record validation and the chosen status, without visitor screening, payments or automatic completion dispatch.

PaymentReplay reads a saved submission and payment after a verified provider/domain callback. It does not repopulate fields from browser input, repeat validation or spend CAPTCHA. If payment permits completion, dispatch can continue.

## Choose an Extension Point

For code that reacts to an accepted page or a completed submission, use the [semantic page and completion events](/guides/submissions-workflows/run-custom-code-on-page-submit-or-form-submit). For an audit trail around a named phase, use [workflow observation events](/guides/submissions-workflows/using-submission-workflow-events).

For an ordered check that can stop processing, register a [custom task](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) inside a fixed stage. Registration must declare its applicable operations. Stages cannot be added or reordered.

Direct Craft element persistence remains available for imports and administrative code. It raises element events but does not run this workflow or automatically send notifications and integrations. Use an explicit operation when you need those lifecycle policies, and check its typed result before deciding what to show the caller.
