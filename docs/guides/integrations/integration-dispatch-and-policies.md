# Integration dispatch and policies

Imagine a contact form that sends to Mailchimp, posts to a CRM, and fires a Slack notification — but only when the user opts in, and only after the submission is saved. Formie separates **what** each integration does (credentials, mapping, API calls) from **when and how** it runs on each submission. The form builder's Integrations tab controls that dispatch behaviour — order, queue vs immediate execution, re-run policies, and per-integration conditions — without changing your integration PHP.

This guide walks through configuring those controls on a real multi-integration form.

## Where dispatch settings live

Open **Formie → Forms → {Your Form} → Integrations**. Each integration has its own settings panel. Form-level controls appear under **Integrations → Settings** at the bottom of the tab.

| Setting | Scope | Purpose |
| --- | --- | --- |
| **Enabled** | Per integration | Whether the integration runs for this form |
| **Conditions** | Per integration | Whether the integration runs for a given submission |
| **Mapping / URL / list** | Per integration | Provider-specific configuration |
| **Dispatch** | Form | Execution order, queue vs immediate, notification timing |
| **Per-integration behaviour** | Form | When integrations re-run after the initial submission |

Integration **credentials** (API keys, OAuth connection) are managed under **Formie → Integrations** and are shared across forms. Per-form settings reference those credentials but do not duplicate them.

## Site scope and multi-site

Forms can exist on multiple Craft sites, but integration configuration is **structural** — the same integrations, conditions, and dispatch plan apply on every site where the form is enabled.

What differs per site:

- Translated labels and messages (form builder site overrides)
- The `siteId` stored on each submission

Integration conditions can target site context using `{submission:site}` variables — site name and handle appear in the condition field picker. Use conditions when one form should send to different providers (or skip dispatch) based on which site received the submission.

For how site availability differs from translation, see [Multi-Site & Translation](/forms/multi-site).

## Dispatch conditions (per integration)

Each integration can enable **Conditions** to control whether it runs for a particular submission. This is separate from field visibility conditions — it gates the integration trigger itself.

1. Open the integration on the form’s Integrations tab.
2. Enable **Conditions**.
3. Build rules using submission fields, status, date, site name, or site handle.

Conditions are evaluated at dispatch time. If rules do not match, the integration is skipped for that submission — no API call, no queue job.

Common patterns:

- Run a CRM integration only when status is **Qualified**
- Skip marketing integrations for submissions marked as spam (spam submissions skip dispatch by default)
- Send to a regional automation endpoint only when `{submission:site}` matches a specific site handle

Re-run policies and manual triggers **still respect conditions**. An operator cannot manually re-run an integration whose conditions fail for that submission.

## Integration dispatch orchestration

When a form has **two or more** payload integrations enabled, **Integrations → Settings → Dispatch** becomes available.

### Enable Integration Dispatch

Turn on dispatch to run integrations **sequentially** in a defined order instead of independently through the default queue.

| Control | Options | Effect |
| --- | --- | --- |
| **Notification timing** | Before integrations / After integrations | When default-timed email notifications send relative to the integration sequence. Individual notifications can override this in Advanced settings. |
| **Failure policy** | Continue / Stop | Whether later integrations run when one fails |
| **Step list** | Ordered handles with mode | Execution order and queue vs immediate per integration |

Each step can run as:

- **Queued** — pushed to Craft’s queue (recommended for external APIs)
- **Immediate** — runs during the submission request before the response returns

Use **Immediate** for element integrations that must finish before notifications or the success page — User registration, Entry creation, and similar flows where the next step depends on the created element.

Formie shows a **Recommended for User & Entry flows** shortcut that enables dispatch, sets notifications to run after integrations, marks User/Entry integrations as immediate, and configures re-run on edit for those integrations.

When dispatch is disabled, enabled integrations run independently using Formie’s global `useQueueForIntegrations` setting — see [Configuration](/get-started/configuration).

### Dispatch requires two active integrations

Orchestration only runs when at least two integrations are enabled on the form. If dispatch is enabled but only one integration is active, Formie falls back to default behaviour and shows a warning in the builder.

## Re-run policies

By default, integrations run **once on submit**. Formie adds per-integration re-run policies under **Integrations → Settings → Per-integration behaviour**.

| Policy | Runs on |
| --- | --- |
| **Once on submit** | Initial front-end submission only (default) |
| **Also when submission is edited** | Submit, front-end edit, and control panel save |
| **Custom…** | Pick from: Initial submission, Front-end edit, Control panel save, Unmarked as not spam |

Re-run policies matter for Entry and User integrations that should update linked elements when an editor changes a submission in the control panel. They also apply when bulk status changes trigger a CP save.

Integration conditions still apply on re-runs. Changing a submission’s status does not bypass condition checks.

## Plugin-wide settings

These plugin settings affect all forms:

| Setting | Location | Recommendation |
| --- | --- | --- |
| `useQueueForIntegrations` | **Formie → Settings → General** | Enable on production so API calls do not slow submissions |
| `queuePriority` | Same | Tune if integration jobs compete with other queue work |
| `sendIntegrationAlerts` | **Formie → Settings → Integrations** | Email admins when an integration fails |
| `redirectUri` | Config / env | OAuth redirect override for multi-environment setups |

API keys and secrets should use `.env` variables. Formie resolves env syntax when settings are loaded and does not export resolved secrets to project config — see [Configuration](/get-started/configuration).

## How dispatch fits the workflow

Integrations trigger during the **dispatch** stage of the submission workflow — after validation, screening, and save:

1. `dispatch.guardDispatchEligibility` — checks whether dispatch should run
2. `dispatch.sendNotifications` — may run before or after integrations depending on dispatch plan
3. `dispatch.triggerIntegrations` — runs enabled integrations that pass conditions and re-run policy
4. `dispatch.markDispatchFinalized` — records completion

Edit-existing and save-draft workflow modes skip most dispatch work. Edit-existing can re-run integrations when the re-run policy allows — see [Submission Workflow](/developers/submission-workflow).

Avoid triggering integrations from `Submission::EVENT_AFTER_SAVE`. That bypasses re-run policies, workflow idempotency, and the CP save vs workflow split. Use `Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION` or `IntegrationTriggers` when you need custom dispatch.

## Putting it together

A typical registration form with User, Mailchimp, and Web Request integrations might configure:

1. **Dispatch enabled** — User → Immediate, Mailchimp → Queued, Web Request → Queued
2. **Notification timing** — After integrations (activation email needs the user element first)
3. **User integration re-run** — Also when submission is edited
4. **Mailchimp conditions** — Only when an Agree field is checked
5. **Global queue** — `useQueueForIntegrations` enabled

Submit once from the front end to create the user immediately, queue marketing and automation calls, and send the activation email after the user exists.

## Related

- [Form Builder](/forms/form-builder)
- [Submission Workflow](/developers/submission-workflow)
- [Configuration](/get-started/configuration)
- [Re-run failed integrations from the control panel](/guides/integrations/re-run-failed-integrations-from-the-control-panel)
- [Project config, environment, and control panel settings](/guides/configuration/project-config-environment-and-control-panel-settings)
