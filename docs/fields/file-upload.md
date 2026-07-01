# File Upload

Use File Upload when the form should accept one or more files from the person submitting it.

Use File Upload for resumes, supporting documents, images, and attachments. If you only need a URL to a file hosted elsewhere, use Single-Line Text instead.

## Display types

File Upload supports two display types:

- **File Input (Simple)** — native browser file input with an uploaded-file summary. Files upload when the form is submitted.
- **Upload Manager (Advanced)** — drag-and-drop zone with async staged uploads, progress, remove, and reorder controls. Files upload immediately to Craft via the Formie upload endpoints and are stored as asset IDs until the form is submitted.

Existing forms default to **File Input (Simple)**.

Choose **Upload Manager (Advanced)** when you want immediate feedback, drag-and-drop, or the ability to remove staged files before submit. Choose **File Input (Simple)** when you want the smallest front-end footprint or standard browser file-picker behaviour.

## Key settings

- **Display Type** - Choose Simple or Advanced upload UI.
- **Upload location** - Choose the asset volume and subpath where files should be saved.
- **File count limit** - Restrict how many files can be uploaded.
- **File size limits** - Set minimum and maximum file sizes.
- **Allowed file kinds** - Restrict uploads to the file types the form actually needs.
- **Filename format** - Control how uploaded assets are named.
- **Notification attachments** - Allow uploaded files to be attached to email notifications when the notification is configured to include them.

## Submitted value

File Upload stores references to uploaded Craft assets. In templates, exports, summaries and integrations, treat the value as uploaded asset data rather than the original browser file input.

Both display types submit the same asset ID payload. **Upload Manager (Advanced)** stages uploads immediately and writes asset IDs into hidden inputs as each file completes. **File Input (Simple)** uploads files during submission processing.

### Asset retention

Each File Upload field can define **Asset Data Retention** in the field settings. When enabled, Formie deletes uploaded assets for that field after the configured duration while keeping the submission record. This is separate from the form-level **Data Retention** setting, which deletes entire submissions.

Retention is processed by the `./craft formie/cron/run` or `./craft formie/gc/run` console commands. Schedule one of these on cron for production sites. Craft garbage collection provides a best-effort fallback on web requests.

For GraphQL mutations, File Upload fields accept an array of `FileUploadInput` values. You can pass base64 file data for new uploads or asset IDs for existing assets (including assets staged via the Formie upload endpoints). See [Create Submissions](/graphql/create-submissions#file-upload-fields).

## Multipage forms and drafts

Uploaded files persist across multipage navigation and page reloads when Formie has saved the incomplete submission or draft state — for example after clicking **Next**, using save-and-resume, or editing an existing submission.

Uploading files without advancing the form or saving a draft does not survive a full page reload. The staged assets may still exist temporarily in Craft, but the form is not re-rendered with their asset IDs until submission or draft context is restored.

## Theme config

The File Upload field can be targeted with the `fileUpload` theme config key.

See [File Upload Field theme config](/theming/theme-config#file-upload-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        fileUpload: {
            fieldInput: {
                attributes: {
                    class: 'my-file-input',
                },
            },
            fieldDropzone: {
                attributes: {
                    class: 'my-upload-dropzone',
                },
            },
            fieldFileList: {
                attributes: {
                    class: 'my-upload-list',
                },
            },
            fieldSummaryContainer: {
                attributes: {
                    class: 'my-file-summary',
                },
            },
        },
    },
}) }}
```

`fieldDropzone` and `fieldFileList` apply to **Upload Manager (Advanced)** fields. `fieldInput` and `fieldSummaryContainer` apply to **File Input (Simple)** fields.

Use theme config for class and attribute changes. Use a template override only when the upload input, hidden inputs or file summary markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

Custom-rendered forms must use `multipart/form-data`; otherwise the browser will not send selected files. If you override the field markup, preserve the upload input and hidden asset inputs Formie needs to synchronize uploaded assets.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [File Upload](/browser/ui-reference/fields/file-upload)
- [Upload manager module](/browser/modules/field/upload-manager)

## Related fields

- Use [Single-Line Text](/fields/single-line-text) if the user only needs to provide a URL to a file hosted elsewhere.
