# Re-run failed integrations from the control panel

Integrations can fail when a provider is down, credentials expire, or mapping sends invalid data. Formie lets operators manually re-trigger an integration for an existing submission from the control panel — without resubmitting the form or writing custom code.

This guide covers manual re-runs from the submission edit screen. For email delivery failures, use [Sent Notifications](/forms/sent-notifications) to inspect and resend notification messages separately.

## Prerequisites

- A saved submission with at least one enabled payload integration on its form
- Permission to edit submissions in the control panel

## When to re-run manually

Manual re-runs help when:

- A queue job failed and the provider never received the data
- Credentials were fixed after submissions already failed
- Mapping was corrected and you need to push updated submission values
- A transient API error occurred and a retry should succeed

Manual re-runs are **not** a substitute for fixing systemic problems. If every submission fails, check integration credentials, mapping, provider status, and Craft queue logs first.

Enable **Send Email Alert for Failed Integration** under **Formie → Settings → Integrations** so your team is notified when failures happen automatically — you may not need to discover them by checking each submission.

## Re-run from the submission edit screen

1. Go to **Formie → Submissions** and open the submission.
2. In the sidebar, find the **Integrations** dropdown (visible when the form has enabled payload integrations and the submission is not spam or incomplete).
3. Select the integration you want to run.
4. Confirm the prompt — **Are you sure you want to trigger the {name} integration?**
5. Wait for the page to reload. A success or error flash message appears.

The re-run uses the submission’s current field values and the form’s current integration settings. If you edited the submission or changed mapping since the original dispatch, the new values and settings apply.

### What manual re-runs respect

Manual triggers call `IntegrationTriggers::dispatchManualIntegration()`, which:

- Loads the integration with the form’s saved settings
- Populates integration context from the submission
- Sends the payload through the normal `sendPayload()` path
- Fires integration events and logging

Manual re-runs **still evaluate integration conditions**. If conditions fail for the submission, the integration will not send data even when triggered manually.

Manual re-runs bypass re-run **policy** checks — policies govern automatic triggers on edit or resubmit, not operator-initiated runs.

### What is not re-run

Manual integration re-runs do not:

- Resend email notifications (use Sent Notifications for that)
- Re-validate or re-save the submission
- Re-run captcha or spam screening
- Trigger other integrations unless you run each one separately

## Re-run from the command line

For bulk recovery or automation, use the Formie console command:

```bash
php craft formie/submissions/run-integration \
  --submission-id=123 \
  --integration=mailchimp
```

Pass comma-separated submission IDs to process multiple submissions:

```bash
php craft formie/submissions/run-integration \
  --submission-id=123,124,125 \
  --integration=mailchimp
```

The handle matches the integration handle in **Formie → Integrations**.

## Troubleshooting failed integrations

When a re-run fails:

1. Check **Craft → Utilities → Logs** for Formie integration errors.
2. Open the failed queue job under **Utilities → Queue Manager** if the original run was queued — payload details are included for debugging.
3. Verify credentials under **Formie → Integrations** — reconnect OAuth integrations if tokens expired.
4. Confirm mapping on the form’s Integrations tab matches what the provider expects.
5. Test with a fresh front-end submission after fixing the root cause.

For dispatch order issues — for example, an automation running before a User integration creates an account — see [Integration dispatch and policies](/guides/integrations/integration-dispatch-and-policies).

## Sent Notifications vs integration re-runs

These solve different problems:

| Problem | Tool |
| --- | --- |
| CRM / automation / marketing API did not receive data | Submission sidebar **Integrations** menu or console command |
| Email notification was not sent or needs resending | **Formie → Sent Notifications** |

A submission can succeed while an individual integration fails, or vice versa. Check the appropriate log or screen for the failure type you are investigating.

## Related

- [Sent Notifications](/forms/sent-notifications)
- [Submission Workflow](/developers/submission-workflow)
- [Integration dispatch and policies](/guides/integrations/integration-dispatch-and-policies)
- [Configuration](/get-started/configuration)
