# Notification Events

## Notification Model Events

### The `beforeSaveNotification` Event
The event that is triggered before an email notification is saved.

```php
use verbb\formie\events\NotificationEvent;
use verbb\formie\services\Notifications;
use yii\base\Event;

Event::on(Notifications::class, Notifications::EVENT_BEFORE_SAVE_NOTIFICATION, function(NotificationEvent $event) {
    $notification = $event->notification;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveNotification` Event
The event that is triggered after an email notification is saved.

```php
use verbb\formie\events\NotificationEvent;
use verbb\formie\services\Notifications;
use yii\base\Event;

Event::on(Notifications::class, Notifications::EVENT_AFTER_SAVE_NOTIFICATION, function(NotificationEvent $event) {
    $notification = $event->notification;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteNotification` Event
The event that is triggered before an email notification is deleted.

```php
use verbb\formie\events\NotificationEvent;
use verbb\formie\services\Notifications;
use yii\base\Event;

Event::on(Notifications::class, Notifications::EVENT_BEFORE_DELETE_NOTIFICATION, function(NotificationEvent $event) {
    $notification = $event->notification;
    // ...
});
```

### The `afterDeleteNotification` Event
The event that is triggered after an email notification is deleted.

```php
use verbb\formie\events\NotificationEvent;
use verbb\formie\services\Notifications;
use yii\base\Event;

Event::on(Notifications::class, Notifications::EVENT_AFTER_DELETE_NOTIFICATION, function(NotificationEvent $event) {
    $notification = $event->notification;
    // ...
});
```

### The `modifyExistingNotifications` Event
The event that is triggered to allow modifying of available existing notifications to select from.

```php
use verbb\formie\events\ModifyExistingNotificationsEvent;
use verbb\formie\services\Notifications;
use yii\base\Event;

Event::on(Notifications::class, Notifications::EVENT_MODIFY_EXISTING_NOTIFICATIONS, function(ModifyExistingNotificationsEvent $event) {
    $notifications = $event->notifications;
    // ...
});
```

### The `modifyNotificationSchema` Event
The event that is triggered to allow modification of the notification editor schema.

```php
use verbb\formie\events\ModifyNotificationSchemaEvent;
use verbb\formie\services\Notifications;
use yii\base\Event;

Event::on(Notifications::class, Notifications::EVENT_MODIFY_NOTIFICATION_SCHEMA, function(ModifyNotificationSchemaEvent $event) {
    $tabs = $event->tabs;
    // ...
});
```

### The `beforeSendNotification` Event
The event that is triggered before an email notification is queued or sent for a submission.

The `isValid` event property can be set to `false` to prevent the notification from being sent.

```php
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\services\Notifications;
use yii\base\Event;

Event::on(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, function(SendNotificationEvent $event) {
    $submission = $event->submission;
    $notification = $event->notification;

    // Prevent this notification from sending.
    // $event->isValid = false;
});
```

## Email Events

### The `modifyRenderVariables` Event
The event that is triggered to allow modification of the render variables used in templates.

```php
use craft\helpers\Template;
use verbb\formie\events\MailRenderEvent;
use verbb\formie\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_MODIFY_RENDER_VARIABLES, function(MailRenderEvent $event) {
    $email = $event->email;
    $submission = $event->submission;
    $notification = $event->notification;
    $renderVariables = $event->renderVariables;

    // Modify the "Content HTML" as defined in the email notification settings
    // Ensure that you provide "raw" HTML for `contentHtml`.
    $event->renderVariables['contentHtml'] = Template::raw('<p>Override Text</p>');
});
```

### The `beforeRenderEmail` Event
The event that is triggered before an email is rendered.

```php
use verbb\formie\events\MailEvent;
use verbb\formie\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_RENDER_EMAIL, function(MailEvent $event) {
    $email = $event->email;
    $submission = $event->submission;
    $notification = $event->notification;
    // ...
});
```

### The `afterRenderEmail` Event
The event that is triggered after an email is rendered.

```php
use verbb\formie\events\MailEvent;
use verbb\formie\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_AFTER_RENDER_EMAIL, function(MailEvent $event) {
    $email = $event->email;
    $submission = $event->submission;
    $notification = $event->notification;
    // ...
});
```

### The `beforeSendEmail` Event
The event that is triggered before an email is sent.

```php
use verbb\formie\events\MailEvent;
use verbb\formie\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, function(MailEvent $event) {
    $email = $event->email;
    $submission = $event->submission;
    $notification = $event->notification;
    // ...
});
```

