# Scheduled Reports

Scheduled reports email a saved report on a daily or weekly cadence. Each delivery sends:

- a **summary email** with submission counts (and per-form breakdown when multiple forms match)
- an **export attachment** in the file type you choose, using the report’s filters, columns, and export filename setting

Configure schedules under **Formie → Settings → Scheduled Reports**, or from a report's **Scheduled** tab (**Formie → Reports → {report} → Edit → Scheduled**).

::: tip
For a getting-started walkthrough, see [Saved reports and scheduled delivery](/guides/submissions-workflows/saved-reports-and-scheduled-delivery).
:::

## Before you start: cron

Deliveries do **not** send by themselves. They run when a console command executes on a server cron schedule — typically every hour:

```shell
./craft formie/cron/run
```

You can also run only scheduled reports:

```shell
./craft formie/reports/run-scheduled
```

Or run cleanup and reports on separate schedules with `--only=reports` or `--only=gc`. See [Console commands — Cron](/developers/console-commands#cron).

If no cron command is scheduled, enabled scheduled reports will never send. Use **Send Test Email** on an existing scheduled report to verify delivery without waiting for cron.

## Creating a scheduled report

1. Create and save a [report](/reports/overview) with the filters and columns you want.
2. Open **Settings → Scheduled Reports** (or the report’s **Scheduled** tab) and choose **New Scheduled Report**.
3. Set **Name**, **Report**, and **Enabled**.
4. Under **Schedule**, choose **Frequency** (daily or weekly), **Day of Week** (weekly only), **Hour** (0–23, control panel timezone), and optional **Start Date** / **End Date**.
5. Under **Delivery**, set **File Type**, **Recipients** (comma- or line-separated addresses), optional **Recipient User Group**, **Email Subject**, **Email Message**, and optional **Email Template**.

### File type

**File Type** controls the attached export format:

| File type | Extension |
| --- | --- |
| **Excel** | `.xlsx` |
| **CSV** | `.csv` |
| **JSON** | `.json` |
| **XML** | `.xml` |
| **Text** | `.txt` |

These match the formats available from the report run screen **Export** menu. The file extension is added automatically from **File Type**; the **Export Filename** on the linked report controls the basename only.

Leave **Email Subject** blank to use the report name. Leave **Email Message** blank to send only the summary table and attachment. Leave **Email Template** blank to use Formie’s default email wrapper — the same behaviour as notification emails.

## Incremental export windows

After the first successful delivery, the attachment includes submissions created **since the previous delivery** (`lastSentAt`), while still respecting the report’s saved filters.

The email summary counts use the same incremental window. The first delivery includes all submissions matching the report filters.

Test sends use the full current report filters and do **not** update `lastSentAt`.

## Save before testing

Actions in the save menu (**Send Test Email**, **Delete**) use the scheduled report as stored in the database. Save your changes before sending a test email if you have edited recipients, schedule, file type, or message fields.

## Permissions

Users need **Manage scheduled reports** to create, edit, delete, or test scheduled delivery. Viewing the **Scheduled** tab on a report requires the same permission.

## Troubleshooting

### Nothing sends on schedule

Confirm `./craft formie/cron/run` (or `./craft formie/reports/run-scheduled`) is on cron and that the scheduled report is **Enabled**, within any start/end dates, and due for the current hour/day.

Run the command manually and check the console output for errors.

### Test email works but cron does not

Usually the cron job is missing, pointed at the wrong PHP binary, or not running frequently enough. Weekly schedules only send on the chosen weekday after the configured hour.

### Attachment is empty

If the incremental window has no new submissions since the last delivery, the attachment may contain headers only (or an empty structure for JSON/XML). Run a test send or check `lastSentAt` on the scheduled report record.

Large attachments may be omitted when they exceed the plugin’s maximum email attachment size. Check Craft logs for a warning.

## Related reading

- [Reports overview](/reports/overview)
- [Console commands](/developers/console-commands#reports)
- [Email notifications](/forms/email-notifications)
