# File Upload

Use File Upload when the form should accept one or more files from the person submitting it.

Use File Upload for resumes, supporting documents, images, and attachments. If you only need a URL to a file hosted elsewhere, use Single-Line Text instead.

## Key settings

- **Upload location** - Choose the asset volume and subpath where files should be saved.
- **File count limit** - Restrict how many files can be uploaded.
- **File size limits** - Set minimum and maximum file sizes.
- **Allowed file kinds** - Restrict uploads to the file types the form actually needs.
- **Filename format** - Control how uploaded assets are named.
- **Notification attachments** - Allow uploaded files to be attached to email notifications when the notification is configured to include them.

## Submitted value

File Upload stores references to uploaded Craft assets. In templates, exports, summaries and integrations, treat the value as uploaded asset data rather than the original browser file input.

For GraphQL mutations, File Upload fields accept an array of `FileUploadInput` values. You can pass base64 file data for new uploads or asset IDs for existing assets. See [Create Submissions](/graphql/create-submissions#file-upload-fields).

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
            fieldSummaryContainer: {
                attributes: {
                    class: 'my-file-summary',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the upload input, hidden inputs or file summary markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

Custom-rendered forms must use `multipart/form-data`; otherwise the browser will not send selected files. If you override the field markup, preserve the upload input and hidden asset inputs Formie needs to synchronize uploaded assets.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [File Upload](/browser/ui-reference/fields/file-upload)

## Related fields

- Use [Single-Line Text](/fields/single-line-text) if the user only needs to provide a URL to a file hosted elsewhere.

