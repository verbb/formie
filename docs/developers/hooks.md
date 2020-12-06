# Hooks
Rather than maintaining a full form template, we recommend using Formie's default form template along with your own style using the existing classes and structure.

We provide many [template hooks](https://docs.craftcms.com/v3/extend/template-hooks.html) in the form template so that if needed, you can add additional functionality to the form while receiving bug fixes and improvements.

For a full list of available hooks, please refer to the list below.

## Form

[View this template](https://github.com/verbb/formie/blob/craft-3/src/templates/_special/form-template/form.html).

Hook | Description
--- | ---
`formie.form.start` | The start of the form element, before the form title (if shown).
`formie.form.end` | The end of the form element.

### Example

```php
Craft::$app->view->hook('formie.form.start', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```


## Page

[View this template](https://github.com/verbb/formie/blob/craft-3/src/templates/_special/form-template/page.html#L09-L41).

Hook | Description
--- | ---
`formie.page.start` | The start of the page, before the page legend (if shown).
`formie.page.end` | The end of the page.

### Example

```php
Craft::$app->view->hook('formie.page.start', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```


## Buttons

[View this template](https://github.com/verbb/formie/blob/craft-3/src/templates/_special/form-template/page.html#L38-L92).

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
Craft::$app->view->hook('formie.buttons.before', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```


## Field

[View this template](https://github.com/verbb/formie/blob/craft-3/src/templates/_special/form-template/field.html).

Hook | Description
--- | ---
`formie.field.field-before` | Before the field container.
`formie.field.field-after` | After the field container.
`formie.field.field-start` | The start of the field container.
`formie.field.input-before` | Before the input container.
`formie.field.input-after` | After the input container.
`formie.field.input-start` | The start of the input container.
`formie.field.input-end` | The end of the input container.
`formie.subfield.field-start` | The start of the subfield field container.
`formie.subfield.field-end` | The end of the subfield field container.
`formie.subfield.input-before` | Before the subfield input container.
`formie.subfield.input-after` | After the subfield input container.
`formie.subfield.input-start` | The start of the subfield input container.
`formie.subfield.input-end` | The end of the subfield input container.

### Example

```php
Craft::$app->view->hook('formie.field.field-before', function(array &$context) {
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
Craft::$app->view->hook('formie.cp.submissions.edit.content', function(array &$context) {
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
Craft::$app->view->hook('formie.cp.sentNotifications.edit.content', function(array &$context) {
    // Add a variable to be accessible in the context object.
    $context['foo'] = 'bar';

    // Optionally return a string
    return '<p>Hey!</p>';
});
```
