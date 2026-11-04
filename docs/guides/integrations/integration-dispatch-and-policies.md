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
| After synchronous integrations | The synchronous integrations finish. Queued integrations may still be waiting. |
| After finalized delivery attempts | All integrations in that run finish. |

Timing controls order, not success. Succeeded, skipped and failed integrations have all finished, so a failure does not suppress an otherwise eligible notification. A skipped integration might have an unmet condition or missing opt-in, or have been skipped by the Stop policy. Use an explicitly authored notification condition when the email requires a particular integration to succeed.

Pending or running integrations keep those notifications waiting.

For the registration example, put the User integration in the synchronous lane and send the confirmation email after synchronous integrations. The email can then use the created user without waiting for the mailing service. Enable re-running the User integration on edit if it should update that user later.

## Results and Retries

A delivery either passes or fails. When a queued delivery fails, its job fails in Craft's queue. Open **Utilities → Queue Manager**, select the failed Formie job and choose **Diagnostics** to see what happened.

| Result | Meaning | What to Do |
| --- | --- | --- |
| Succeeded | The delivery completed. | Nothing. |
| Skipped | The integration did not need to run, or an earlier failure stopped it. | Check conditions, opt-in, and execution order if this was unexpected. |
| Failed | The delivery did not complete. The error is shown in **Diagnostics**. | Fix the cause if needed, then click **Retry**. |

**Retry** always runs the job again, using the submission's current values and the current integration settings. Formie remembers steps that already succeeded and skips them. For example, if a CRM contact was created but adding it to a list failed, a retry only sends the list step. You can correct a field mapping and retry, and the failed step uses the new mapping.

Every step that didn't succeed is sent again. If a request timed out after the service had already accepted it, Formie has no way of knowing, so retrying can create a duplicate. Check the service first if a duplicate would matter, such as for a text message that costs money to send.

A step that already succeeded stays done, even if you've since changed its mapping. To send everything again from the start, choose the integration from the **Integrations** menu on the submission's edit page, which starts a new run.

Integration results belong to the run that produced them. A queued notification reads results from its own run, even if a later edit has already run the same integration again. Results from another run are not used as a fallback.

## Manual Runs

Manual runs still check integration conditions and opt-in. Automatic re-run settings separately control whether integrations run on initial submission, front-end editing, control panel saving or unmarking spam.

## Queue Diagnostics

Craft's queue detail screen includes a **Diagnostics** button for failed Formie jobs with retained attempt data. It shows a plain-language explanation of what failed, a delivery timeline, and tabbed evidence for errors, provider requests and responses, submission data, and other recorded activity.

Use **Export** to download the redacted support bundle when requesting support. Credentials are removed, but the bundle can still contain mapped submission values, provider evidence, child-operation details, and other personal data. Viewing diagnostics requires access to both diagnostics and the form's submissions.

Evidence records the stored and normalized submission values used at delivery time. Notification evidence includes the rendered subject, HTML and text, addressing, headers and attachment names before the mail transport runs. Error evidence includes the exception chain and stack locations without function arguments. Support does not need to reconstruct these details from a submission or notification that may have changed since delivery. Large checkpoints and export limits are marked explicitly when truncated; attachment binaries are not included.

Detailed evidence for completed attempts is removed after 30 days by default. Change `deliveryEvidenceRetentionDays` in `config/formie.php` to choose another retention period. Failed attempts keep the data needed to retry them, and delivery history remains after detailed evidence expires. If a step's saved response has expired, a retry sends that step again. Keep the Formie security key with database backups so encrypted records can be restored.

## Network and Credential Settings

Prefer environment references such as `$CRM_API_KEY` for credentials managed by your developer. Web Request headers and HTTP authentication can use an environment variable only when its name is included in `referenceEnvironmentAllowlist` in `config/formie.php`.

Web Request destinations must be publicly reachable HTTP or HTTPS URLs on ports 80 or 443. Private network addresses and redirects are blocked, so enter the final destination URL. Per-form settings cannot override a connection's protected credentials or API address.

## Workflow and Extension Points

For custom integration code, use Formie's [integration services](/developers/custom-integration/overview) and [Integration Events](/developers/events/integration-events). Calling a provider directly from a submission save event skips the checks and retry handling described here.
