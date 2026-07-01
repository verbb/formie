# Console Commands
Formie comes with a number of command line utilities that can be run on-demand, or on a set schedule.

## Forms

### Re-save Forms
Refer to the [Craft docs](https://craftcms.com/docs/5.x/reference/cli#resave) on available options.

```shell
./craft resave/formie-forms --update-search-index=1
```

### Delete Forms
You can bulk delete forms with this command.

Option | Description
--- | ---
`--form-handle` | The form handle(s) to delete. Can be set to multiple comma-separated handles.
`--form-id` | The form ID(s) to delete. Can be set to multiple comma-separated IDs.

```shell
./craft formie/forms/delete --form-handle=form1,anotherForm
```

## Import/Export

### List Forms
Lists all available Formie forms that can be exported or imported.

Option | Description
--- | ---
`folderPath` | Optional path to look for JSON files. Defaults to the plugin's export folder.

```shell
./craft formie/forms/list
```

### Export Forms
Export Formie forms as JSON files. Requires form IDs or handles as a comma-separated list.

```shell
./craft formie/forms/export 1,contact-form,newsletter
```

### Import Form
Import a Formie form from a JSON file.

Option | Description
--- | ---
`fileLocation` | Path to a JSON file to import. Can be relative to the plugin's export folder or an absolute path.
`--create` | Whether to create a new form instead of updating an existing one. Default is false.

```shell
./craft formie/forms/import formie-contact-form.json
```

### Import All Forms
Import all Formie form JSON files from a folder.

Option | Description
--- | ---
`folderPath` | Optional path to look for JSON files. Defaults to the plugin's export folder.
`--create` | Whether to create new forms instead of updating existing ones. Default is false.

```shell
./craft formie/forms/import-all
```

## Submissions

### Re-save Submissions
Refer to the [Craft docs](https://craftcms.com/docs/5.x/reference/cli#resave) on available options.

```shell
./craft resave/formie-submissions --form-id=1234 --update-search-index=1
```

### Run Integrations
For a provided submission, run the provided integration.

Option | Description
--- | ---
`--submission-id` | The submission ID(s) to use data for. Can be set to multiple comma-separated IDs.
`--integration` | The handle of the integration to trigger.

```shell
./craft formie/submissions/run-integration --submission-id=12345 --integration=mailchimp
```

### Send Email Notification
For a provided submission, send the provided notification.

Option | Description
--- | ---
`--submission-id` | The submission ID(s) to use data for. Can be set to multiple comma-separated IDs.
`--notification-id` | The ID of the notification to trigger.

```shell
./craft formie/submissions/send-notification --submission-id=12345 --notification-id=12
```

## Cron

### Run Scheduled Tasks

Runs Formie tasks that should be scheduled on cron: cleanup/retention and due scheduled reports.

Schedule this command on production sites — for example, hourly:

```shell
./craft formie/cron/run
```

Option | Description
--- | ---
`--skip-gc` | Skip cleanup and retention tasks.
`--skip-reports` | Skip scheduled report delivery.
`--only` | Comma-separated task groups to run: `gc`, `reports`.

Use `--only` or the skip flags when you want separate cron schedules — for example, daily cleanup and hourly reports:

```shell
# Daily cleanup at 3am
0 3 * * * /path/to/craft formie/cron/run --only=gc

# Hourly scheduled reports
0 * * * * /path/to/craft formie/cron/run --only=reports
```

Craft's [garbage collection](https://craftcms.com/docs/5.x/system/gc.html) still runs Formie cleanup as a best-effort fallback on web requests, but production sites should not rely on it.

## Reports

### Run Scheduled Reports

Sends any enabled scheduled reports that are due. This is included in `./craft formie/cron/run`, or you can schedule it separately — for example, every hour — so [scheduled report](/reports/scheduled-reports) deliveries run automatically.

```shell
./craft formie/reports/run-scheduled
```


## Cleanup

### Run All Cleanup Tasks

Runs every Formie cleanup and retention task. This is included in `./craft formie/cron/run`, or you can schedule it separately — for example, daily:

```shell
./craft formie/gc/run
```

Option | Description
--- | ---
`--only` | Comma-separated cleanup task handles. Omit to run all tasks. Handles: `incomplete-submissions`, `data-retention-submissions`, `sent-notifications`, `file-upload-asset-retention`, `stale-pending-uploads`, `report-exports`, `submission-states`, `draft-storage`.

### Prune Incomplete Submissions
Deletes any incomplete submissions that exceed the "Maximum Incomplete Submission Age" plugin setting.

```shell
./craft formie/gc/prune-incomplete-submissions
```

### Prune Data Retention Submissions
Deletes any submissions that exceed your data retention form settings.

```shell
./craft formie/gc/prune-data-retention-submissions
```

### Prune Sent Notifications
Deletes sent notifications that exceed the plugin's maximum age setting.

```shell
./craft formie/gc/prune-sent-notifications
```

### Prune Submission States
Deletes stale submission draft state records.

```shell
./craft formie/gc/prune-submission-states
```

### Prune Draft Storage
Deletes expired submission draft storage rows.

```shell
./craft formie/gc/prune-draft-storage
```

### Prune File Upload Asset Retention
Deletes uploaded assets that exceed a File Upload field's asset retention setting while keeping the submission record.

```shell
./craft formie/gc/prune-file-upload-asset-retention
```

### Prune Stale Pending Uploads
Deletes unfinalized staged File Upload assets that exceed the plugin's maximum incomplete submission age.

```shell
./craft formie/gc/prune-stale-pending-uploads
```

### Prune Report Exports
Deletes expired report export files.

```shell
./craft formie/gc/prune-report-exports
```

## Delete Submissions
You can bulk delete submissions with this command.

Option | Description
--- | ---
`--form-handle` | The form handle(s) to delete submissions from. Can be set to multiple comma-separated handles.
`--form-id` | The form ID(s) to delete submissions from. Can be set to multiple comma-separated IDs.
`--incomplete-only` | Whether to delete only incomplete submissions.
`--spam-only` | Whether to delete only spam submissions.
`--before` | Delete submissions created before a date or relative date string.
`--after` | Delete submissions created after a date or relative date string.

```shell
./craft formie/submissions/delete --form-handle=form1,anotherForm
```

## Delete Sent Notifications
You can bulk delete sent notifications with this command.

Option | Description
--- | ---
`--form-handle` | The form handle(s) to delete sent notifications for. Can be set to multiple comma-separated handles.
`--form-id` | The form ID(s) to delete sent notifications for. Can be set to multiple comma-separated IDs.
`--all` | Delete sent notifications for all forms.
`--hard-delete` | Permanently delete sent notifications instead of soft deleting them.

```shell
./craft formie/sent-notifications/delete --form-handle=form1,anotherForm
```

## Migration
You can run the migrations from either Sprout Forms or Freeform via the command line. This is useful if you have a large number of submissions or complex forms to migrate.

### Migrate Sprout Forms

Option | Description
--- | ---
`--form-handle` | The Sprout Forms handle(s) to migrate. Can be set to multiple comma-separated handles. Omit to migrate all.

```shell
./craft formie/migrate/sprout-forms --form-handle=form1,anotherForm
```

### Migrate Freeform

Option | Description
--- | ---
`--form-handle` | The Freeform form handle(s) to migrate. Can be set to multiple comma-separated handles. Omit to migrate all.

```shell
./craft formie/migrate/freeform4 --form-handle=form1,anotherForm
```

```shell
./craft formie/migrate/freeform5 --form-handle=form1,anotherForm
```
