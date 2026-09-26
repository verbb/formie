# Integration Dispatch and Policies

Use **Forms → your form → Integrations → Settings** to control integration order, notification timing and re-run policies. Global connections and credentials remain under **Formie → Integrations**. Each form owns its enabled bindings, conditions and annotated mapping settings.

## Execution Order

Dispatch settings are available with one integration. Enable dispatch and assign each integration to a lane:

| Lane | Behavior |
| --- | --- |
| Synchronous | Runs during the submission request, top to bottom. Use for User or Entry integrations whose result is needed by the success page. |
| Queued | Runs after the synchronous lane, in the configured order within one durable dispatch job. Enqueued does not mean remotely delivered. |

Move steps within a lane or change their execution setting. Formie always finishes synchronous steps before enqueueing the queued lane; a mixed list cannot interleave request execution with remote queue completion. If global queue use is disabled, queued-lane steps run after synchronous steps in the same request.

**Continue** runs later integrations after a failure. **Stop** records remaining steps as skipped. When dispatch is disabled, the global `useQueueForIntegrations` setting applies.

## Notification Timing

A form sets the default timing. Each notification can override it in Advanced settings.

| Timing | Sends when |
| --- | --- |
| Before integrations | The submission has been saved and notification conditions pass. |
| After synchronous integrations | All configured synchronous integrations meet the completion policy. Queued deliveries may still be pending. |
| After finalized delivery attempts | Every configured integration meets the completion policy for the same execution identity. |

The **Delivery Completion Policy** defaults to requiring **succeeded or skipped** results. Choose **Also allow failed or rejected** for notifications that should send after unsuccessful but known outcomes. An **unknown** outcome blocks after-delivery notifications under either policy until it is reconciled. Pending and running deliveries also block them. Skipped includes disabled integrations, unmet conditions, missing opt-in and steps stopped by an earlier failure.

The recommended User and Entry setup runs element integrations synchronously, sends default notifications after that lane, and enables the selected element integrations to re-run on edit. It does not wait for queued marketing integrations.

## Results and Recovery

| Result | Meaning | Recovery |
| --- | --- | --- |
| Succeeded | The operation completed. | Formie reuses the recorded result. |
| Skipped | The operation was ineligible or deliberately not run. | Review conditions, opt-in and the plan. |
| Rejected | Local validation or the provider refused the operation. | Correct the configuration before starting a new intended run. |
| Failed | The operation failed; its result states whether retry is safe. | Retry only when the recorded result permits it. |
| Unknown | The remote service may have accepted the operation. | Confirm the outcome with the provider before reconciliation. |

Every integration and notification has a durable attempt. Providers using Formie's request helpers also have child attempts for individual writes. A safe retry retains the execution identity, reuses successful child responses and retries only definitely failed steps. Changed operation parameters cannot reuse an existing child identity. Transport uncertainty stops automatic replay, including when Craft retries a failed queue job.

Open **Submission Delivery History**, select an attempt and inspect its result and operation history. Editors with reconciliation permission can record **Confirm delivered** or **Confirm not delivered**, with an audit reason. Reconcile uncertain children before their parent. Confirming delivery does not invent a missing provider response: if subsequent operations require response data that was never recorded, automatic continuation remains blocked. Confirmed non-delivery permits a safe retry of the original parent identity. Notifications also block a new send identity while an earlier delivery remains unresolved. Element integrations retain their created element identity when later operations fail. A new manual run is a separate intended execution and should not be used to work around an unresolved attempt.

## Manual and Force Runs

Manual runs bypass the automatic trigger schedule but still check integration conditions and opt-in. Re-run policies choose which automatic events are eligible: initial submission, front-end editing, control panel saving and unmarking spam.

A force run uses the separately permissioned `IntegrationTriggers::forceIntegration()` path and requires permission to save submissions for the form and a reason. It records ordinary eligibility and the conditions/opt-in overrides in immutable execution context. Force does not bypass an unknown prior delivery or destination security checks.

## Queue Diagnostics

For an identifiable Formie job, Craft's queue detail screen includes **Formie delivery diagnostics**. This opens a Formie-owned Plugin Kit modal. If Craft changes its queue markup or a legacy job has no attempt locator, use **Submission Delivery History** as the stable fallback.

The modal shows mapping inputs, safe submission projections, operation results, provider errors and retry/reconciliation checkpoints. Copy or download the support bundle for troubleshooting. Values are escaped, credential keys and known secrets are redacted, bodies and checkpoint counts are bounded, and both diagnostics permission and the form's submission-view permission are required. Sensitive response export additionally requires its own permission and explicit acknowledgement; exports are audited.

Operational settings and exact responses are encrypted. Completed evidence is purged after 30 days; unresolved attempts retain the data required for reconciliation. Operation identities and result, retry, reconciliation and export audit checkpoints remain so evidence expiry cannot enable a duplicate write. A response that has expired cannot be replayed to a dependent step. Keep the security key with database backups. Queue jobs themselves contain only stable locators and are never rewritten to add diagnostics.

## Network and Credential Settings

Prefer environment references such as `$CRM_API_KEY` for managed credentials. Literal connection settings and permitted per-form secrets are encrypted at rest. Web Request headers and HTTP authentication may resolve `$ENV` references only when the variable name is included in `referenceEnvironmentAllowlist`; this prevents a form editor from forwarding unrelated server secrets. Environment-owned connections remain project-config owned; per-form settings cannot overwrite unannotated credentials or API domains.

Web Request and other form-configured public destinations must use HTTP or HTTPS on ports 80 or 443. Formie rejects loopback, private, link-local, metadata, reserved and transition addresses, checks DNS results, pins the validated address using cURL and disables redirects and proxies. Public destinations use a clean client rather than inheriting another provider's credentials. Authenticated provider calls must remain on their configured provider origin. Maximizer API discovery must resolve to that same origin.

## Workflow and Extension Points

Delivery follows successful submission persistence in the dispatch stage. Queueing records scheduling intent; it does not finalize remote delivery. Avoid invoking integrations from an element after-save hook, which bypasses workflow eligibility and identity. Use the runner and semantic [Integration Events](/developers/events/integration-events). New providers should follow the [custom integration contracts](/developers/custom-integration/overview).
