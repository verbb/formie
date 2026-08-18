# File Upload

File Upload is the field for selecting, restoring, and listing uploaded files.

Use this page to preserve the input attributes, hidden upload inputs, and summary or upload-manager containers used by the field.

## Preview

### File Input (Simple)

<FormiePreview src="../examples/file-upload.preview.ts" />

### Upload Manager (Advanced)

<FormiePreview src="../examples/upload-manager.preview.ts" />

## Display types

File Upload can render as:

- `fileInput` — native `<input type="file">` with an uploaded-file summary (**File Input (Simple)**)
- `uploadManager` — drag-and-drop upload zone with async staged uploads (**Upload Manager (Advanced)**)

Both display types share the same hidden asset ID contract and submit Craft asset references.

## Attributes

File Upload fields span a field wrapper, one or more controls, and supporting hidden inputs.

### Field

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation, conditions, and error rendering | Required |

### File Input (Simple)

| Attribute | Description | Importance |
| --- | --- | --- |
| `name="fields[attachments][]"` | File input payload name for selected files | Required |
| `data-formie-input` | Generic Formie input marker included in normal output | Recommended |
| `data-formie-file-input` | File upload selector used by the `file-upload` module | Required for Simple upload behaviour |
| `data-formie-input-id` | Stable identifier used to match upload results | Required for upload behaviour |
| `data-formie-input-type="file"` | Server-rendered file-input type marker | Recommended |
| `data-formie-file-upload-key` | Explicit event-matching key for upload responses | Optional |
| `data-formie-file-limit` | File-count validation limit | Optional |
| `data-formie-size-min-limit` | Minimum file size validation in MB | Optional |
| `data-formie-size-max-limit` | Maximum file size validation in MB | Optional |
| `data-formie-file-upload-hydrate-endpoint` | Override for uploaded asset hydration | Optional |

### Upload Manager (Advanced)

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-upload-manager-root` | Root container for the upload manager UI | Required for Advanced upload behaviour |
| `data-formie-upload-manager` | Dropzone selector used by the `upload-manager` module | Required for Advanced upload behaviour |
| `data-formie-upload-manager-browse` | Browse button inside the dropzone | Required |
| `data-formie-upload-manager-input` | Hidden file input used for browse selection | Required |
| `data-formie-validation-skip` | Opts the browse input out of client-side validation; required state is owned by the status input | Required |
| `data-formie-upload-manager-status` | Screen-reader-only validation status input | Required for required validation |
| `data-formie-upload-manager-list` | Uploaded file list container | Required |
| `data-formie-input-type="upload-manager"` | Server-rendered display-type marker on the dropzone | Recommended |
| `data-formie-upload-key` | Stable upload identity for the field | Recommended |
| `data-formie-file-upload-upload-endpoint` | Override for async upload action | Optional |
| `data-formie-file-upload-delete-endpoint` | Override for staged file deletion | Optional |
| `data-formie-file-upload-hydrate-endpoint` | Override for uploaded asset hydration | Optional |
| `data-formie-file-limit` | File-count validation limit | Optional |
| `data-formie-size-min-limit` | Minimum file size validation in MB | Optional |
| `data-formie-size-max-limit` | Maximum file size validation in MB | Optional |

Upload manager list items are created at runtime and include:

| Attribute | Description |
| --- | --- |
| `data-formie-upload-manager-item` | Uploaded file row |
| `data-formie-upload-manager-filename` | Filename label |
| `data-formie-upload-manager-progress` | Progress container |
| `data-formie-upload-manager-progress-track` | Progress track |
| `data-formie-upload-manager-progress-bar` | Progress bar |
| `data-formie-upload-manager-progress-label` | Progress status label |
| `data-formie-upload-manager-sort-controls` | Sort control wrapper |
| `data-formie-upload-manager-sort="up"` / `"down"` | Reorder buttons |
| `data-formie-upload-manager-remove` | Remove button |
| `data-formie-upload-manager-error` | Upload error message |

## Supporting elements

Both display types depend on hidden asset inputs:

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-file-upload-anchor="true"` | Stable insertion point for uploaded asset IDs | Required when uploads are synchronized back into hidden inputs |
| `data-formie-file-upload-asset-id="true"` | Stored uploaded asset IDs for restored state | Managed by Formie |

