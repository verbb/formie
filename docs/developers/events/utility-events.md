# Utility Events

## Stencil Events

### The `beforeSaveStencil` event
The event that is triggered before a stencil is saved.

```php
use verbb\formie\events\StencilEvent;
use verbb\formie\services\Stencils;
use yii\base\Event;

Event::on(Stencils::class, Stencils::EVENT_BEFORE_SAVE_STENCIL, function(StencilEvent $event) {
    $stencil = $event->stencil;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveStencil` event
The event that is triggered after a stencil is saved.

```php
use verbb\formie\events\StencilEvent;
use verbb\formie\services\Stencils;
use yii\base\Event;

Event::on(Stencils::class, Stencils::EVENT_AFTER_SAVE_STENCIL, function(StencilEvent $event) {
    $stencil = $event->stencil;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteStencil` event
The event that is triggered before a stencil is deleted.

```php
use verbb\formie\events\StencilEvent;
use verbb\formie\services\Stencils;
use yii\base\Event;

Event::on(Stencils::class, Stencils::EVENT_BEFORE_DELETE_STENCIL, function(StencilEvent $event) {
    $stencil = $event->stencil;
    // ...
});
```

### The `beforeApplyStencilDelete` event
The event that is triggered before a stencil is deleted.

```php
use verbb\formie\events\StencilEvent;
use verbb\formie\services\Stencils;
use yii\base\Event;

Event::on(Stencils::class, Stencils::EVENT_BEFORE_APPLY_STENCIL_DELETE, function(StencilEvent $event) {
    $stencil = $event->stencil;
    // ...
});
```

### The `afterDeleteStencil` event
The event that is triggered after a stencil is deleted.

```php
use verbb\formie\events\StencilEvent;
use verbb\formie\services\Stencils;
use yii\base\Event;

Event::on(Stencils::class, Stencils::EVENT_AFTER_DELETE_STENCIL, function(StencilEvent $event) {
    $stencil = $event->stencil;
    // ...
});
```

## Migration Events

### The `modifyField` event
The event that is triggered during a migration (from Sprout Forms or Freeform), trying to map the respective third-party field to a Formie field. The `field` variable represents the Sprout Forms or Freeform field, and `newField` represents the Formie equivalent field.

You can use this event to map a custom Sprout Forms or Freeform field to a field Formie can understand.

```php
use verbb\formie\events\ModifyMigrationFieldEvent;
use verbb\formie\migrations\plugins\MigrateFreeform4;
use yii\base\Event;

Event::on(MigrateFreeform4::class, MigrateFreeform4::EVENT_MODIFY_FIELD, function(ModifyMigrationFieldEvent $event) {
    $field = $event->field;
    $newField = $event->newField;
    // ...
});

use verbb\formie\events\ModifyMigrationFieldEvent;
use verbb\formie\migrations\plugins\MigrateSproutForms;
use yii\base\Event;

Event::on(MigrateSproutForms::class, MigrateSproutForms::EVENT_MODIFY_FIELD, function(ModifyMigrationFieldEvent $event) {
    $field = $event->field;
    $newField = $event->newField;
    // ...
});
```

### The `modifyForm` event
The event that is triggered during a migration when a third-party form is mapped to a Formie form.

```php
use verbb\formie\events\ModifyMigrationFormEvent;
use verbb\formie\migrations\plugins\MigrateFreeform5;
use yii\base\Event;

Event::on(MigrateFreeform5::class, MigrateFreeform5::EVENT_MODIFY_FORM, function(ModifyMigrationFormEvent $event) {
    $form = $event->form;
    $newForm = $event->newForm;
    // ...
});
```

### The `modifyNotification` event
The event that is triggered during a migration when a third-party notification is mapped to a Formie notification.

```php
use verbb\formie\events\ModifyMigrationNotificationEvent;
use verbb\formie\migrations\plugins\MigrateFreeform5;
use yii\base\Event;

Event::on(MigrateFreeform5::class, MigrateFreeform5::EVENT_MODIFY_NOTIFICATION, function(ModifyMigrationNotificationEvent $event) {
    $form = $event->form;
    $notification = $event->notification;
    $newNotification = $event->newNotification;
    // ...
});
```

### The `modifySubmission` event
The event that is triggered during a migration when a third-party submission is mapped to a Formie submission.

```php
use verbb\formie\events\ModifyMigrationSubmissionEvent;
use verbb\formie\migrations\plugins\MigrateFreeform5;
use yii\base\Event;

Event::on(MigrateFreeform5::class, MigrateFreeform5::EVENT_MODIFY_SUBMISSION, function(ModifyMigrationSubmissionEvent $event) {
    $form = $event->form;
    $submission = $event->submission;
    // ...
});
```

## Variable Events

### The `registerVariables` event
The event that is triggered to register additional variable groups used when variable values are parsed.

```php
use Craft;
use verbb\formie\events\RegisterVariablesEvent;
use verbb\formie\helpers\Variables;
use yii\base\Event;

Event::on(Variables::class, Variables::EVENT_REGISTER_VARIABLES, function(RegisterVariablesEvent $event) {
    $event->variables['custom'] = [
        'label' => Craft::t('site', 'Custom'),
        'variables' => [
            'siteName' => Craft::$app->getSystemName(),
        ],
    ];
});
```

### The `registerTransformers` event
The event that is triggered to register additional variable transformers used when variable values are parsed.

```php
use Craft;
use verbb\formie\helpers\Variables;
use verbb\formie\events\RegisterTransformersEvent;
use yii\base\Event;

Event::on(Variables::class, Variables::EVENT_REGISTER_TRANSFORMERS, function(RegisterTransformersEvent $event) {
    $event->transformerRegistry['custom'] = [
        'label' => Craft::t('site', 'Custom'),
        'transformer' => CustomVariableTransformer::class,
    ];
});
```

## Email Domain Events

### The `modifyFreeEmailDomains` event
The event that is triggered when Formie prepares the list of free email domains.

```php
use verbb\formie\events\ModifyEmailDomainsEvent;
use verbb\formie\services\EmailDomains;
use yii\base\Event;

Event::on(EmailDomains::class, EmailDomains::EVENT_MODIFY_FREE_EMAIL_DOMAINS, function(ModifyEmailDomainsEvent $event) {
    $event->domains[] = 'example.com';
});
```

### The `modifyTwigEnvironment` event
The event that is triggered to modify the allowed items in the Twig Sandbox used to parse some content like Email Notifications.

Formie uses a Twig Sandbox with a limited set of allowed Tags, Filter and Functions. This also extends to the allowed Methods and Properties. This is a security measure to prevent Twig injections into the fields that support Twig.

```php
use verbb\formie\Formie;
use verbb\formie\events\ModifyTwigEnvironmentEvent;
use yii\base\Event;

Event::on(Formie::class, Formie::EVENT_MODIFY_TWIG_ENVIRONMENT, function(ModifyTwigEnvironmentEvent $event) {
    // Add allowed Twig Tags
    $event->allowedTags[] = [
        'tag',
    ];

    // Add allowed Twig Filters
    $event->allowedFilters[] = [
        'format',
        'format_number',
    ];

    // Add allowed Twig Functions
    $event->allowedFunctions[] = [
        'alias',
    ];

    // Add allowed methods
    // i.e. to allow `craft.entries.one()`
    $event->allowedMethods[\craft\web\twig\variables\CraftVariable::class] = 'entries';
    $event->allowedMethods[\craft\elements\db\ElementQuery::class] = 'one';

    // Add allowed properties
    $event->allowedProperties[\craft\base\Element::class] = 'title';
});
```
