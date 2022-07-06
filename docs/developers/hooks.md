# Hooks
Hooks give you the means to insert your own Twig template code into Formie's templates without having to overwrite templates. This allows you to add HTML, Twig variables or even JavaScript as various points of the form's rendering.

Hooks are purely for inserting new content, and cannot be used to prevent something already existing in the Formie template from rendering. To do this, read our [theming](docs:theming) guide.

## Form
[View this template](https://github.com/verbb/formie/blob/craft-4/src/templates/_special/form-template/form.html).

Hook | Description
--- | ---
`formie.form.start` | The start of the form element, before the form title (if shown).
`formie.form.end` | The end of the form element.

### Example

```php
Craft::$app->getView()->hook('formie.form.start', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```


## Page
[View this template](https://github.com/verbb/formie/blob/craft-4/src/templates/_special/form-template/page.html#L09-L41).

Hook | Description
--- | ---
`formie.page.start` | The start of the page, before the page legend (if shown).
`formie.page.end` | The end of the page.

### Example

```php
Craft::$app->getView()->hook('formie.page.start', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```


## Buttons
[View this template](https://github.com/verbb/formie/blob/craft-4/src/templates/_special/form-template/page.html#L38-L92).

Hook | Description
--- | ---
`formie.buttons.before` |  Before the buttons container.
`formie.buttons.after` |  After the buttons container.
`formie.buttons.start` |  The start of the buttons container.
`formie.buttons.end` |  The end of the buttons container.
`formie.buttons.submit-start` |  The start of the submit/next page button.
`formie.buttons.submit-end` |  The end of the submit/next page button.
`formie.buttons.prev-start` |  The start of the previous page button (if shown).
`formie.buttons.prev-end` |  The end of the previous page button (if shown).

### Example

```php
Craft::$app->getView()->hook('formie.buttons.before', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```


## Field
[View this template](https://github.com/verbb/formie/blob/craft-4/src/templates/_special/form-template/field.html).

Hook | Description
--- | ---
`formie.field.field-before` | Before the field container.
`formie.field.field-after` | After the field container.
`formie.field.field-start` | The start of the field container.
`formie.field.input-before` | Before the input container.
`formie.field.input-after` | After the input container.
`formie.field.input-start` | The start of the input container.
`formie.field.input-end` | The end of the input container.

### Example

```php
Craft::$app->getView()->hook('formie.field.field-before', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```

## Control Panel - Edit Submission
When editing a submissions in the control panel, you'll have access to the following hooks.

Hook | Description
--- | ---
`formie.cp.submissions.edit` | Before submission detail view’s template blocks.
`formie.cp.submissions.edit.content` | After submission detail view’s main content.
`formie.cp.submissions.edit.details` | After submission detail view’s existing right sidebar details column.

### Example

```php
Craft::$app->getView()->hook('formie.cp.submissions.edit.content', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```


## Control Panel - Edit Sent Notification
When viewing a sent notification in the control panel, you'll have access to the following hooks.

Hook | Description
--- | ---
`formie.cp.sentNotifications.edit` | Before submission detail view’s template blocks.
`formie.cp.sentNotifications.edit.content` | After submission detail view’s main content.
`formie.cp.sentNotifications.edit.details` | After submission detail view’s existing right sidebar details column.


### Example

```php
Craft::$app->getView()->hook('formie.cp.sentNotifications.edit.content', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```
