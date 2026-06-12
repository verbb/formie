# Upload manager

Upload manager enhances File Upload fields when **Display type** is **Upload Manager (Advanced)**. It is powered by [Uppy](https://uppy.io/) (`@uppy/core` + `@uppy/xhr-upload`) and stages files immediately through Formie’s upload endpoints.

## When it loads

Formie adds the `upload-manager` module to a form’s client module manifest when a File Upload field uses **Upload Manager (Advanced)**. The `file-upload` module is not loaded for those fields.

Simple **File Input** fields continue to use the [file-upload module](/browser/modules/field/file-upload).

## Endpoints

The module posts to Formie actions configured on the dropzone element:

| Endpoint attribute | Default action |
| --- | --- |
| `data-formie-file-upload-upload-endpoint` | `formie/file-upload/upload` |
| `data-formie-file-upload-delete-endpoint` | `formie/file-upload/delete` |
| `data-formie-file-upload-hydrate-endpoint` | `formie/file-upload/hydrate` |

Upload and delete requests include form context such as `handle`, `fieldHandle`, `renderId`, `draftContextToken`, and `submissionId` when available.

## Behaviour

Upload manager:

- accepts drag-and-drop and browse input
- uploads each file asynchronously and writes returned asset IDs into hidden inputs
- hydrates filenames for asset IDs already present in the DOM (saved submissions, drafts, multipage resume)
- removes staged files through the delete endpoint and clears hidden inputs
- reorders uploaded files with up/down controls and keeps hidden inputs in sync
- validates required state and file-count limits through the shared validator
- re-initializes when Repeater rows are added

Uploaded asset IDs are synchronized through the same hidden-input contract as Simple file uploads. Both display types emit `formie:field:file-upload:uploaded-assets-sync`.

## Events

#### The `formie:field:file-upload:uploaded-assets-sync` event

Triggered after uploaded asset IDs have been synchronized into hidden inputs and the upload manager file list.

```js
document.addEventListener('formie:field:file-upload:uploaded-assets-sync', (event) => {
  const { fileUpload, assets } = event.detail;

  if (fileUpload?.dataset.formieFieldHandle === 'attachments') {
    fileUpload.dataset.uploadedCount = String(assets.length);
  }
});
```

#### The `formie:field:file-upload:uploaded-assets-reordered` event

Triggered after the user reorders uploaded files with the sort controls.

```js
document.addEventListener('formie:field:file-upload:uploaded-assets-reordered', (event) => {
  const { fileUpload, assets } = event.detail;

  console.log('New asset order', fileUpload?.dataset.formieFieldHandle, assets);
});
```

The shared module lifecycle also exposes scoped events such as `formie:module:upload-manager:init` and `formie:module:upload-manager:destroy`.

## Related pages

- [File Upload field](/browser/ui-reference/fields/file-upload)
- [File upload module](/browser/modules/field/file-upload)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
