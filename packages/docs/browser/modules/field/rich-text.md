# Rich text

Rich text mounts a Pell editor beside the textarea transport input used by Multi Line Text.

## Events

#### The `formie:field:rich-text:before-init` event

Triggered before the Pell editor is created.

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

  if (richText?.content instanceof HTMLElement) {
    richText.content.setAttribute('data-rich-text-ready', 'true');
  }
});
```

#### The `formie:field:rich-text:populate` event

Triggered after the editor content has been synchronized back into the textarea value.

```js
document.addEventListener('formie:field:rich-text:populate', (event) => {
  const { richText, value } = event.detail;

  if (richText instanceof HTMLTextAreaElement) {
    richText.closest('[data-formie-field]')?.toggleAttribute('data-rich-text-has-content', value.trim().length > 0);
  }
});
```

The broader module lifecycle also emits scoped events such as `formie:module:rich-text:after-setup`.

## Related pages

- [Multi Line Text field](/browser/ui-reference/fields/multi-line-text)
- [Overview](/browser/modules/)
- [JavaScript events](/browser/behavior/javascript-events)
