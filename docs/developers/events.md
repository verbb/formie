# Events

Formie provides a multitude of events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to modify Formie’s behavior.

## Form Events

### The `modifyFormCaptchas` event
The event that is triggered to allow modification of captchas for a specific form.

```php
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFormCaptchasEvent;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_MODIFY_FORM_CAPTCHAS, function(ModifyFormCaptchasEvent $event) {
    $captchas = $event->captchas;
    // ...
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




## Submission Events

### The `defineSubmissionRules` event
The event that is triggered to modify or define additional validation rules for submissions.

```php
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionRulesEvent;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_DEFINE_RULES, function(SubmissionRulesEvent $event) {
    $rules = $event->rules;
    // ...
});
```

### The `beforeMarkedAsSpam` event
The event that is triggered before the submission which has been marked as spam, is marked as spam.

The `isValid` event property can be set to `false` to prevent the submission from being marked as spam.

```php
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionMarkedAsSpamEvent;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_MARKED_AS_SPAM, function(SubmissionMarkedAsSpamEvent $event) {
    $submission = $event->submission;
    $isNew = $event->isNew;
    $isValid = $event->isValid;
    // ...
});
```

### The `afterSubmission` event
The event that is triggered after a submission has been made, whether successful or not.

```php
use verbb\formie\events\SubmissionEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_AFTER_SUBMISSION, function(SubmissionEvent $event) {
    $submission = $event->submission;
    $success = $event->success;
    // ...
});
```

### The `beforeSendNotification` event
The event that is triggered before an email notification is sent.

The `isValid` event property can be set to `false` to prevent the notification from proceeding.

```php
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_SEND_NOTIFICATION, function(SendNotificationEvent $event) {
    $submissionId = $event->submissionId;
    $notificationId = $event->notificationId;
    // ...
});
```

### The `beforeTriggerIntegration` event
The event that is triggered before an integration is triggered.

The `isValid` event property can be set to `false` to prevent the integration from proceeding.

```php
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_TRIGGER_INTEGRATION, function(SendNotificationEvent $event) {
    $submissionId = $event->submissionId;
    $integration = $event->integration;
    // ...
});
```



## Field Events

### The `registerFields` event
The event that is triggered for registration of additional fields.

```php
use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, function(RegisterFieldsEvent $event) {
    $event->fields[] = MyField::class;
    // ...
});
```

### The `registerLabelPositions` event
The event that is triggered for registration of additional label positions.

```php
use verbb\formie\events\RegisterFieldOptionsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_REGISTER_LABEL_POSITIONS, function(RegisterFieldOptionsEvent $event) {
    $fields = $event->fields;
    $options = $event->options;
    // ...
});
```

### The `registerInstructionsPositions` event
The event that is triggered for registration of additional instructions positions.

```php
use verbb\formie\events\RegisterFieldOptionsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_REGISTER_INSTRUCTIONS_POSITIONS, function(RegisterFieldOptionsEvent $event) {
    $fields = $event->fields;
    $options = $event->options;
    // ...
});
```

### The `modifyExistingFields` event
The event that is triggered to allow modifying of available existing fields to select from.

```php
use verbb\formie\events\ModifyExistingFieldsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_MODIFY_EXISTING_FIELDS, function(ModifyExistingFieldsEvent $event) {
    $fields = $event->fields;
    // ...
});
```

### The `modifyFieldConfig` event
The event that is triggered to allow modification of the config for fields, used in the form builder.

```php
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_MODIFY_FIELD_CONFIG, function(ModifyFieldConfigEvent $event) {
    $config = $event->config;
    // ...
});
```

### The `modifyFieldRowConfig` event
The event that is triggered to allow modification of the config for rows, used in the form builder.

```php
use verbb\formie\events\ModifyFieldRowConfigEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_MODIFY_FIELD_ROW_CONFIG, function(ModifyFieldRowConfigEvent $event) {
    $config = $event->config;
    // ...
});
```

### The `beforeSaveFieldRow` event
The event that is triggered before the field's row is saved.

```php
use verbb\formie\events\FieldRowEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_BEFORE_SAVE_FIELD_ROW, function(FieldRowEvent $event) {
    $row = $event->row;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveFieldRow` event
The event that is triggered after the field's row is saved.

```php
use verbb\formie\events\FieldRowEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_AFTER_SAVE_FIELD_ROW, function(FieldRowEvent $event) {
    $row = $event->row;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeSaveFieldPage` event
The event that is triggered before the field's page is saved.

```php
use verbb\formie\events\FieldPageEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_BEFORE_SAVE_FIELD_PAGE, function(FieldPageEvent $event) {
    $page = $event->page;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveFieldPage` event
The event that is triggered after the field's page is saved.

```php
use verbb\formie\events\FieldPageEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_AFTER_SAVE_FIELD_PAGE, function(FieldPageEvent $event) {
    $page = $event->page;
    $isNew = $event->isNew;
    // ...
});
```



## Phone Field Events

### The `modifyPhoneCountries` event
The event that is triggered to modify the available countries the phone field has access to.

```php
use verbb\formie\events\ModifyPhoneCountriesEvent;
use verbb\formie\services\Phone;
use yii\base\Event;

Event::on(Phone::class, Phone::EVENT_MODIFY_PHONE_COUNTRIES, function(ModifyPhoneCountriesEvent $event) {
    $countries = $event->countries;
    // ...
});
```


## Element Field Events

### The `modifyElementFieldQuery` event
The event that is triggered to modify the query for element fields, for when rendering options on the front-end.

```php
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\fields\formfields\Entries;
use yii\base\Event;

Event::on(Entries::class, Entries::EVENT_MODIFY_ELEMENT_QUERY, function(ModifyElementFieldQueryEvent $event) {
    $query = $event->query;
    $field = $event->field;
    // ...
});
```



## Synced Field Events

### The `beforeSaveSyncedField` event
The event that is triggered before a synced field is saved.

```php
use verbb\formie\events\SyncedFieldEvent;
use verbb\formie\services\Syncs;
use yii\base\Event;

Event::on(Syncs::class, Syncs::EVENT_BEFORE_SAVE_SYNCED_FIELD, function(SyncedFieldEvent $event) {
    $field = $event->field;
    // ...
});
```

### The `afterSaveSyncedField` event
The event that is triggered after a synced field is saved.

```php
use verbb\formie\events\SyncedFieldEvent;
use verbb\formie\services\Syncs;
use yii\base\Event;

Event::on(Syncs::class, Syncs::EVENT_AFTER_SAVE_SYNCED_FIELD, function(SyncedFieldEvent $event) {
    $field = $event->field;
    // ...
});
```



## Submission Status Events

### The `beforeSaveStatus` event
The event that is triggered before a submission status is saved.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_BEFORE_SAVE_STATUS, function(StatusEvent $event) {
    $status = $event->status;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveStatus` event
The event that is triggered after a submission status is saved.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_AFTER_SAVE_STATUS, function(StatusEvent $event) {
    $status = $event->status;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteStatus` event
The event that is triggered before a submission status is deleted.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_BEFORE_DELETE_STATUS, function(StatusEvent $event) {
    $status = $event->status;
    // ...
});
```

### The `beforeApplyStatusDelete` event
The event that is triggered before a submission status is deleted.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_BEFORE_APPLY_STATUS_DELETE, function(StatusEvent $event) {
    $status = $event->status;
    // ...
});
```

### The `afterDeleteStatus` event
The event that is triggered after a submission status is deleted.

```php
use verbb\formie\events\StatusEvent;
use verbb\formie\services\Statuses;
use yii\base\Event;

Event::on(Statuses::class, Statuses::EVENT_AFTER_DELETE_STATUS, function(StatusEvent $event) {
    $status = $event->status;
    // ...
});
```



## Notification Events

### The `beforeSaveNotification` event
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

### The `afterSaveNotification` event
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

### The `beforeDeleteNotification` event
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

### The `afterDeleteNotification` event
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

### The `modifyExistingNotifications` event
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



## Email Events

### The `beforeSendEmail` event
The event that is triggered before an email is sent.

```php
use verbb\formie\events\MailEvent;
use verbb\formie\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, function(MailEvent $event) {
    $email = $event->email;
    // ...
});
```

### The `afterSendEmail` event
The event that is triggered after an email is sent.

```php
use verbb\formie\events\
use verbb\formie\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_AFTER_SEND_MAIL, function(Event $event) {
    $email = $event->email;
    // ...
});
```



## Email Template Events

### The `beforeSaveEmailTemplate` event
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

### The `afterSaveEmailTemplate` event
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

### The `beforeDeleteEmailTemplate` event
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

### The `beforeApplyEmailTemplateDelete` event
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

### The `afterDeleteEmailTemplate` event
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



## Form Template Events

### The `beforeSaveFormTemplate` event
The event that is triggered before an email template is saved.

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
The event that is triggered after an email template is saved.

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
The event that is triggered before an email template is deleted.

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
The event that is triggered before an email template is deleted.

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
The event that is triggered after an email template is deleted.

```php
use verbb\formie\events\FormTemplateEvent;
use verbb\formie\services\FormTemplates;
use yii\base\Event;

