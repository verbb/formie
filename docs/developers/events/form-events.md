# Form Events

## Form Lifecycle Events

### The `beforeSaveForm` Event
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

### The `afterSaveForm` Event
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

### The `beforeDeleteForm` Event
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

### The `afterDeleteForm` Event
The event that is triggered after a form is deleted.

```php
use verbb\formie\elements\Form;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_AFTER_DELETE, function(Event $event) {
    $form = $event->sender;
    // ...
});
```

### The `modifySlotTag` Event
The event that is triggered when preparing a form slot tag for rendering. Modify the `tag` event property to change how a form is rendered.

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

### The `registerModules` Event

Contribute browser modules for an entire form through `BrowserModuleManifestBuilder::EVENT_REGISTER_MODULES`. The event supplies the form and requested rendering surface. Contributions use a form target and core kind by default; specify other targets or kinds explicitly when needed. The normal surface filtering, occurrence keys and duplicate-key validation still apply. Module configuration is public browser data and must not contain credentials.

```php
use verbb\formie\client\modules\BrowserModuleManifestBuilder;
use verbb\formie\events\RegisterBrowserModulesEvent;
use verbb\formie\models\BrowserModule;
use yii\base\Event;

Event::on(BrowserModuleManifestBuilder::class, BrowserModuleManifestBuilder::EVENT_REGISTER_MODULES, function(RegisterBrowserModulesEvent $event) {
    if ($event->form->handle !== 'registration') {
        return;
    }

    $event->modules[] = new BrowserModule([
        'key' => 'acme:registration-help',
        'moduleId' => 'acme:help',
        'surfaces' => [BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED],
        'config' => ['message' => 'Contact us if you need help.'],
    ]);
});
```

Register the corresponding executable module in your trusted JavaScript bundle. A PHP contribution cannot load arbitrary JavaScript URLs.

### The `modifyRenderForm` Event
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

### The `modifyRenderPage` Event
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

### The `modifyRenderField` Event
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

### The `modifyFormRenderOptions` Event
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

### The `modifyBrowserJsTranslations` Event
The event that is triggered to modify or define additional translation strings for Formie's browser JavaScript.

Those strings are encoded into the inline JSON translation seed that Formie outputs alongside its browser assets, and are then merged into the browser package's translation store at startup.

```php
use verbb\formie\events\ModifyBrowserJsTranslationsEvent;
use verbb\formie\services\Rendering;
use yii\base\Event;

Event::on(Rendering::class, Rendering::EVENT_MODIFY_BROWSER_JS_TRANSLATIONS, function(ModifyBrowserJsTranslationsEvent $event) {
    $event->strings[] = 'My custom string';
});
```

## Form Template Events

### The `beforeSaveFormTemplate` Event
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

### The `afterSaveFormTemplate` Event
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

### The `beforeDeleteFormTemplate` Event
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

### The `beforeApplyFormTemplateDelete` Event
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

### The `afterDeleteFormTemplate` Event
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
