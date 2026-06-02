# File upload

File upload manages selected files, uploaded asset ids, and the rendered summary output for upload fields.

## Events

#### The `formie:field:file-upload:uploaded-assets-sync` event

Triggered after uploaded asset ids have been hydrated back into field state and summary output.

```js
document.addEventListener('formie:field:file-upload:uploaded-assets-sync', (event) => {
  const { fileUpload, assets } = event.detail;

  if (fileUpload?.dataset.formieFieldHandle === 'attachments') {
    fileUpload.dataset.uploadedCount = String(assets.length);
  }
});
```

The shared module lifecycle also exposes scoped events such as `formie:module:file-upload:after-setup`.

## Related pages

- [File Upload field](/browser/ui-reference/fields/file-upload)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
