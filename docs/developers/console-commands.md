# Console Commands

Formie includes console commands you can run from your Craft project’s directory.

## Forms

::: reference
### `resave/formie-forms`

Resaves Formie forms through Craft’s `resave` command. It takes Craft’s usual [resave options](https://craftcms.com/docs/5.x/reference/cli#resave), such as `--update-search-index` and `--queue`.

```shell
php craft resave/formie-forms --update-search-index=1
```
:::

::: reference
### `formie/forms/delete`

Deletes forms by ID or handle. The forms go to the trash, where they can be restored until Craft’s garbage collection purges them. Pass `--form-id` or `--form-handle`. When you pass both, only `--form-handle` is used.

| Option | Description | Default |
| --- | --- | --- |
| `--form-id` | A comma-separated list of form IDs to delete. | |
| `--form-handle` | A comma-separated list of form handles to delete. | |

```shell
php craft formie/forms/delete --form-handle=contactForm,newsletter
php craft formie/forms/delete --form-id=12
```
:::

## Import & Export

These commands export forms to JSON files and import them again, such as to copy a form between environments. Exported files are written to the [`defaultExportFolder`](../get-started/configuration.md#defaultexportfolder), which is `storage/formie-export` unless you change it.

::: reference
### `formie/forms/list`

Lists the JSON files in the export folder, then every existing form with its ID, handle and title. Pass another folder as the argument to list the JSON files there instead.

```shell
php craft formie/forms/list
php craft formie/forms/list ./form-exports
```
:::

::: reference
### `formie/forms/export`

Exports forms to the export folder, one `formie-[handle].json` file per form, replacing a file with the same name. Pass a comma-separated list of form IDs or handles as the argument.

```shell
php craft formie/forms/export contactForm
php craft formie/forms/export 1,contactForm,newsletter
```
:::

::: reference
### `formie/forms/import`

Imports a form from a JSON file. Pass the file’s path as the argument. A path that doesn’t exist is also looked for in the export folder, so a file name from `list` is enough. By default the import updates the existing form with the same handle, and creates a new form when there isn’t one.

| Option | Description | Default |
| --- | --- | --- |
| `--create` | Always creates a new form instead of updating the form with the same handle. | `false` |

```shell
php craft formie/forms/import formie-contactForm.json
php craft formie/forms/import ./form-exports/formie-contactForm.json --create
```
:::

::: reference
### `formie/forms/import-all`

Imports every JSON file in the export folder, or in the folder you pass as the argument. Each file is imported by its own queue job, so keep a queue worker running or run `php craft queue/run` afterwards. Like `import`, each job updates the form with the same handle unless you pass `--create`.

| Option | Description | Default |
| --- | --- | --- |
| `--create` | Always creates new forms instead of updating the forms with the same handles. | `false` |

```shell
php craft formie/forms/import-all
php craft formie/forms/import-all ./form-exports --create
```
:::

## Submissions

::: reference
### `resave/formie-submissions`

Resaves Formie submissions through Craft’s `resave` command, one form at a time. It also takes Craft’s usual [resave options](https://craftcms.com/docs/5.x/reference/cli#resave). Pass `--update-search-index` when you need submission search keywords rebuilt, because resaving alone doesn’t refresh them.

| Option | Description | Default |
| --- | --- | --- |
| `--form-id` | A comma-separated list of form IDs whose submissions to resave. Leave it out to resave submissions for every form. | |
| `--update-title` | Regenerates each submission’s title from its form’s Submission Title Format. | |
| `--update-search-index` | Updates the search index while resaving. | `false` |

```shell
php craft resave/formie-submissions --form-id=1234 --update-search-index=1
php craft resave/formie-submissions --update-title=1
```
:::

::: reference
### `formie/submissions/delete`

Deletes the submissions of one or more forms, including incomplete and spam submissions unless you narrow it down. The submissions go to the trash, where they can be restored until Craft’s garbage collection purges them. Pass `--form-id` or `--form-handle`. When you pass both, only `--form-handle` is used.

| Option | Description | Default |
| --- | --- | --- |
| `--form-id` | A comma-separated list of form IDs whose submissions to delete. | |
| `--form-handle` | A comma-separated list of form handles whose submissions to delete. | |
| `--incomplete-only` | Deletes only incomplete submissions. | `false` |
| `--spam-only` | Deletes only spam submissions. | `false` |
| `--before` | Deletes only submissions created before this date. Accepts a date or a relative date such as `-30 days`. | |
| `--after` | Deletes only submissions created after this date. Accepts a date or a relative date such as `-30 days`. | |

```shell
php craft formie/submissions/delete --form-handle=contactForm,newsletter
php craft formie/submissions/delete --form-handle=contactForm --spam-only --before="-30 days"
```
:::

::: reference
### `formie/submissions/run-integration`

Sends one or more submissions to an integration again, using the integration settings saved on each submission’s form. Only integrations that send data, such as CRM or email marketing integrations, can be run this way. The command reports each submission that failed and exits with an error when any did.

| Option | Description | Default |
| --- | --- | --- |
| `--submission-id` | A comma-separated list of submission IDs to send. | |
| `--integration` | The handle of the integration to run. | |

```shell
php craft formie/submissions/run-integration --submission-id=12345 --integration=mailchimp
```
:::

::: reference
### `formie/submissions/send-notification`

Sends an email notification for one or more submissions, such as to resend a notification that failed. The command reports each submission that failed and exits with an error when any did.

| Option | Description | Default |
| --- | --- | --- |
| `--submission-id` | A comma-separated list of submission IDs to send the notification for. | |
| `--notification-id` | The ID of the notification to send. | |

```shell
php craft formie/submissions/send-notification --submission-id=12345,12346 --notification-id=12
```
:::

## Sent Notifications

::: reference
### `formie/sent-notifications/delete`

Deletes the sent notifications of one or more forms. By default they go to the trash. Pass `--form-id`, `--form-handle` or `--all`.

| Option | Description | Default |
| --- | --- | --- |
| `--form-id` | A comma-separated list of form IDs whose sent notifications to delete. | |
| `--form-handle` | A comma-separated list of form handles whose sent notifications to delete. | |
| `--all` | Deletes sent notifications for every form. | `false` |
| `--hard-delete` | Deletes the sent notifications permanently instead of moving them to the trash. | `false` |

```shell
php craft formie/sent-notifications/delete --form-handle=contactForm
php craft formie/sent-notifications/delete --all --hard-delete
```
:::

## Submission Content Encryption

These commands set up the key for encrypted submission content, move existing content to it and rotate it. [Content Encryption](/submissions/content-encryption) explains the setup, encryption boundaries, environments, locked submissions, recovery and safe key rotation.

The usual order is to generate a key, deploy the same key and Formie version to every web and queue process that reads encrypted submissions, restart long-running workers, activate the key, then re-encrypt existing content after a verified database backup.

::: reference
### `formie/encryption/generate-key`

Generates a field-content encryption key and saves it as `FORMIE_ENCRYPTION_KEY` in the project’s `.env` file, then prints the key and its ID. When the `.env` file doesn’t exist, the command asks before creating it. The change applies only to that installation, so keep a recoverable copy of the key and deploy the same value to every environment that must read encrypted submissions.

The command refuses to replace a configured key unless you pass `--rotate`. Rotating replaces `FORMIE_ENCRYPTION_KEY` in `.env`, so copy the current key to `FORMIE_ENCRYPTION_PREVIOUS_KEYS` first. Generating a key doesn’t activate it or change stored content.

| Option | Description | Default |
| --- | --- | --- |
| `--stdout` | Prints only the new key without changing any file, for when a deployment system, container platform or secrets manager owns the environment. | `false` |
| `--rotate` | Generates a replacement key even though one is already configured. | `false` |

```shell
php craft formie/encryption/generate-key
php craft formie/encryption/generate-key --stdout
php craft formie/encryption/generate-key --rotate
```
:::

::: reference
### `formie/encryption/verify-key`

Prints the ID of a key you pass as the argument, without configuring it or printing the key itself. Use it to check which key a saved value is, such as a key from a password manager, against the ID `status` reports.

```shell
php craft formie/encryption/verify-key "the-saved-key"
```
:::

::: reference
### `formie/encryption/status`

Shows the active write format, the configured key ID, the key ID Formie expects and how many stored values use each encryption format. It doesn’t change anything, so you can run it at any time.

```shell
php craft formie/encryption/status
```
:::

::: reference
### `formie/encryption/activate`

Starts encrypting new values with the configured key. Run it only after every web and queue process has the same key and the updated Formie code. When the configured key differs from the one Formie expects, the command stops and points you to `reencrypt --adopt-new-key` instead.

```shell
php craft formie/encryption/activate
```
:::

::: reference
### `formie/encryption/reencrypt`

Moves readable stored values to the current key, including values encrypted with a previous key. Make and verify a database backup first. The command works through submissions in batches and resumes from its last completed batch after an interruption. It doesn’t fire submission save events, and it skips a submission that was edited while it ran. It never replaces content it can’t decrypt. It lists those submissions instead, and you can run it again once the missing key is in `FORMIE_ENCRYPTION_PREVIOUS_KEYS`.

To confirm the target key, pass the key ID from `status` with `--key-id`, or type it when prompted. When rotating a key, keep the former key in `FORMIE_ENCRYPTION_PREVIOUS_KEYS` and add `--adopt-new-key`.

| Option | Description | Default |
| --- | --- | --- |
| `--confirm-backup` | Confirms you’ve made and verified a database backup. The command doesn’t run without it. | `false` |
| `--key-id` | The ID of the configured key, to confirm the target key without a prompt. | |
| `--adopt-new-key` | Accepts a configured key that differs from the key Formie expects, when you’re rotating the key. | `false` |
| `--batch-size` | The number of submissions to process in each batch, from 1 to 1000. | `250` |
| `--restart` | Starts again from the first submission instead of resuming from the last completed batch. | `false` |

```shell
php craft formie/encryption/reencrypt --confirm-backup --key-id=r_123456789abc
php craft formie/encryption/reencrypt --confirm-backup --key-id=r_123456789abc --adopt-new-key
```
:::

## Cron

::: reference
### `formie/cron/run`

Runs the Formie tasks that should be scheduled on cron, which are payment webhook recovery, cleanup and retention, and due scheduled reports. Schedule it on production sites, such as hourly. Craft’s [garbage collection](https://craftcms.com/docs/5.x/system/gc.html) still runs Formie cleanup on web requests as a fallback, but production sites shouldn’t rely on it.

| Option | Description | Default |
| --- | --- | --- |
| `--skip-gc` | Skips cleanup and retention tasks. | `false` |
| `--skip-reports` | Skips scheduled report delivery. | `false` |
| `--skip-deliveries` | Skips payment webhook recovery. | `false` |
| `--only` | A comma-separated list of task groups to run, from `gc`, `reports` and `deliveries`. It overrides the skip options. | |

```shell
php craft formie/cron/run
php craft formie/cron/run --only=reports
```

Use `--only` or the skip options for separate schedules, such as daily cleanup and hourly reports.

```shell
# Daily cleanup at 3am
0 3 * * * /path/to/project/craft formie/cron/run --only=gc

# Hourly scheduled reports
0 * * * * /path/to/project/craft formie/cron/run --only=reports
```
:::

## Reports

::: reference
### `formie/reports/run-scheduled`

Sends the enabled [scheduled reports](/reports/scheduled-reports) that are due. `formie/cron/run` already includes it, or you can schedule it on its own, such as every hour. The command exits with an error when any report fails to send.

```shell
php craft formie/reports/run-scheduled
```
:::

## Cleanup

These commands delete data that has passed its retention settings. `formie/cron/run` already runs all of them, or you can schedule `formie/gc/run` on its own, such as daily. The pruning commands run a single task, which helps when you want to clear one kind of data straight away.

::: reference
### `formie/gc/run`

Runs every Formie cleanup and retention task, or the tasks you choose with `--only`.

| Option | Description | Default |
| --- | --- | --- |
| `--only` | A comma-separated list of cleanup tasks to run. Leave it out to run every task. The tasks are `payment-capabilities`, `submission-operations`, `incomplete-submissions`, `data-retention-submissions`, `sent-notifications`, `file-upload-asset-retention`, `trashed-submission-files`, `stale-pending-uploads`, `report-exports`, `submission-grants` and `submission-progress`. | |

```shell
php craft formie/gc/run
php craft formie/gc/run --only=incomplete-submissions,sent-notifications
```
:::

::: reference
### `formie/gc/prune-incomplete-submissions`

Permanently deletes incomplete submissions that haven’t been updated within [`maxIncompleteSubmissionAge`](../get-started/configuration.md#maxincompletesubmissionage) days. When [Spam Protection](/forms/spam-protection) saves spam submissions, it also permanently deletes the oldest ones beyond the spam limit. It does nothing when `maxIncompleteSubmissionAge` is `0`.

```shell
php craft formie/gc/prune-incomplete-submissions
```
:::

::: reference
### `formie/gc/prune-data-retention-submissions`

Permanently deletes submissions older than their form’s data retention setting, including incomplete and spam submissions. Forms that keep submissions forever are skipped.

```shell
php craft formie/gc/prune-data-retention-submissions
```
:::

::: reference
### `formie/gc/prune-sent-notifications`

Permanently deletes sent notifications that haven’t been updated within [`maxSentNotificationsAge`](../get-started/configuration.md#maxsentnotificationsage) days.

```shell
php craft formie/gc/prune-sent-notifications
```
:::

::: reference
### `formie/gc/prune-file-upload-asset-retention`

Deletes uploaded assets that are older than their File Upload field’s asset retention setting, while keeping the submissions.

```shell
php craft formie/gc/prune-file-upload-asset-retention
```
:::

::: reference
### `formie/gc/prune-stale-pending-uploads`

Deletes File Upload assets that were uploaded for a submission that was never finished, once they pass the maximum incomplete submission age.

```shell
php craft formie/gc/prune-stale-pending-uploads
```
:::

::: reference
### `formie/gc/prune-report-exports`

Deletes report export files that have expired.

```shell
php craft formie/gc/prune-report-exports
```
:::

::: reference
### `formie/gc/prune-submission-grants`

Deletes expired submission grants, which give a visitor temporary access to their own submission.

```shell
php craft formie/gc/prune-submission-grants
```
:::

::: reference
### `formie/gc/prune-submission-progress`

Deletes saved submission progress that has expired.

```shell
php craft formie/gc/prune-submission-progress
```
:::

## Recover Payments

These commands help when a payment is still unresolved after a timeout or an interrupted request. A pending payment may already have charged the customer, so Formie keeps its saved amount, gateway account and references, and retrying the submission can’t quietly create another purchase. Moneris, Eway, BPOINT, Opayo, Mollie and Paddle use this recovery flow.

The usual order is to list unresolved payments, inspect one, try `reconcile`, record a verified outcome with `resolve` when the gateway can’t be checked automatically, then `resume` a successful submission. A timeout or an empty search result alone isn’t evidence that a payment failed.

::: reference
### `formie/payments/index`

Lists unresolved payments, meaning those whose status is unknown, pending, processing or waiting on customer action, with one line of JSON for each. It never shows card data or gateway credentials. This is also the default action, so `php craft formie/payments` runs it. For the next page of results, pass the last payment ID you saw and a limit of up to 500.

```shell
php craft formie/payments
php craft formie/payments/index 123 100
```
:::

::: reference
### `formie/payments/inspect`

Shows one payment in detail, including its submission and integration IDs, amount, currency, gateway reference and merchant reference. Pass the local payment ID as the argument.

```shell
php craft formie/payments/inspect 123
```
:::

::: reference
### `formie/payments/reconcile`

Looks up a payment’s status with the gateway and updates it when the gateway gives a verified result. Try it before making a manual decision. Eway can also find a payment by its unique invoice reference when the creation response was lost, and Mollie can restore a missing reference from a webhook after verifying its transaction, saved owner and amount. A missing or unverified result keeps the payment unresolved.

When the gateway has no automatic lookup, use the merchant reference to find the transaction in the original gateway account. Check the amount, currency and final status, and for a hosted checkout confirm it has completed or been cancelled before deciding whether the customer can retry.

```shell
php craft formie/payments/reconcile 123
```
:::

::: reference
### `formie/payments/resolve`

Records an outcome you’ve verified yourself in the gateway. Pass the payment ID, the outcome (`success` or `failed`), the amount, the currency, the gateway reference and a note saying who checked it and what evidence they used. The command checks the amount and currency against the saved payment, rejects a conflicting reference and asks you to confirm. It doesn’t create a charge or resume the submission.

Record `failed` only when the gateway confirms no charge occurred and any open checkout or authorisation can no longer complete. Use the saved gateway reference if the payment already has one, or `""` when it doesn’t. Earlier attempts may have no saved gateway account, so verify the original account as well as the transaction, and include that in your note. The command doesn’t infer the account from the integration’s current credentials.

| Option | Description | Default |
| --- | --- | --- |
| `--confirmed` | Skips the confirmation prompt, for automation that has already made the same checks. Without it, a non-interactive run stops without saving. | `false` |

```shell
php craft formie/payments/resolve 123 success 25.00 USD "gateway-reference" "Checked by Alex in the gateway dashboard. Amount and receipt match."
php craft formie/payments/resolve 123 failed 25.00 USD "" "Checked by Alex. Gateway confirms no charge and no open checkout."
```
:::

::: reference
### `formie/payments/resume`

Continues a submission’s remaining processing after its payment is verified as successful, which can send the form’s notifications and run its integrations. Formie reuses the saved workflow state so completed delivery steps aren’t repeated. If processing fails, the payment stays successful, so fix the reported error before running the command again.

The saved payment must match the submission’s current amount and currency. If the submission or payment settings changed while checkout was pending, review the difference first. A mismatch keeps the submission incomplete without creating another charge.

```shell
php craft formie/payments/resume 123
```
:::

::: reference
### `formie/payments/verify-account`

Records that a payment or subscription belongs to the merchant account the integration connects to. Status lookups and cancellation stay blocked for a payment or subscription without a verifiable account until you verify it. Find the original provider resource yourself first, and check the integration connects to that same account. Pass `payment` or `subscription`, its ID and a note saying how you verified the account.

The command records the account and your note only. It can’t replace an existing account, change the amount, settle the payment or send a request to the provider.

| Option | Description | Default |
| --- | --- | --- |
| `--confirmed` | Skips the confirmation prompt. Without it, a non-interactive run stops without saving. | `false` |

```shell
php craft formie/payments/verify-account payment 123 "Matched the original charge in the merchant dashboard." --confirmed
php craft formie/payments/verify-account subscription 45 "Matched the subscription in the merchant dashboard."
```
:::

::: reference
### `formie/payments/receipts`

Lists payment webhook receipts after the ID you pass, up to the limit, with one line of JSON for each. Each line includes the receipt’s state history, attempts, errors safe to show and a summary with sensitive values removed. It never decrypts the provider’s raw payload.

Receipts move through verified, scheduled, processing and processed states. A receipt that fails is retried with increasing delays, and becomes failed once its automatic attempts run out. Retries reuse the same receipt, and a completed receipt never runs its side effects again.

```shell
php craft formie/payments/receipts 0 100
```
:::

::: reference
### `formie/payments/evidence`

Prints the exact decrypted body, verification headers and normalised payload of one webhook receipt. Run it only in a trusted terminal, and treat the output as private provider and customer data.

```shell
php craft formie/payments/evidence 123
```
:::

::: reference
### `formie/payments/recover-webhooks`

Schedules verified webhook receipts that are due or were interrupted, such as when publishing to the queue failed or a worker stopped. Pass the number to schedule, up to 500. `formie/cron/run` already includes it.

A processed receipt may still need its payment and submission reconciled after an interrupted completion, so inspect the payment’s history before resuming it.

```shell
php craft formie/payments/recover-webhooks
php craft formie/payments/recover-webhooks 200
```
:::

## Upload References

Formie keeps an index of File Upload values so it can tell whether a file is still used. After an upgrade, existing submissions are indexed by the queue, and new and changed submissions update the index as they’re saved. Files stay protected while indexing is incomplete or when encrypted content can’t be read.

Before deploying the update, let in-flight requests finish and stop old queue workers. Apply migrations, then restart web and queue processes with the updated code before resuming traffic. Keep the same encryption keys available to web and queue processes.

::: reference
### `formie/upload-references/status`

Lists each form’s indexing progress and how many of its submissions couldn’t be read. It doesn’t print submission answers.

```shell
php craft formie/upload-references/status
```
:::

::: reference
### `formie/upload-references/resume`

Continues pending indexing from its saved position, then runs `verify`. Pass a form ID to limit it to that form.

```shell
php craft formie/upload-references/resume
php craft formie/upload-references/resume 123
```
:::

::: reference
### `formie/upload-references/verify`

Compares stored submission values with the index, and exits with an error when it finds a mismatch or content it can’t read. Pass a form ID to limit it to that form.

```shell
php craft formie/upload-references/verify
php craft formie/upload-references/verify 123
```
:::

::: reference
### `formie/upload-references/rebuild`

Rebuilds the index, then runs `verify`. Run it after restoring an encryption key or changing submission content outside Formie’s save APIs. Existing references stay in place while each submission is reconciled, so the index is never cleared first. Pass a form ID to limit it to that form.

```shell
php craft formie/upload-references/rebuild
php craft formie/upload-references/rebuild 123
```
:::

## Repairs

::: reference
### `formie/repair/emoji-shortcodes`

Converts emoji shortcodes, such as `:smile:`, to Unicode emoji in form, page, field, notification, integration and template settings. Before changing anything, it checks that every affected column can store 4-byte characters. When one can’t, it lists those columns and stops, so run Craft’s `db/convert-charset` command first. Values with invalid JSON are skipped and listed.

| Option | Description | Default |
| --- | --- | --- |
| `--dry-run` | Reports how many values would change without saving anything. | `false` |

```shell
php craft formie/repair/emoji-shortcodes --dry-run
php craft formie/repair/emoji-shortcodes
```
:::

## Migration

These commands migrate forms from Sprout Forms or Freeform, which helps when you have many submissions or complex forms. The other plugin must still be installed.

::: reference
### `formie/migrate/sprout-forms`

Migrates Sprout Forms forms, with their submissions and notifications.

| Option | Description | Default |
| --- | --- | --- |
| `--form-handle` | A comma-separated list of Sprout Forms form handles to migrate. Leave it out to migrate every form. | |

```shell
php craft formie/migrate/sprout-forms
php craft formie/migrate/sprout-forms --form-handle=contactForm,newsletter
```
:::

::: reference
### `formie/migrate/freeform4`

Migrates Freeform 4 forms, with their submissions and notifications. Submissions are migrated in batches, and the submission options let you split a large migration into several runs.

| Option | Description | Default |
| --- | --- | --- |
| `--form-handle` | A comma-separated list of Freeform form handles to migrate. Leave it out to migrate every form. | |
| `--batch-size` | The number of submissions to migrate in each batch. | `100` |
| `--submission-offset` | The number of submissions to skip before migrating, to continue an earlier run. | `0` |
| `--submission-limit` | The most submissions to migrate for each form. Leave it out to migrate them all. | |
| `--skip-submissions` | Migrates the forms and notifications without their submissions. | `false` |
| `--submissions-only` | Migrates only submissions, into Formie forms that were already migrated with the same handles. | `false` |
| `--verbose-submission-logs` | Shows a line for every migrated submission. | `false` |

```shell
php craft formie/migrate/freeform4 --form-handle=contactForm
php craft formie/migrate/freeform4 --form-handle=contactForm --skip-submissions
php craft formie/migrate/freeform4 --form-handle=contactForm --submissions-only --submission-offset=5000 --submission-limit=5000
```
:::

::: reference
### `formie/migrate/freeform5`

Migrates Freeform 5 forms, with their submissions and notifications. It takes the same options as `formie/migrate/freeform4`.

```shell
php craft formie/migrate/freeform5 --form-handle=contactForm,newsletter
php craft formie/migrate/freeform5 --batch-size=500 --verbose-submission-logs
```
:::
