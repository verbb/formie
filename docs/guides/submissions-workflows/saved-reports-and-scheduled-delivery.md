# Saved reports and scheduled delivery

Reports are saved analytical views over submissions. When you need the same export every week — not a one-off download from the submissions index — save a report and optionally attach scheduled email delivery.

This guide is a **getting-started walkthrough**. For export formats, large-export behaviour, permissions, project config, cron details, incremental windows, and troubleshooting, see [Reports overview](/reports/overview) and [Scheduled reports](/reports/scheduled-reports).

## Prerequisites

- [Reports overview](/reports/overview)
- [Scheduled reports](/reports/scheduled-reports)
- Submissions permission to access reports

## When to use a report

Use the **submissions index** for day-to-day review, editing, and status changes. Use a **saved report** when you need a reusable filter set, on-demand export in multiple formats, or recurring email delivery. See [Reports vs the submissions index](/reports/overview#reports-vs-the-submissions-index).

## Step 1: Create and run a saved report

1. Go to **Formie → Reports** → **New Report**.
2. Configure **General** (name, handle, forms), **Filters**, **Columns**, **Display**, and **Export** — see [What a report includes](/reports/overview#what-a-report-includes).
3. Save and run the report. Confirm the summary, chart, and table match what you expect.
4. Use **Export** for a one-off download. Pick the format your team needs — [Export formats](/reports/overview#export-formats).

If the export is large, Formie queues it in the background — see [Large exports](/reports/overview#large-exports).

## Step 2: Schedule email delivery

Scheduled reports email a summary plus an export attachment on a daily or weekly cadence.

1. Ensure server cron runs `./craft formie/cron/run` — typically hourly. See [Before you start: cron](/reports/scheduled-reports#before-you-start-cron).
2. Create and save a report first, then open **Settings → Scheduled Reports** (or the report's **Scheduled** tab) and choose **New Scheduled Report**.
3. Link the report, set **Schedule** (frequency, day, hour), and **Delivery** (file type, recipients, optional subject/message). See [Creating a scheduled report](/reports/scheduled-reports#creating-a-scheduled-report).
4. **Save**, then use **Send Test Email** to verify delivery without waiting for cron.

After the first successful delivery, attachments use an **incremental window** — submissions since the previous send. See [Incremental export windows](/reports/scheduled-reports#incremental-export-windows).

## Checklist before go-live

- Cron job registered and `./craft formie/cron/run` runs without errors
- Test email received with expected summary and attachment
- Recipients have **Access reports** or relevant export permissions — see [Permissions](/reports/overview#permissions)
- Report and schedule settings deployed via project config to staging/production

## Troubleshooting

Work through [Scheduled reports — Troubleshooting](/reports/scheduled-reports#troubleshooting) for empty attachments, cron vs test-email mismatches, and attachment size limits.

## Related

- [Reports overview](/reports/overview)
- [Scheduled reports](/reports/scheduled-reports)
- [Console commands — Reports](/developers/console-commands#reports)
