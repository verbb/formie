# Saved Reports and Scheduled Delivery

Reports are saved analytical views over submissions. When you need the same export every week — not a one-off download from the submissions index — save a report and optionally attach scheduled email delivery.

This guide is a **getting-started walkthrough**. For export formats, large-export behaviour, permissions, project config, cron details, incremental windows, and troubleshooting, see [Reports overview](/reports/reports) and [Scheduled reports](/reports/scheduled-reports).

## Prepare a Weekly Enquiry Report

Use a contact form that already has a few completed test submissions. You need permission to manage reports and scheduled reports, a working mail transport, and access to the server scheduler (or help from the person who manages hosting).

In this example, name the report **Weekly Enquiries**, select your contact form and include its submission date and email field as columns. Send the report to a test mailbox you control while configuring delivery. The [Reports overview](/reports/reports) explains the available filters and columns.

## When to Use a Report

Use the **submissions index** for day-to-day review, editing, and status changes. Use a **saved report** when you need a reusable filter set, on-demand export in multiple formats, or recurring email delivery. See [Reports vs the submissions index](/reports/reports#reports-vs-the-submissions-index).

## Step 1: Create and Run a Saved Report

1. Go to **Formie → Reports** → **New Report**.
2. Configure **General** (name, handle, forms), **Filters**, **Columns**, **Display**, and **Export** — see [What a report includes](/reports/reports#what-a-report-includes).
3. Save and run the report. Confirm the summary, chart, and table match what you expect.
4. Use **Export** for a one-off download. Pick the format your team needs — [Export formats](/reports/reports#export-formats).

If the export is large, Formie queues it in the background — see [Large exports](/reports/reports#large-exports).

## Step 2: Schedule Email Delivery

Scheduled reports email a summary plus an export attachment on a daily or weekly cadence.

1. Ensure server cron runs `./craft formie/cron/run` — typically hourly. See [Before you start: cron](/reports/scheduled-reports#before-you-start-cron).
2. Create and save a report first, then open **Settings → Scheduled Reports** (or the report's **Scheduled** tab) and choose **New Scheduled Report**.
3. Link the report, set **Schedule** (frequency, day, hour), and **Delivery** (file type, recipients, optional subject/message). See [Creating a scheduled report](/reports/scheduled-reports#creating-a-scheduled-report).
4. **Save**, then use **Send Test Email** to inspect the summary and attachment in your own mailbox. The test goes to the acting user, not the configured recipients. Check recipient addresses separately and confirm the first scheduled delivery reaches them.

After the first successful delivery, attachments use an **incremental window** — submissions since the previous send. See [Incremental export windows](/reports/scheduled-reports#incremental-export-windows).

## Checklist Before Go-Live

- Cron job registered and `./craft formie/cron/run` runs without errors
- Test email received with expected summary and attachment
- Confirm the configured recipient addresses and user group are authorised to receive the exported data. Email recipients do not need Craft report permissions; those permissions govern control-panel actions, not receipt of attachments.
- Report and schedule settings deployed via project config to staging/production

## Troubleshooting

Work through [Scheduled reports — Troubleshooting](/reports/scheduled-reports#troubleshooting) for empty attachments, cron vs test-email mismatches, and attachment size limits.
