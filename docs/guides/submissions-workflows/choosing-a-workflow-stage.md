# Choosing a Workflow Stage

Formie's six stages have fixed responsibilities and order. Extend the appropriate stage with a task when your project needs an additional check or action.

Suppose an order needs a project-specific reference check after ordinary field validation. Register a Submit task after `validate.submission`. Invalid input then stops before spam checks, CAPTCHA or persistence. The [custom task walkthrough](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch) builds and tests that example in a Craft module.

## Match the Work to Its Boundary

| Work | Placement |
| --- | --- |
| Validate accepted field values before screening | After `validate.submission` |
| Evaluate a content policy before CAPTCHA | Before `screen.verifyCaptcha` |
| Observe the first persisted submission before payment processing | After `persist.submission` |
| Enqueue work before integrations | Before `dispatch.triggerIntegrations` |
| Observe the final domain outcome | After the Finalize stage |

Formie registers task IDs, handlers and applicable operations together. Choose Submit, SaveDraft, Revise or PaymentReplay explicitly. A task can use `TaskResult::continue()` or stop with a typed outcome; stage/task observation events cannot cancel execution.

Use `prepend()` or `append()` when a stage boundary is sufficient. Use a stable public anchor when another task must run immediately before or after yours. Navigation-state resolution, progression enforcement, persistence planning and finalisation bookkeeping are internal rather than anchors, and extensions cannot replace or reorder stages.

After adding the task, test the intended operation and an operation that should skip it. For example, a Submit validation rule should reject the relevant input on submission while allowing a draft to save. The [workflow reference](/developers/submission-workflow) lists every public anchor and explains version conflicts and retries.
