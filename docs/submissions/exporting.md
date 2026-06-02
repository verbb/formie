# Exporting

Exporting lets you take saved submission data out of Formie for reporting, transfer, or further processing.

## What gets exported

Submission exports include both:

- built-in submission information, such as status and dates
- the field values stored on the submission

That makes exports useful for spreadsheets and reporting, not just for backup.

## What to expect from the output

Formie prepares field values for export rather than dumping them exactly as they are stored internally.

That matters because some fields need to be flattened or expanded so the export is actually useful in a table format.

For example:

- simple fields may export as one column
- complex fields may export as multiple columns
- repeatable or nested content can expand to fit the export

Exporting is useful when:

- a team needs a spreadsheet of submission data
- you want to hand data to another system manually
- you need a reporting snapshot outside Craft

Submission export is about the saved records.

If you need to move the form structure itself, use [Forms → Import & Export](/forms/import-export) instead.
