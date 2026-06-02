# File Upload

File Upload is the field for selecting, restoring, and listing uploaded files.

Use this page to preserve the input attributes, hidden upload inputs, and summary containers used by the field.

## Preview

<FormiePreview src="../examples/file-upload.preview.ts" />

## Attributes

File Upload fields span a field wrapper, the real file input, and supporting hidden inputs or summary containers.

### Field

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation, conditions, and error rendering | Required |

### Field input

| Attribute | Description | Importance |
| --- | --- | --- |
| `name="fields[attachments][]"` | File input payload name for selected files | Required |
| `data-formie-input` | Generic Formie input marker included in normal output | Recommended |
| `data-formie-file-input` | File upload selector used by the field module | Required for upload behavior |
| `data-formie-input-id` | Stable identifier used to match upload results | Required for upload behavior |
| `data-formie-input-type="file"` | Server-rendered file-input type marker | Recommended |
| `data-formie-file-upload-key` | Explicit event-matching key for upload responses | Optional |
| `data-formie-file-limit` | File-count validation limit | Optional |
| `data-formie-size-min-limit` | Minimum file size validation in MB | Optional |
| `data-formie-size-max-limit` | Maximum file size validation in MB | Optional |
| `data-formie-file-upload-hydrate-endpoint` | Override for uploaded asset hydration | Optional |

## Supporting elements

The file upload module also depends on a few adjacent elements:

### Hidden inputs

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-file-upload-anchor="true"` | Stable insertion point for uploaded asset IDs | Required when uploads are synchronized back into hidden inputs |
| `data-formie-file-upload-asset-id="true"` | Stored uploaded asset IDs for restored state | Managed by Formie |

### Field summary

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `[data-formie-file-summary]` | Summary wrapper for uploaded filenames | Recommended |
| `[data-formie-file-summary-container]` | Summary list container | Recommended |
| `[data-formie-file-summary-item]` | Individual uploaded file label | Recommended |

If a summary container is missing, Formie can create one after the file input. Hand-authored templates should still keep it explicit when possible so the server-rendered and browser-updated states match.

## Behavior

File Upload manages more than the native `<input type="file">` surface:

- It validates file count and size limits from `data-formie-file-limit`, `data-formie-size-min-limit`, and `data-formie-size-max-limit`.
- It listens for upload result payloads and synchronizes uploaded asset IDs into hidden inputs beside the file input.
- It hydrates filename labels for stored asset IDs when only numeric asset references are initially available.
- It resets both summary output and hidden asset inputs when the form emits `formie:state:reset`.

## Events

File Upload emits a field event when uploaded asset ids have been synchronized back into the field state.

#### The `formie:field:file-upload:uploaded-assets-sync` event

Triggered after uploaded asset ids have been hydrated into the field state and the summary output.

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

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field input

| Class | Description |
| --- | --- |
| `formie-input` | Shared file-input styling and focus treatment |
| `formie-file-input` | File Upload input styling class |
| `formie-input-error` | Error-state styling class |

### Field summary

| Class | Description |
| --- | --- |
| `formie-field-note` | Summary note wrapper styling |
| `formie-file-summary` | Summary wrapper for uploaded files |
| `formie-file-summary-container` | Summary list container |
| `formie-file-summary-item` | Individual uploaded file label |

## Accessibility notes

- Keep the file input explicitly labeled with the field label.
- Summary output should remain close to the control so uploaded state is easy to review.
- Error state should still apply through the surrounding field layout, not just the native file input.

## Related pages

- [Fields](/browser/ui-reference/fields/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
- [CSS variables](/browser/ui-reference/css-variables)
