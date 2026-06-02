# Form Events

## Form Lifecycle Events

### The `beforeSaveForm` event
The event that is triggered before a form is saved. You can set `$event->isValid` to false to prevent saving.

```php
use craft\events\ModelEvent;
use verbb\formie\elements\Form;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $form = $event->sender;
    $event->isValid = false;
    // ...
});
```

### The `afterSaveForm` event
The event that is triggered after a form is saved.

```php
use craft\events\ModelEvent;
use verbb\formie\elements\Form;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $form = $event->sender;
    // ...
});
```

### The `beforeDeleteForm` event
The event that is triggered before a form is deleted.

The `isValid` event property can be set to `false` to prevent the deletion from proceeding.

```php
use verbb\formie\elements\Form;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_BEFORE_DELETE, function(Event $event) {
    $form = $event->sender;
    $event->isValid = false;
    // ...
});
```

### The `afterDeleteForm` event
The event that is triggered after a form is deleted.

```php
use verbb\formie\elements\Form;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_AFTER_DELETE, function(Event $event) {
    $form = $event->sender;
    // ...
});
```

### The `modifySlotTag` event
The event that is triggered when preparing a form slot tag for rendering. Modify the `tag` event property to change how a form is rendered.

The older `modifyHtmlTag` event name remains as a deprecated alias.

For more examples, consult the [Theme Config](/theming/theme-config) docs.

```php
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFormSlotTagEvent;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_MODIFY_SLOT_TAG, function(ModifyFormSlotTagEvent $event) {
    $form = $event->form;
    $tag = $event->tag;
    $key = $event->key;
    $context = $event->context;

    // For the field `<form>` element, change the tag and add attributes
    if ($event->key === 'form') {
        $event->tag->tag = 'div';
        $event->tag->attributes['class'][] = 'p-4 w-full mb-4';
    }
});
```

## Form Render Events

### The `modifyRenderForm` event
The event that is triggered when a form is rendered using the `craft.formie.renderForm()` function.

```php
use verbb\formie\events\ModifyRenderEvent;
use verbb\formie\services\Rendering;
use yii\base\Event;

Event::on(Rendering::class, Rendering::EVENT_MODIFY_RENDER_FORM, function(ModifyRenderEvent $event) {
    $html = $event->html;
    // ...
});
```

### The `modifyRenderPage` event
The event that is triggered when a form page is rendered using the `craft.formie.renderPage()` function.

```php
use verbb\formie\events\ModifyRenderEvent;
use verbb\formie\services\Rendering;
use yii\base\Event;

Event::on(Rendering::class, Rendering::EVENT_MODIFY_RENDER_PAGE, function(ModifyRenderEvent $event) {
    $html = $event->html;
    // ...
});
```

### The `modifyRenderField` event
The event that is triggered when a form field is rendered using the `craft.formie.renderField()` function.

```php
use verbb\formie\events\ModifyRenderEvent;
use verbb\formie\services\Rendering;
use yii\base\Event;

Event::on(Rendering::class, Rendering::EVENT_MODIFY_RENDER_FIELD, function(ModifyRenderEvent $event) {
    $html = $event->html;
    // ...
});
```

### The `modifyFormRenderOptions` event
The event that is triggered before a form is rendered, allowing render options to be changed in PHP.

```php
use verbb\formie\events\ModifyFormRenderOptionsEvent;
use verbb\formie\services\Rendering;
use yii\base\Event;

Event::on(Rendering::class, Rendering::EVENT_MODIFY_FORM_RENDER_OPTIONS, function(ModifyFormRenderOptionsEvent $event) {
    $form = $event->form;

    $event->renderOptions['outputCss'] = false;
});
```

### The `modifyFrontendJsTranslations` event
The event that is triggered to modify or define additional translation strings for Formie's front-end JavaScript.

Those strings are encoded into the inline JSON translation seed that Formie outputs alongside its browser assets, and are then merged into the browser package's translation store at startup.

```php
use verbb\formie\events\ModifyFrontendJsTranslationsEvent;
use verbb\formie\services\Rendering;
use yii\base\Event;

Event::on(Rendering::class, Rendering::EVENT_MODIFY_FRONTEND_JS_TRANSLATIONS, function(ModifyFrontendJsTranslationsEvent $event) {
    $event->strings[] = 'My custom string';
});
```

## Form Template Events

### The `beforeSaveFormTemplate` event
The event that is triggered before a form template is saved.

```php
use verbb\formie\events\FormTemplateEvent;
use verbb\formie\services\FormTemplates;
use yii\base\Event;

Event::on(FormTemplates::class, FormTemplates::EVENT_BEFORE_SAVE_FORM_TEMPLATE, function(FormTemplateEvent $event) {
    $template = $event->template;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveFormTemplate` event
The event that is triggered after a form template is saved.

```php
use verbb\formie\events\FormTemplateEvent;
use verbb\formie\services\FormTemplates;
use yii\base\Event;

Event::on(FormTemplates::class, FormTemplates::EVENT_AFTER_SAVE_FORM_TEMPLATE, function(FormTemplateEvent $event) {
    $template = $event->template;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteFormTemplate` event
The event that is triggered before a form template is deleted.

```php
use verbb\formie\events\FormTemplateEvent;
use verbb\formie\services\FormTemplates;
use yii\base\Event;

Event::on(FormTemplates::class, FormTemplates::EVENT_BEFORE_DELETE_FORM_TEMPLATE, function(FormTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `beforeApplyFormTemplateDelete` event
The event that is triggered before a form template is deleted.

```php
use verbb\formie\events\FormTemplateEvent;
use verbb\formie\services\FormTemplates;
use yii\base\Event;

Event::on(FormTemplates::class, FormTemplates::EVENT_BEFORE_APPLY_FORM_TEMPLATE_DELETE, function(FormTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `afterDeleteFormTemplate` event
The event that is triggered after a form template is deleted.

```php
use verbb\formie\events\FormTemplateEvent;
use verbb\formie\services\FormTemplates;
use yii\base\Event;

Event::on(FormTemplates::class, FormTemplates::EVENT_AFTER_DELETE_FORM_TEMPLATE, function(FormTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```
