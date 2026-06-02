# Multi Line Text

Multi Line Text is the standard textarea field.

Use this page to see the default markup and preserve the required attributes when you override templates or adapt the field to another design system.

## Preview

<FormiePreview src="../examples/multi-line-text.preview.ts" />

## Rich text module

Multi Line Text can also act as the transport field behind the `rich-text` module. In that mode, the textarea stays in the DOM as the submitted value, while the browser module mounts a Pell editor UI beside it.

<FormiePreview src="../examples/multi-line-text-rich-text.preview.ts" />

Keep these rich-text attributes intact when you enable the module:

| Attribute | Description | Importance |
| --- | --- | --- |
| `[data-formie-rich-text]` | Rich-text editor mount container | Required |
| `textarea[data-formie-multi-line-text-input]` | Underlying textarea transport input | Required |
| `data-formie-input` | Generic input marker on the textarea | Required |
| `data-formie-input-id` | Stable textarea identity | Required |

### Events

Rich-text-enhanced Multi Line Text fields emit field events in addition to the broader events documented on [JavaScript events](/browser/behavior/javascript-events).

#### The `formie:field:rich-text:before-init` event

Triggered before the Pell editor is created. Use this to adjust editor options before the field mounts.

```js
document.addEventListener('formie:field:rich-text:before-init', (event) => {
  // Keep the toolbar minimal for a short project brief field.
  event.detail.options.actions = ['bold', 'italic', 'olist', 'ulist', 'link'];
});
```

#### The `formie:field:rich-text:after-init` event

Triggered after the editor instance has been attached to the field.

```js
document.addEventListener('formie:field:rich-text:after-init', (event) => {
  const { richText } = event.detail;

  // Pell exposes the editable region on the editor instance.
  if (!(richText?.content instanceof HTMLElement)) {
    return;
  }

  // Mark the contenteditable surface for custom UI or analytics hooks.
  richText.content.setAttribute('data-rich-text-ready', 'true');
});
```

#### The `formie:field:rich-text:populate` event

Triggered after the editor content has been synchronized back into the textarea value.

```js
document.addEventListener('formie:field:rich-text:populate', (event) => {
  const { richText, value } = event.detail;

  // The rich-text module keeps the textarea as the transport input.
  if (!(richText instanceof HTMLTextAreaElement)) {
    return;
  }

  // Mirror editor state onto the field wrapper for custom UI.
  richText.closest('[data-formie-field]')?.toggleAttribute('data-rich-text-has-content', value.trim().length > 0);
});
```

The broader module lifecycle also emits `formie:module:rich-text:init` and `formie:module:rich-text:destroy` when you need global setup or teardown hooks.

### Module example

Register the `rich-text` field module against a Multi Line Text field target:

```html
<form
  class="formie-form"
  data-formie
  data-formie-form
  data-formie-modules='[
    {
      "id": "rich-text",
      "type": "field",
      "targets": [
        {
          "targetType": "field",
          "targetId": "projectBrief"
        }
      ],
      "config": {
        "options": {
          "buttons": ["bold", "italic", "ulist", "link"]
        }
      }
    }
  ]'
>
  ...
</form>
```

The module looks for a field that contains both a `[data-formie-rich-text]` container and a `textarea[data-formie-multi-line-text-input]`, then mounts the editor and keeps the textarea value synchronized for validation and submission.

## Attributes

Multi Line Text fields span an outer field wrapper plus the actual `<textarea>`.

### Field

| Attribute | Description | Importance |
| --- | --- | --- |
| `data-formie-field-handle` | Stable field identity used by validation, conditions, calculations, and error rendering | Required |
| `data-formie-field-type="multi-line-text"` | Field-type marker on the outer wrapper | Recommended |

### Field input

| Attribute | Description | Importance |
| --- | --- | --- |
| `name` | Submission payload key for the textarea value | Required |
| `data-formie-input` | Generic Formie input marker included in normal output | Recommended |
| `data-formie-input-id` | Stable input identity for browser behavior and module targeting | Recommended |
| `data-formie-multi-line-text-input` | Textarea selector used by text-specific enhancements | Required for text-specific modules |
| `data-formie-input-type="textarea"` | Server-rendered input-type metadata | Recommended |
| `rows` | Initial control height | Optional |
| `aria-describedby` | Connects instructions and errors | Recommended |

## Styling classes

These classes are for presentation only. They are not behavior requirements:

### Field input

| Class | Description |
| --- | --- |
| `formie-textarea` | Multi Line Text textarea styling |
| `formie-input` | Shared control styling and focus treatment |
| `formie-input-error` | Error-state styling class |

### Rich text

| Class | Description |
| --- | --- |
| `formie-rich-text` | Pell editor mount surface for the rich-text enhancement |

## Related pages

- [Modules](/browser/modules/)
- [Single Line Text](/browser/ui-reference/fields/single-line-text)
- [JavaScript API](/browser/)
- [JavaScript events](/browser/behavior/javascript-events)
- [CSS variables](/browser/ui-reference/css-variables)