### The `afterSendEmail` Event
The event that is triggered after an email is sent.

```php
use verbb\formie\events\MailEvent;
use verbb\formie\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_AFTER_SEND_MAIL, function(MailEvent $event) {
    $email = $event->email;
    $submission = $event->submission;
    $notification = $event->notification;
    // ...
});
```

## Email Template Events

### The `beforeSaveEmailTemplate` Event
The event that is triggered before an email template is saved.

```php
use verbb\formie\events\EmailTemplateEvent;
use verbb\formie\services\EmailTemplates;
use yii\base\Event;

Event::on(EmailTemplates::class, EmailTemplates::EVENT_BEFORE_SAVE_EMAIL_TEMPLATE, function(EmailTemplateEvent $event) {
    $template = $event->template;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveEmailTemplate` Event
The event that is triggered after an email template is saved.

```php
use verbb\formie\events\EmailTemplateEvent;
use verbb\formie\services\EmailTemplates;
use yii\base\Event;

Event::on(EmailTemplates::class, EmailTemplates::EVENT_AFTER_SAVE_EMAIL_TEMPLATE, function(EmailTemplateEvent $event) {
    $template = $event->template;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteEmailTemplate` Event
The event that is triggered before an email template is deleted.

```php
use verbb\formie\events\EmailTemplateEvent;
use verbb\formie\services\EmailTemplates;
use yii\base\Event;

Event::on(EmailTemplates::class, EmailTemplates::EVENT_BEFORE_DELETE_EMAIL_TEMPLATE, function(EmailTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `beforeApplyEmailTemplateDelete` Event
The event that is triggered before an email template is deleted.

```php
use verbb\formie\events\EmailTemplateEvent;
use verbb\formie\services\EmailTemplates;
use yii\base\Event;

Event::on(EmailTemplates::class, EmailTemplates::EVENT_BEFORE_APPLY_EMAIL_TEMPLATE_DELETE, function(EmailTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `afterDeleteEmailTemplate` Event
The event that is triggered after an email template is deleted.

```php
use verbb\formie\events\EmailTemplateEvent;
use verbb\formie\services\EmailTemplates;
use yii\base\Event;

Event::on(EmailTemplates::class, EmailTemplates::EVENT_AFTER_DELETE_EMAIL_TEMPLATE, function(EmailTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

## PDF Template Events

### The `beforeSavePdfTemplate` Event
The event that is triggered before a PDF template is saved.

```php
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_SAVE_PDF_TEMPLATE, function(PdfTemplateEvent $event) {
    $template = $event->template;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSavePdfTemplate` Event
The event that is triggered after a PDF template is saved.

```php
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_AFTER_SAVE_PDF_TEMPLATE, function(PdfTemplateEvent $event) {
    $template = $event->template;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeletePdfTemplate` Event
The event that is triggered before a PDF template is deleted.

```php
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_DELETE_PDF_TEMPLATE, function(PdfTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `beforeApplyPdfTemplateDelete` Event
The event that is triggered before a PDF template is deleted.

```php
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_APPLY_PDF_TEMPLATE_DELETE, function(PdfTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `afterDeletePdfTemplate` Event
The event that is triggered after a PDF template is deleted.

```php
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_AFTER_DELETE_PDF_TEMPLATE, function(PdfTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `beforeRenderPdf` Event
The event that is triggered before a PDF is rendered. You can provide a `pdf` property to return a custom-rendered PDF.

```php
use verbb\formie\events\PdfEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, function(PdfEvent $event) {
    $template = $event->template;
    $variables = $event->variables;

    // Override the template to your own
    $event->template = 'my-template-path';

    // Add your own variables for use in your PDF template
    $event->variables['myVariable'] = 'test';
});
```

### The `afterRenderPdf` Event
The event that is triggered after a PDF is rendered.

```php
use verbb\formie\events\PdfEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_AFTER_RENDER_PDF, function(PdfEvent $event) {
    $template = $event->template;
    $variables = $event->variables;
    $pdf = $event->pdf;
});
```

### The `modifyRenderOptions` Event
The event that is triggered to modify DOMPDF options before a PDF is rendered.

```php
use verbb\formie\events\PdfRenderOptionsEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_MODIFY_RENDER_OPTIONS, function(PdfRenderOptionsEvent $event) {
    $renderOptions = $event->renderOptions;
});
```