Event::on(FormTemplates::class, FormTemplates::EVENT_AFTER_DELETE_FORM_TEMPLATE, function(FormTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```



## Integration Events

### The `registerFormieIntegrations` event
The event that is triggered for registering new captcha integrations.

```php
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
    $event->captchas = new myCaptcha();
    // ...
});
```

### The `beforeSaveIntegration` event
The event that is triggered before an integration is saved.

```php
use verbb\formie\events\IntegrationEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_BEFORE_SAVE_INTEGRATION, function(IntegrationEvent $event) {
    $integration = $event->integration;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveIntegration` event
The event that is triggered after an integration is saved.

```php
use verbb\formie\events\IntegrationEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_AFTER_SAVE_INTEGRATION, function(IntegrationEvent $event) {
    $integration = $event->integration;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteIntegration` event
The event that is triggered before an integration is deleted

```php
use verbb\formie\events\IntegrationEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_BEFORE_DELETE_INTEGRATION, function(IntegrationEvent $event) {
    $integration = $event->integration;
    // ...
});
```

### The `beforeApplyIntegrationDelete` event
The event that is triggered before a integration delete is applied to the database.

```php
use verbb\formie\events\IntegrationEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_BEFORE_APPLY_INTEGRATION_DELETE, function(IntegrationEvent $event) {
    $integration = $event->integration;
    // ...
});
```

### The `afterDeleteIntegration` event
The event that is triggered after an integration is deleted

```php
use verbb\formie\events\IntegrationEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_AFTER_DELETE_INTEGRATION, function(IntegrationEvent $event) {
    $integration = $event->integration;
    // ...
});
```



## Integration Payload Events

The below events an example using the `Mailchimp` class, but any class that inherits from the `verbb\formie\base\Integration` class can use these events.

### The `beforeSendPayload` event
The event that is triggered before an integration sends its payload.

The `isValid` event property can be set to `false` to prevent the payload from being sent.

```php
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_BEFORE_SEND_PAYLOAD, function(SendIntegrationPayloadEvent $event) {
    $submission = $event->submission;
    $payload = $event->payload;
    $integration = $event->integration;
    // ...
});
```

### The `afterSendPayload` event
The event that is triggered after an integration sends its payload.

The `isValid` event property can be set to `false` to flag a payload-sending response.

```php
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_AFTER_SEND_PAYLOAD, function(SendIntegrationPayloadEvent $event) {
    $submission = $event->submission;
    $payload = $event->payload;
    $integration = $event->integration;
    $response = $event->response;
    // ...
});
```



## Integration Connection Events

The below events an example using the `Mailchimp` class, but any class that inherits from the `verbb\formie\base\Integration` class can use these events.

### The `beforeCheckConnection` event
The event that is triggered before an integration has checked its connection.

The `isValid` event property can be set to `false` to prevent the payload from being sent.

```php
use verbb\formie\events\IntegrationConnectionEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_BEFORE_CHECK_CONNECTION, function(IntegrationConnectionEvent $event) {
    $integration = $event->integration;
    // ...
});
```

### The `afterCheckConnection` event
The event that is triggered after an integration has checked its connection.

```php
use verbb\formie\events\IntegrationConnectionEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_AFTER_CHECK_CONNECTION, function(IntegrationConnectionEvent $event) {
    $integration = $event->integration;
    $success = $event->success;
    // ...
});
```



## Integration Form Settings Events

The below events an example using the `Mailchimp` class, but any class that inherits from the `verbb\formie\base\Integration` class can use these events.

### The `beforeFetchFormSettings` event
The event that is triggered before an integration fetches its available settings for the form settings.

The `isValid` event property can be set to `false` to prevent the payload from being sent.

```php
use verbb\formie\events\IntegrationFormSettingsEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_BEFORE_FETCH_FORM_SETTINGS, function(IntegrationFormSettingsEvent $event) {
    $integration = $event->integration;
    // ...
});
```

### The `afterFetchFormSettings` event
The event that is triggered after an integration fetches its available settings for the form settings.

```php
use verbb\formie\events\IntegrationFormSettingsEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_AFTER_FETCH_FORM_SETTINGS, function(IntegrationFormSettingsEvent $event) {
    $integration = $event->integration;
    $settings = $event->settings;
    // ...
});
```

### The `parseMappedFieldValue` event
The event that is triggered when parsing the field value made during submission to the field mapped in the provider. Using this event allows you to modify how Formie translates content from Craft into the third-party provider.

You **must** use `handled = true` to flag you are overriding field content behaviour. If not, it'll fall back to Formie's default handling.

```php
use verbb\formie\events\ParseMappedFieldValueEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_PARSE_MAPPED_FIELD_VALUE, function(ParseMappedFieldValueEvent $event) {
    $integrationField = $event->integrationField;
    $formField = $event->formField;
    $value = $event->value;
    $submission = $event->submission;
    $integration = $event->integration;

    if ($formField->handle === 'myFieldHandle') {
        $event->value = 'An overridden value';

        // This tells Formie you've overridden the parsed value, and to use that.
        $event->handled = true;
    }
});
```



## Integration OAuth Events

### The `afterOauthCallback` event

```php
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\events\OauthTokenEvent;
use yii\base\Event;

