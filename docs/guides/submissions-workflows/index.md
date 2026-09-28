# Submissions & Workflows

Submission lifecycle, statuses, screening, reports, and rendering stored content.


## [Editing Submissions on the Front End](/guides/submissions-workflows/editing-submissions-on-the-front-end)

Formie can render a saved submission back into the form so someone can update it from the front end — account areas, application review flows, or any case where data may change after first submit.

## [Relations Between Submissions and Entries](/guides/submissions-workflows/relations-between-submissions-and-entries)

Submissions can be related to Craft elements — entries, categories, products, users — so form data stays connected to the content it belongs to. Relations are set in Twig before render, not chosen manually by editors in the form builder.

## [Saved Reports and Scheduled Delivery](/guides/submissions-workflows/saved-reports-and-scheduled-delivery)

Getting-started walkthrough for saved reports and scheduled email delivery — links to the reports reference for formats, permissions, cron, and troubleshooting.

## [Run Custom Code on Page Submit or Form Submit](/guides/submissions-workflows/run-custom-code-on-page-submit-or-form-submit)

Every page POST uses the same pipeline. This guide shows the two public hooks — after a page submit, and when the form is submitted — without `afterTask` archaeology.

## [Submission Screening Rules in Practice](/guides/submissions-workflows/submission-screening-rules-in-practice)

Submission screening is Formie's unified layer for deciding whether a submission is legitimate before it is saved and dispatched. This guide shows how guards, captchas, and content rules work together in practice — and how to tune them without blocking real users.

## [Submission Statuses and Conditional Workflows](/guides/submissions-workflows/submission-statuses-and-conditional-workflows)

Submission **statuses** are team workflow labels on saved submissions. **Conditions** change what the form shows and which notifications send based on answers. Together they let you route work after submit and tailor the form experience before submit.

## [Submission Workflow and Stages Explained](/guides/submissions-workflows/submission-workflow-and-stages-explained)

Formie runs every submission through a staged pipeline. This guide explains what each stage does, which requests run the full flow, and how to think about hooks before you write PHP.

## [Using Submission Workflow Events](/guides/submissions-workflows/using-submission-workflow-events)

Observe validation and integration dispatch from a Craft module. Use a registered task when a check needs to stop processing.

## [Adding a Custom Workflow Task from Scratch](/guides/submissions-workflows/adding-a-custom-workflow-task-from-scratch)

When one ordered step inside an existing stage is what you need — before integrations, after spam checks, and so on — register a custom task and insert it relative to a built-in anchor. This walkthrough adds and tests an order-reference rule after field validation.

## [Choosing a Workflow Stage](/guides/submissions-workflows/choosing-a-workflow-stage)

Choose one of the six fixed stages for your custom task. This guide maps common checks and actions to their supported public anchors.

## [Save and Continue Later](/guides/submissions-workflows/save-and-continue-later)

Long and multi-page forms lose completions when visitors get interrupted. Formie stores incomplete submissions in the database, optionally exposes a save button with resume links, and can restore in-progress state automatically when someone returns. This walkthrough configures the feature end-to-end for a job application or registration form.
