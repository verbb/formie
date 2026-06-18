# Reports

Reports are saved analytical views over Formie submissions. Use them when the submissions index is too operational — review, edit, change status — and you need answers like “how many enquiries came in last week, by form, excluding spam?” without exporting by hand each time.

Open **Formie → Reports** to create and run reports. Report definitions sync through [project config](https://craftcms.com/docs/5.x/system/project-config.html), so filters, columns, and display settings deploy with your Craft project.

## What a report includes

Each report combines:

| Area | What it controls |
| --- | --- |
| **General** | Name, handle, and which forms are included |
| **Filters** | Date windows (new reports default to the last month), complete/incomplete/spam, statuses, and more |
| **Columns** | Which submission attributes and fields appear in the table and export |
| **Display** | Chart type and summary layout on the report run screen |
| **Export** | Export filename pattern (with variable tokens) |
| **Scheduled** | Links to [scheduled email delivery](/reports/scheduled-reports) for this report |

Running a report shows summary counts, a trend chart, and a paginated submissions table. **Export** downloads submission data using the report’s current filters and column settings.

## Export formats

From the report run screen, choose **Export** and pick a file type:

| Format | Typical use |
| --- | --- |
| **Excel** (`.xlsx`) | Spreadsheets and sharing with non-technical teams |
| **CSV** (`.csv`) | Universal tabular import |
| **JSON** (`.json`) | Feeds and developer tooling |
| **XML** (`.xml`) | Structured interchange |
| **Text** (`.txt`) | Plain tab-separated output |

The file extension is added automatically from the format you choose. The **Export Filename** setting on the report editor controls the basename only — not the extension.

[Scheduled reports](/reports/scheduled-reports) attach exports in the **File Type** you choose on each schedule (CSV, Excel, JSON, XML, or text). That uses the same export engine as on-demand downloads.

## Large exports

Reports with more submissions than the **Report Async Export Row Threshold** (**Settings → Submissions**, default **1000**) are exported in the background via Craft’s queue. The control panel shows an **Exporting…** state while the export runs, and your download starts automatically when it is ready. If you leave the control panel before it finishes, a backup email with a download link is sent to your account email address when Craft’s mailer is configured.

CSV and text exports are streamed row-by-row to disk so memory use stays bounded on large datasets. Scheduled reports still attach small exports to the summary email; when an export exceeds the **Maximum Email Attachment Size** (**Settings → Notifications**), the email includes a signed download link instead.

Download links expire after **Report Interactive Export Expiry** or **Report Scheduled Export Expiry** (**Settings → Submissions**, defaults 72 and 48 hours). **Single-Use Report Export Downloads** is enabled by default so each signed email link works once; control panel auto-downloads use a separate authenticated route and do not invalidate email links.

## Reports vs the submissions index

| | Submissions index | Reports |
| --- | --- | --- |
| **Purpose** | Day-to-day submission management | Saved analysis and recurring output |
| **Filters** | Per-session element index filters | Saved, reusable filter sets |
| **Export** | Not available (use reports instead) | On-demand export in multiple formats |
| **Email delivery** | — | [Scheduled reports](/reports/scheduled-reports) (configurable file type) |

If you only need a one-off file, run a report and export in the format you need. If you need the same view every Monday morning, save the report and attach a scheduled delivery.

## Export filename

On the report editor **Export** tab, set **Export Filename** to control the basename for downloads and scheduled attachments.

The default pattern is `formie-report-{handle}-{timestamp}`. Use the variable picker to insert tokens such as:

- `{name}` and `{handle}` — report title and handle
- `{timestamp}` — current date and time when the file is generated
- General and site tokens from the same picker used elsewhere in Formie

Tokens are resolved when the export runs, not when you save the report.

## Permissions

Reports use dedicated Craft user permissions:

| Permission | Allows |
| --- | --- |
| **Access reports** | View the Reports section and run saved reports |
| **Manage reports** | Create, edit, and delete reports; export data |
| **Manage scheduled reports** | Configure scheduled email delivery under **Settings → Scheduled Reports** and on a report’s **Scheduled** tab |

Users with **Export submissions** can also export from reports without full manage access.

## Project config

Report settings are stored under `formie.reports.{uid}` in project config. Scheduled delivery settings (recipients, schedule, email content) are stored under `formie.scheduledReports.{uid}`.

Runtime fields such as `lastSentAt` live in the database and are not synced through project config.

## Related reading

- [Scheduled reports](/reports/scheduled-reports)
- [Submissions overview](/submissions/overview)