### Field summary (Simple only)

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `[data-formie-file-summary]` | Summary wrapper for uploaded filenames | Recommended |
| `[data-formie-file-summary-container]` | Summary list container | Recommended |
| `[data-formie-file-summary-item]` | Individual uploaded file label | Recommended |

If a summary container is missing on Simple fields, Formie can create one after the file input. Hand-authored templates should still keep it explicit when possible so the server-rendered and browser-updated states match.

## Behavior

### File Input (Simple)

The `file-upload` module:

- validates file count and size limits from `data-formie-file-limit`, `data-formie-size-min-limit`, and `data-formie-size-max-limit`
- listens for upload result payloads and synchronizes uploaded asset IDs into hidden inputs beside the file input
- hydrates filename labels for stored asset IDs when only numeric asset references are initially available
- resets both summary output and hidden asset inputs when the form emits `formie:state:reset`

### Upload Manager (Advanced)

The `upload-manager` module:

- uploads files immediately through Formie’s upload endpoint and writes asset IDs into hidden inputs
- hydrates existing asset IDs from hidden inputs on init
- removes staged files through the delete endpoint
- reorders uploaded files with up/down controls and keeps hidden inputs in sync
- validates required state and file-count limits through the shared validator
- re-initializes when Repeater rows are added

See the [upload-manager module](/browser/modules/field/upload-manager) page for endpoint and event details.

## Events

File Upload emits field events when uploaded asset ids have been synchronized back into the field state.

#### The `formie:field:file-upload:uploaded-assets-sync` event

Triggered after uploaded asset ids have been hydrated into the field state. Both display types emit this event.

```js
document.addEventListener('formie:field:file-upload:uploaded-assets-sync', (event) => {
  const { fileUpload, assets } = event.detail;

  // Only target the attachments field.
  if (fileUpload?.dataset.formieFieldHandle !== 'attachments') {
    return;
  }

  // Reflect the current uploaded file count on the field wrapper.
  fileUpload.dataset.uploadedCount = String(assets.length);
});
```

#### The `formie:field:file-upload:uploaded-assets-reordered` event

Triggered after the user reorders uploaded files in **Upload Manager (Advanced)** fields.

```js
document.addEventListener('formie:field:file-upload:uploaded-assets-reordered', (event) => {
  const { fileUpload, assets } = event.detail;

  console.log('Reordered assets', fileUpload?.dataset.formieFieldHandle, assets);
});
```

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### File Input (Simple)

| Class | Description |
| --- | --- |
| `formie-input` | Shared file-input styling and focus treatment |
| `formie-file-input` | File Upload input styling class |
| `formie-input-error` | Error-state styling class |
| `formie-field-note` | Summary note wrapper styling |
| `formie-file-summary` | Summary wrapper for uploaded files |
| `formie-file-summary-container` | Summary list container |
| `formie-file-summary-item` | Individual uploaded file label |

### Upload Manager (Advanced)

| Class | Description |
| --- | --- |
| `formie-upload-manager` | Upload manager root styling |
| `formie-upload-manager-dropzone` | Dropzone surface |
| `formie-upload-manager-browse-button` | Browse button styling |
| `formie-upload-manager-list` | Uploaded file list |
| `formie-upload-manager-item` | Uploaded file row |
| `formie-upload-manager-filename` | Filename label |
| `formie-upload-manager-progress` | Progress container |
| `formie-upload-manager-progress-track` | Progress track |
| `formie-upload-manager-progress-bar` | Progress bar |
| `formie-upload-manager-progress-label` | Progress status label |
| `formie-upload-manager-sort-controls` | Sort control wrapper |
| `formie-upload-manager-sort-button` | Reorder button styling |
| `formie-upload-manager-actions` | Action button wrapper |
| `formie-upload-manager-remove-button` | Remove button styling |
| `formie-upload-manager-error` | Upload error message |

## Accessibility notes

- Keep the file input or browse button explicitly labeled with the field label.
- Uploaded state should remain close to the control so users can review staged files.
- Error state should still apply through the surrounding field layout, not just the native file input or dropzone.

## Related pages

- [Upload manager module](/browser/modules/field/upload-manager)
- [File upload module](/browser/modules/field/file-upload)
- [Fields](/browser/ui-reference/fields/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
- [CSS variables](/browser/ui-reference/css-variables)
