# Submission workflow and stages explained

Formie processes submissions through a staged workflow.

Instead of treating submission handling as one large save step, Formie moves through a series of smaller stages in order. That lets Formie handle multi-page navigation, draft saving, validation, captcha and spam checks, payment handling, persistence, notifications, integrations, and the final browser response in a predictable way.

If you are extending submission handling, the workflow tells you where your code belongs. Instead of guessing where custom logic should run, you can choose the stage or task that owns that responsibility.

That model also explains practical behaviour worth knowing early: why save-and-continue skips spam checks, why integrations wait until after payment succeeds, and why hooking `Submission::EVENT_AFTER_SAVE` is not always the same as hooking into a front-end submit.

This guide walks through what runs in each stage, which requests use the full pipeline, and how to choose an extension point. For stage and task names, code examples, and events, see [Submission Workflow](/developers/submission-workflow).

## Prerequisites

- [Submission Workflow](/developers/submission-workflow) — canonical reference when you start writing code
- [Submission Events](/developers/events/submission-events)

## The pipeline at a glance

Each stage contains smaller **tasks** with stable names — useful when you extend the workflow in PHP, but not something you need to memorise to understand the overall flow.

On a normal front-end submit, Formie moves through eight stages in order:

1. **Prepare** — set up the request; restore save-and-continue or draft context
2. **Normalize** — resolve page flow, back navigation, default values
3. **Validate** — run field and form validation rules
4. **Screen** — submission guards, captchas, spam keyword and content rules
5. **Authorize** — stop if earlier errors exist; resolve payment state
6. **Save** — persist the submission; process payments
7. **Dispatch** — send notifications; trigger integrations
8. **Finalize** — build the response; apply next-page or success behaviour

The order matters. Validation runs before save. Integrations run in **dispatch**, after the submission is stored and payment has had its chance to succeed.

```
Browser POST
    → prepare (draft context, request init)
    → normalize (pages, defaults)
    → validate (field rules)
    → screen (guards, captcha, spam)
    → authorize (errors, payment state)
    → save (persist, charge card)
    → dispatch (email, CRM, automations)
    → finalize (response, replay token)
    → Browser response
```

Payment provider callbacks re-enter through **payment replay** — a shortened path through save, dispatch, and finalize rather than repeating validation from scratch.

## Not every request runs the full pipeline

The same form can trigger different workflow **modes**. The mode decides which stages run:

| Mode | Typical trigger | What you should expect |
| --- | --- | --- |
| **Submit** | Visitor completes a step or final submit | Full pipeline — validation, screening, save, dispatch |
| **Save draft** | Save and continue, or back navigation on a multi-page form | Persists progress only — no validation, screening, notifications, or integrations |
| **Edit existing** | Front-end or control panel edit of a saved submission | Validates and saves; may re-run integrations per form settings; skips notifications and screening |
| **Payment replay** | Stripe/GoCardless callback or status poll | Resumes after payment — save, dispatch, finalize |

If you add custom logic to **dispatch**, it will not run when a visitor only saves a draft. That is intentional — drafts are not finished submissions.

See [Save and continue later](/guides/submissions-workflows/save-and-continue-later) for how draft mode fits multi-page forms, and [Submission screening rules in practice](/guides/submissions-workflows/submission-screening-rules-in-practice) for what runs during the **screen** stage on a full submit.

## Where your custom code belongs

You do not always need a custom workflow task. Match the hook to what you actually care about:

**You need to react whenever the submission element is saved** — including control panel edits, imports, or API updates — use a submission **element event** such as `Submission::EVENT_AFTER_SAVE`. That fires on the element, not on the front-end submit request.

**You need logic only during a front-end submit** — for example extra screening before spam checks, or work that should never run on draft saves — listen to **workflow stage or task events** and check the request mode is `submit`.

**The stage is right but you need one more step inside it** — for example queue a job after save but before Mailchimp runs — register a **custom workflow task** in that stage, positioned before or after a built-in task.

**You need a new phase in the pipeline** — for example a fraud score after screening but before save — register a **custom workflow stage**.

If you are unsure, start with the smallest hook. Element events are the broadest; workflow events are narrower and usually what you want for submit-time behaviour. See [Using submission workflow events](/guides/submissions-workflows/using-submission-workflow-events) for copy-paste listener patterns.

## When you are ready to implement

The [Submission Workflow](/developers/submission-workflow) developer reference has:

- Default task names per stage
- Workflow mode details
- Copy-paste examples for stage events, custom tasks, and task results
- The full list of registration and before/after events

For full walkthroughs with module structure and copy-paste classes, see [Using submission workflow events](/guides/submissions-workflows/using-submission-workflow-events), [Adding a custom workflow task from scratch](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch), and [Adding a custom workflow stage from scratch](/guides/submissions-workflows/adding-a-custom-workflow-stage-from-scratch).

[Submission Events](/developers/events/submission-events) documents every event payload if you need the full reference.

## Related

- [Using submission workflow events](/guides/submissions-workflows/using-submission-workflow-events)
- [Adding a custom workflow task from scratch](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch)
- [Adding a custom workflow stage from scratch](/guides/submissions-workflows/adding-a-custom-workflow-stage-from-scratch)
- [Submission Workflow](/developers/submission-workflow)
- [Submission Events](/developers/events/submission-events)
- [Submission screening rules in practice](/guides/submissions-workflows/submission-screening-rules-in-practice)
- [Integration dispatch and policies](/guides/integrations/integration-dispatch-and-policies)
- [Save and continue later](/guides/submissions-workflows/save-and-continue-later)
- [Submission statuses and conditional workflows](/guides/submissions-workflows/submission-statuses-and-conditional-workflows)
