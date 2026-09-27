# Integration Dispatch and Policies

Use **Forms → your form → Integrations → Settings** to choose when integrations run and when notification emails are sent. For example, a registration form might create a Craft user before showing its success page, then send the contact to a mailing service in the background. Global connections and credentials are configured under **Formie → Integrations**.

## Execution Order

Enable dispatch to arrange integrations into two groups, called lanes:

| Lane | Behaviour |
| --- | --- |
| Synchronous | Runs before the visitor receives the submission result. Use this for a User or Entry integration whose result is needed on the success page. |
| Queued | Runs in the background, in the order you choose, after synchronous integrations finish. |

Move integrations within each lane to set their order. Synchronous integrations always run first. If queue use is disabled globally, queued integrations run afterward in the same submission request. When dispatch is disabled, the global `useQueueForIntegrations` setting decides whether integrations use the queue.

Choose **Continue** to run later integrations after one fails, or **Stop** to skip the remaining steps.

## Notification Timing

Set a default timing for the form, then override it in an individual notification's Advanced settings when needed.

| Timing | Sends when |
| --- | --- |
| Before integrations | The submission is saved and the notification's conditions pass. |
| After synchronous integrations | The synchronous integrations finish and meet the completion policy. Queued integrations may still be waiting. |
| After finalized delivery attempts | All integrations in that run finish and meet the completion policy. |

The **Delivery Completion Policy** decides which results allow a notification to send. The default requires each integration to have **succeeded or skipped**. Choose **Also allow failed or rejected** if the email should also send after a known failure. A skipped integration might have an unmet condition or missing opt-in.

An **unknown** result means the service may have received the request, but Formie could not confirm it. Notifications that wait for integrations remain blocked until that result is resolved. Pending or running integrations also keep those notifications waiting.

For the registration example, put the User integration in the synchronous lane and send the confirmation email after synchronous integrations. The email can then use the created user without waiting for the mailing service. Enable re-running the User integration on edit if it should update that user later.

## Results and Recovery

Open **Submission Delivery History** on the submission to see what ran and whether it finished.

| Result | Meaning | What to Do |
| --- | --- | --- |
| Succeeded | The operation completed. | No retry is needed. |
| Skipped | The integration did not need to run, or an earlier failure stopped it. | Check conditions, opt-in and execution order if this was unexpected. |
| Rejected | Validation or the provider refused the operation. | Correct the reported problem before running it again. |
| Failed | The operation failed. | Use the available retry action when Formie confirms retrying is safe. |
| Unknown | The service may have accepted the request. | Check the service before confirming the result in Formie. |

Formie remembers completed steps so a retry does not repeat them. For example, if a CRM contact was created but adding it to a list failed, a safe retry can continue with the list step.

For an unknown result, first check the remote account. A user with reconciliation permission can then select **Confirm delivered** or **Confirm not delivered** and enter a reason. Resolve any unknown individual operations before the overall attempt. Confirming non-delivery allows the original attempt to be retried safely.

If later steps need a provider response that Formie never received, confirming delivery alone cannot supply that missing data. Further support may be needed to continue. Do not start a separate manual run to get around an unresolved result: it could duplicate something the service already received.

## Manual and Force Runs

Manual runs still check integration conditions and opt-in. Automatic re-run settings separately control whether integrations run on initial submission, front-end editing, control panel saving or unmarking spam.

A force run can override conditions and opt-in. It requires additional permission, permission to save the form's submissions, and a recorded reason. It cannot bypass an unresolved earlier delivery or send to a blocked destination. Developers can initiate it through the integration services described in [Custom Integrations](/developers/custom-integration/overview).

## Queue Diagnostics

Craft's queue detail screen includes **Formie delivery diagnostics** for supported Formie jobs. It shows the mapped values, provider errors and delivery results. If the link is unavailable, open **Submission Delivery History** instead.

Copy or download the support bundle when you need help investigating a failure. Viewing diagnostics requires access to both diagnostics and the form's submissions. Exporting sensitive responses requires a separate permission and confirmation; treat those downloads as private customer data.

Detailed evidence for completed attempts is removed after 30 days. Unresolved attempts keep the data needed for investigation, and delivery history remains after detailed evidence expires. Expired responses cannot be used to resume steps that depend on their contents. Keep the Formie security key with database backups so encrypted records can be restored.

## Network and Credential Settings

Prefer environment references such as `$CRM_API_KEY` for credentials managed by your developer. Web Request headers and HTTP authentication can use an environment variable only when its name is included in `referenceEnvironmentAllowlist` in `config/formie.php`.

Web Request destinations must be publicly reachable HTTP or HTTPS URLs on ports 80 or 443. Private network addresses and redirects are blocked, so enter the final destination URL. Per-form settings cannot override a connection's protected credentials or API address.

## Workflow and Extension Points

For custom integration code, use Formie's [integration services](/developers/custom-integration/overview) and [Integration Events](/developers/events/integration-events). Calling a provider directly from a submission save event skips the checks and retry handling described here.