Event::on(IntegrationsController::class, IntegrationsController::EVENT_AFTER_OAUTH_CALLBACK, function(OauthTokenEvent $event) {
    $token = $event->token;
    // ...
});
```

### The `beforeSaveToken` event
The event that is triggered before an integration token is saved.

```php
use verbb\formie\events\TokenEvent;
use verbb\formie\services\Tokens;
use yii\base\Event;

Event::on(Tokens::class, Tokens::EVENT_BEFORE_SAVE_TOKEN, function(TokenEvent $event) {
    $token = $event->token;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveToken` event
The event that is triggered after an integration token is saved.

```php
use verbb\formie\events\TokenEvent;
use verbb\formie\services\Tokens;
use yii\base\Event;

Event::on(Tokens::class, Tokens::EVENT_AFTER_SAVE_TOKEN, function(TokenEvent $event) {
    $token = $event->token;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteToken` event
The event that is triggered before an integration token is deleted.

```php
use verbb\formie\events\TokenEvent;
use verbb\formie\services\Tokens;
use yii\base\Event;

Event::on(Tokens::class, Tokens::EVENT_BEFORE_DELETE_TOKEN, function(TokenEvent $event) {
    $token = $event->token;
    // ...
});
```

### The `afterDeleteToken` event
The event that is triggered after an integration token is deleted.

```php
use verbb\formie\events\TokenEvent;
use verbb\formie\services\Tokens;
use yii\base\Event;

Event::on(Tokens::class, Tokens::EVENT_AFTER_DELETE_TOKEN, function(TokenEvent $event) {
    $token = $event->token;
    // ...
});
```



## Address Provider Integration Events

### The `modifyAddressProviderHtml` event
The event that is triggered after an address provider has its HTML generated. You are able to modify its HTML.

```php
use verbb\formie\events\ModifyAddressProviderHtmlEvent;
use verbb\formie\integrations\addressproviders\AddressFinder;
use yii\base\Event;

Event::on(AddressFinder::class, AddressFinder::EVENT_MODIFY_ADDRESS_PROVIDER_HTML, function(ModifyAddressProviderHtmlEvent $event) {
    $html = $event->html;
    // ...
});
```



## Webhook Integration Events

### The `modifyWebhookPayload` event
The event that is triggered to allow modification of the payload sent to your defined webhook URL.

```php
use verbb\formie\events\ModifyWebhookPayloadEvent;
use verbb\formie\integrations\webhooks\Zapier;
use yii\base\Event;

Event::on(Zapier::class, Zapier::EVENT_MODIFY_WEBHOOK_PAYLOAD, function(ModifyWebhookPayloadEvent $event) {
    $payload = $event->payload;
    // ...
});
```



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

You can use this event to custom Sprout Forms or Freeform field to a field Formie can understand.

```php
use verbb\formie\events\ModifyMigrationFieldEvent;
use verbb\formie\migrations\MigrateFreeform;
use yii\base\Event;

Event::on(MigrateFreeform::class, MigrateFreeform::EVENT_MODIFY_FIELD, function(ModifyMigrationFieldEvent $event) {
    $field = $event->field;
    $newField = $event->newField;
    // ...
});

use verbb\formie\events\ModifyMigrationFieldEvent;
use verbb\formie\migrations\MigrateSproutForms;
use yii\base\Event;

Event::on(MigrateSproutForms::class, MigrateSproutForms::EVENT_MODIFY_FIELD, function(ModifyMigrationFieldEvent $event) {
    $field = $event->field;
    $newField = $event->newField;
    // ...
});
```


