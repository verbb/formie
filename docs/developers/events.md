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

### The `beforeSaveForm` event
The event that is triggered before a form is saved. You can set `$event->isValid` to false to prevent saving.

```php
use craft\events\ModelEvent;
use verbb\formie\elements\Form;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $form = $event->sender;

    $event->isValid = false;
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
});
```

### The `afterDeleteForm` event
The event that is triggered after a form is deleted.

```php
use verbb\formie\elements\Form;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_AFTER_DELETE, function(Event $event) {
    $form = $event->sender;
});
```

### The `modifyHtmlTag` event
The event that is triggered when preparing a form's HTML tag for render. Modify the `tag` event property change for a form's HTML is rendered.

For more examples, consult the [theming](docs:template-guides/theming) docs.

```php
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFormHtmlTagEvent;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_MODIFY_HTML_TAG, function(ModifyFormHtmlTagEvent $event) {
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




## Submission Events

### The `defineSubmissionRules` event
The event that is triggered to modify or define additional validation rules for submissions.

```php
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionRulesEvent;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_DEFINE_RULES, function(SubmissionRulesEvent $event) {
    $submission = $event->submission;
    $rules = $event->rules;
    // ...

    // Add a required field for field with handle `emailAddress`
    $event->rules[] = [['field:emailAddress'], 'required'];
});
```

### The `beforeSaveSubmission` event
The event that is triggered before a submission is saved. For multi-page forms, this event will occur on each page submission, as the submission is saved in its incomplete state.

You can set `$event->isValid` to false to prevent saving.

```php
use craft\events\ModelEvent;
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;

    $event->isValid = false;
});
```

### The `afterSaveSubmission` event
The event that is triggered after a submission is saved. For multi-page forms, this event will occur on each page submission, as the submission is saved in its incomplete state.

Do note the difference between this event and `afterSubmission`.

```php
use craft\events\ModelEvent;
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $submission = $event->sender;
});
```

### The `beforeSubmission` event
The event that is triggered before a submission has been completed. For multi-page forms, this is triggered when the final page has been reached and is being submitted.

```php
use verbb\formie\events\SubmissionEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_SUBMISSION, function(SubmissionEvent $event) {
    $submission = $event->submission;
    // ...
});
```

### The `beforeIncompleteSubmission` event
The event that is triggered before a submission has been made, but is incomplete. This is primarily for multi-page forms, where this event is fired on each submission of each page, except the final page.

```php
use verbb\formie\events\SubmissionEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_INCOMPLETE_SUBMISSION, function(SubmissionEvent $event) {
    $submission = $event->submission;
    // ...
});
```

### The `afterSubmission` event
The event that is triggered after a submission has been completed, whether successful or not. For multi-page forms, this is triggered when the final page has been reached and submitted.

You should always check `$event->success` if you want to ensure your event only triggers on submissions that have been successful.

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

### The `afterIncompleteSubmission` event
The event that is triggered after a submission has been made, whether successful or not, not while the submission is incomplete. This is primarily for multi-page forms, where this event is fired on each submission of each page, except the final page.

You should always check `$event->success` if you want to ensure your event only triggers on submissions that have been successful.

```php
use verbb\formie\events\SubmissionEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_AFTER_INCOMPLETE_SUBMISSION, function(SubmissionEvent $event) {
    $submission = $event->submission;
    $success = $event->success;
    // ...
});
```

### The `beforeDeleteSubmission` event
The event that is triggered before a submission is deleted.

The `isValid` event property can be set to `false` to prevent the deletion from proceeding.

```php
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_BEFORE_DELETE, function(Event $event) {
    $submission = $event->sender;
    $event->isValid = false;
});
```

### The `afterDeleteSubmission` event
The event that is triggered after a submission is deleted.

```php
use verbb\formie\elements\Submission;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_AFTER_DELETE, function(Event $event) {
    $submission = $event->sender;
});
```

### The `afterPruneSubmission` event
The event that is triggered after a submission is pruned (permanantly deleted) according to the data retention rules for the form.

```php
use verbb\formie\events\PruneSubmissionEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_AFTER_PRUNE_SUBMISSION, function(PruneSubmissionEvent $event) {
    $submission = $event->submission;
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

### The `beforeSubmissionRequest` event
The event that is triggered before a submission is validated. This is triggered on the controller action endpoint, so should primarily be used for modification of the submission before validation, or any other use-case that needs to occur in the controller.

The `isValid` event property can be set to `false` to prevent the submission from being validated, and instead stop on your own validation notices.

```php
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\events\SubmissionEvent;
use yii\base\Event;

Event::on(SubmissionsController::class, SubmissionsController::EVENT_BEFORE_SUBMISSION_REQUEST, function(SubmissionEvent $event) {
    $submission = $event->submission;
    $success = $event->success;
    // ...

    // Add an error for a field
    $event->submission->addError('emailAddress', 'This did not validate');

    // Tell Formie that the submission is now invalid, and to raise errors
    $event->isValid = false;
});
```

### The `afterSubmissionRequest` event
The event that is triggered after a submission has been completed, whether successful or not. This is triggered on the controller action endpoint, so should primarily be used for redirection hijacking, or any other use-case that needs to occur in the controller.

```php
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\events\SubmissionEvent;
use yii\base\Event;

Event::on(SubmissionsController::class, SubmissionsController::EVENT_AFTER_SUBMISSION_REQUEST, function(SubmissionEvent $event) {
    $submission = $event->submission;
    $success = $event->success;
    // ...
});
```

### The `modifyExportData` event
The event that is triggered when preparing submission(s) to be exported.

Modify the `exportData` event property to change the export data.

```php
use verbb\formie\elements\exporters\SubmissionExport;
use verbb\formie\events\ModifySubmissionExportDataEvent;
use yii\base\Event;

Event::on(SubmissionExport::class, SubmissionExport::EVENT_MODIFY_EXPORT_DATA, function(ModifySubmissionExportDataEvent $event) {
    $exportData = $event->exportData;
    $query = $event->query;
});
```


## Spam Events

### The `beforeMarkedAsSpam` event
The event that is triggered before the submission which has been marked as spam, is marked as spam. This event fires only after the submission has already been marked as spam.

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

### The `beforeSpamCheck` event
The event that is triggered before spam checks are carried out. These are not captcha checks, but Formie's spam checking like keywords.

You can use `$event->submission->isSpam` to modify the behaviour of whether Formie identifies the submission as spam or not.

```php
use verbb\formie\events\SubmissionSpamCheckEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_BEFORE_SPAM_CHECK, function(SubmissionSpamCheckEvent $event) {
    $submission = $event->submission;

    // Flag this submission as spam
    $event->submission->isSpam = true;
});
```

### The `afterSpamCheck` event
The event that is triggered after spam checks have been carried out. These are not captcha checks, but Formie's spam checking like keywords.

You can use `$event->submission->isSpam` to modify the behaviour of whether Formie identifies the submission as spam or not.

```php
use verbb\formie\events\SubmissionSpamCheckEvent;
use verbb\formie\services\Submissions;
use yii\base\Event;

Event::on(Submissions::class, Submissions::EVENT_AFTER_SPAM_CHECK, function(SubmissionSpamCheckEvent $event) {
    $submission = $event->submission;

    // Flag this submission as spam
    $event->submission->isSpam = true;
});
```


## Fields Events

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



## Field Events

### The `modifyDefaultValue` event
The event that is triggered when preparing a field's default value. You can use this on any class that includes the `FormFieldTrait` trait.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\formfields\SingleLineText;
use verbb\formie\events\ModifyFieldValueEvent;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_DEFAULT_VALUE, function(ModifyFieldValueEvent $event) {
    $field = $event->field;
    $value = $event->value;
    
    // Overwrite the value
    $event->value = 'My Custom Value';
});
```

### The `modifyHtmlTag` event
The event that is triggered when preparing a field's HTML tag for render. You can use this on any class that includes the `FormFieldTrait` trait.

Modify the `tag` event property change for a field's HTML is rendered.

For more examples, consult the [theming](docs:template-guides/theming) docs.

```php
use verbb\formie\fields\formfields\SingleLineText;
use verbb\formie\events\ModifyFieldHtmlTagEvent;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_HTML_TAG, function(ModifyFieldHtmlTagEvent $event) {
    $field = $event->field;
    $tag = $event->tag;
    $key = $event->key;
    $context = $event->context;

    // For the main field element, replace the class attribute entirely
    if ($event->key === 'field') {
        $event->tag->attributes['class'] = 'p-4 w-full mb-4';
    }

    // For the inner field wrapper, don't render the element
    if ($event->key === 'fieldContainer') {
        $event->tag = null;
    }

    // For the field `<input>` element, change the tag and add attributes
    if ($event->key === 'fieldInput') {
        $event->tag->tag = 'span';
        $event->tag->attributes['class'][] = 'p-4 w-full mb-4';
    }
});
```

### The `modifyValueAsString` event
The event that is triggered when preparing a field's value to be represented as a string. You can use this on any class that includes the `FormFieldTrait` trait.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\formfields\SingleLineText;
use verbb\formie\events\ModifyFieldValueEvent;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_VALUE_AS_STRING, function(ModifyFieldValueEvent $event) {
    $field = $event->field;
    $value = $event->value;
    $submission = $event->submission;
    
    // Overwrite the value
    $event->value = 'My Custom Value';
});
```

### The `modifyValueAsJson` event
The event that is triggered when preparing a field's value to be represented as a JSON object. You can use this on any class that includes the `FormFieldTrait` trait.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\formfields\SingleLineText;
use verbb\formie\events\ModifyFieldValueEvent;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_VALUE_AS_JSON, function(ModifyFieldValueEvent $event) {
    $field = $event->field;
    $value = $event->value;
    $submission = $event->submission;
    
    // Overwrite the value
    $event->value = 'My Custom Value';
});
```

### The `modifyValueForExport` event
The event that is triggered when preparing a field's value to be exported. You can use this on any class that includes the `FormFieldTrait` trait.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\formfields\MultiLineText;
use verbb\formie\events\ModifyFieldValueEvent;
use yii\base\Event;

Event::on(MultiLineText::class, MultiLineText::EVENT_MODIFY_VALUE_FOR_EXPORT, function(ModifyFieldValueEvent $event) {
    $field = $event->field;
    $value = $event->value;
    $submission = $event->submission;
    
    // Overwrite the value
    $event->value = 'My Custom Value';
});
```

### The `modifyValueForIntegration` event
The event that is triggered when preparing a field's value to be used in integrations. You can use this on any class that includes the `FormFieldTrait` trait.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\formfields\MultiLineText;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use yii\base\Event;

Event::on(MultiLineText::class, MultiLineText::EVENT_MODIFY_VALUE_FOR_INTEGRATION, function(ModifyFieldIntegrationValueEvent $event) {
    $field = $event->field;
    $integrationField = $event->integrationField;
    $integration = $event->integration;
    $value = $event->value;
    $submission = $event->submission;
    
    // Overwrite the value
    $event->value = 'My Custom Value';
});
```

### The `modifyValueForSummary` event
The event that is triggered when preparing a field's value to be represented in the Summary field. You can use this on any class that includes the `FormFieldTrait` trait.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\formfields\Dropdown;
use verbb\formie\events\ModifyFieldValueEvent;
use yii\base\Event;

Event::on(Dropdown::class, Dropdown::EVENT_MODIFY_VALUE_FOR_SUMMARY, function(ModifyFieldValueEvent $event) {
    $field = $event->field;
    $value = $event->value;
    $submission = $event->submission;
    
    // Overwrite the value
    $event->value = 'My Custom Value';
});
```


## Address Field Events

### The `modifyFrontEndSubfields` event
The event that is triggered to modify the front-end subfields for the field.

```php
use craft\helpers\ArrayHelper;
use verbb\formie\events\ModifyFrontEndSubfieldsEvent;
use verbb\formie\fields\formfields\Address;
use yii\base\Event;

Event::on(Address::class, Address::EVENT_MODIFY_FRONT_END_SUBFIELDS, function(ModifyFrontEndSubfieldsEvent $event) {
    $address1 = $event->rows[0][0];

    // Modify the `address1` field - a `SingleLineText` field.
    $address1->name = 'Address Line 1';

    $event->rows[0][0] = $address1;

    // Change the order of fields - remove them first
    $address2 = ArrayHelper::remove($event->rows[1], 0);
    $address3 = ArrayHelper::remove($event->rows[2], 0);

    // Add them to the first row
    $event->rows[0][] = $address2;
    $event->rows[0][] = $address3;
});
```



## Date Field Events

### The `modifyDateFormat` event
The event that is triggered to modify the Date Format for the field.

```php
use verbb\formie\events\ModifyDateTimeFormatEvent;
use verbb\formie\fields\formfields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_MODIFY_DATE_FORMAT, function(ModifyDateTimeFormatEvent $event) {
    $event->dateFormat = 'Y-m-d';
});
```

### The `modifyTimeFormat` event
The event that is triggered to modify the Time Format for the field.

```php
use verbb\formie\events\ModifyDateTimeFormatEvent;
use verbb\formie\fields\formfields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_MODIFY_TIME_FORMAT, function(ModifyDateTimeFormatEvent $event) {
    $event->timeFormat = 'H:i';
});
```

### The `registerDateFormatOptions` event
The event that is triggered to register the available options to select for date formatting.

```php
use verbb\formie\events\RegisterDateTimeFormatOpionsEvent;
use verbb\formie\fields\formfields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_REGISTER_DATE_FORMAT_OPTIONS, function(RegisterDateTimeFormatOpionsEvent $event) {
    $event->options[] = ['label' => 'Standard Formatting', 'value' => 'Y-m-d'];
});
```

### The `registerTimeFormatOptions` event
The event that is triggered to register the available options to select for time formatting.

```php
use verbb\formie\events\RegisterDateTimeFormatOpionsEvent;
use verbb\formie\fields\formfields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_REGISTER_TIME_FORMAT_OPTIONS, function(RegisterDateTimeFormatOpionsEvent $event) {
    $event->options[] = ['label' => 'Standard Formatting', 'value' => 'H:i:s'];
});
```

### The `modifyFrontEndSubfields` event
The event that is triggered to modify the front-end subfields for the field.

```php
use verbb\formie\events\ModifyFrontEndSubfieldsEvent;
use verbb\formie\fields\formfields\Date;
use yii\base\Event;
use DateTime;

Event::on(Date::class, Date::EVENT_MODIFY_FRONT_END_SUBFIELDS, function(ModifyFrontEndSubfieldsEvent $event) {
    $field = $event->field;

    $defaultValue = $field->defaultValue ? $field->defaultValue : new DateTime();
    $year = intval($defaultValue->format('Y'));
    $minYear = $year - 10;
    $maxYear = $year + 10;

    $yearOptions = [];

    for ($y = $minYear; $y < $maxYear; ++$y) {
        $yearOptions[] = ['value' => $y, 'label' => $y];
    }

    $event->rows[0]['Y'] = [
        'handle' => 'year',
        'options' => $yearOptions,
        'min' => $minYear,
        'max' => $maxYear,
    ];
});
```


## Email Field Events

### The `modifyEmailFieldUniqueQuery` event
The event that is triggered to modify the Submission query that determines whether this email is unique. You can modify the query to add your own logic.

```php
use verbb\formie\events\ModifyEmailFieldUniqueQueryEvent;
use verbb\formie\fields\formfields\Email;
use yii\base\Event;

Event::on(Email::class, Email::EVENT_MODIFY_UNIQUE_QUERY, function(ModifyEmailFieldUniqueQueryEvent $event) {
    $query = $event->query;
    $field = $event->field;
    // ...

    // e.g. change the query to only look at today's submissions, to allow only one per day.
    $event->query->andWhere('DATE(e.dateCreated) = UTC_DATE()');
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


## HTML Field Events

### The `modifyPurifierConfig` event
The event that is triggered to modify the HTML Purifier config.

```php
use verbb\formie\events\ModifyPurifierConfigEvent;
use verbb\formie\fields\formfields\Html;
use HTMLPurifier_AttrDef_Text;
use yii\base\Event;

Event::on(Html::class, Html::EVENT_MODIFY_PURIFIER_CONFIG, function(ModifyPurifierConfigEvent $event) {
    $event->config->getHTMLDefinition(true)->addAttribute('span', 'data-type', new HTMLPurifier_AttrDef_Text());
});
```


## Name Field Events

### The `modifyPrefixOptions` event
The event that is triggered to modify the Prefix options for the field.

```php
use verbb\formie\events\ModifyNamePrefixOptionsEvent;
use verbb\formie\fields\formfields\Name;
use yii\base\Event;

Event::on(Name::class, Name::EVENT_MODIFY_PREFIX_OPTIONS, function(ModifyNamePrefixOptionsEvent $event) {
    $event->options[] = ['label' => Craft::t('formie', 'Mx.'), 'value' => 'mx'];
});
```

### The `modifyFrontEndSubfields` event
The event that is triggered to modify the front-end subfields for the field.

```php
use verbb\formie\events\ModifyFrontEndSubfieldsEvent;
use verbb\formie\fields\formfields\Name;
use yii\base\Event;

Event::on(Name::class, Name::EVENT_MODIFY_FRONT_END_SUBFIELDS, function(ModifyFrontEndSubfieldsEvent $event) {
    $lastName = $event->rows[0][3];

    // Modify the `lastName` field - a `SingleLineText` field.
    $lastName->name = 'Surname';

    $event->rows[0][3] = $lastName;

    // Change the order of fields - remove them first
    $firstName = ArrayHelper::remove($event->rows[0], 1);
    $lastName = ArrayHelper::remove($event->rows[0], 3);

    // Reverse the order
    $event->rows[0][1] = $lastName;
    $event->rows[0]3 = $firstName;
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


## Predefined Field Options

### The `registerPredefinedOptions` event
The event that is triggered for registering predefined options for Dropdown, Radio Button and Checkboxes fields.

```php
use verbb\formie\events\RegisterPredefinedOptionsEvent;
use verbb\formie\services\PredefinedOptions;
use yii\base\Event;

Event::on(PredefinedOptions::class, PredefinedOptions::EVENT_REGISTER_PREDEFINED_OPTIONS, function(RegisterPredefinedOptionsEvent $event) {
    $event->options[] = CustomOptions::class;
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

### The `modifyRenderVariables` event
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

### The `beforeRenderEmail` event
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

### The `afterRenderEmail` event
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

### The `beforeSendEmail` event
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

### The `afterSendEmail` event
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



## PDF Template Events

### The `beforeSavePdfTemplate` event
The event that is triggered before a pdf template is saved.

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

### The `afterSavePdfTemplate` event
The event that is triggered after a pdf template is saved.

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

### The `beforeDeletePdfTemplate` event
The event that is triggered before a pdf template is deleted.

```php
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_DELETE_PDF_TEMPLATE, function(PdfTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `beforeApplyPdfTemplateDelete` event
The event that is triggered before a pdf template is deleted.

```php
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_APPLY_PDF_TEMPLATE_DELETE, function(PdfTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `afterDeletePdfTemplate` event
The event that is triggered after a pdf template is deleted.

```php
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_AFTER_DELETE_PDF_TEMPLATE, function(PdfTemplateEvent $event) {
    $template = $event->template;
    // ...
});
```

### The `beforeRenderPdf` event
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

### The `afterRenderPdf` event
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

### The `modifyRenderOptions` event
The event that is triggered to modify DOMPDF options before a PDF is rendered.

```php
use verbb\formie\events\PdfRenderOptionsEvent;
use verbb\formie\services\PdfTemplates;
use yii\base\Event;

Event::on(PdfTemplates::class, PdfTemplates::EVENT_MODIFY_RENDER_OPTIONS, function(PdfRenderOptionsEvent $event) {
    $renderOptions = $event->renderOptions;
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
    $event->captchas = myCaptcha::class;
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
The event that is triggered before an integration delete is applied to the database.

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
    $endpoint = $event->endpoint;
    $method = $event->method;
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

### The `modifyMappedFieldValue` event
The event that is triggered when parsing the field value made during submission to the field mapped in the provider. Using this event allows you to modify how Formie translates content from Craft into the third-party provider.

```php
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(ModifyFieldIntegrationValueEvent $event) {
    $integrationField = $event->integrationField;
    $field = $event->field;
    $value = $event->value;
    $submission = $event->submission;
    $integration = $event->integration;

    if ($field->handle === 'myFieldHandle') {
        $event->value = 'An overridden value';
    }
});
```

### The `modifyMappedFieldValues` event
The event that is triggered when parsing all the mapped values made during submission to the provider. Using this event allows you to modify how Formie translates content from Craft into the third-party provider.

```php
use verbb\formie\events\ModifyFieldIntegrationValuesEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_MODIFY_FIELD_MAPPING_VALUES, function(ModifyFieldIntegrationValuesEvent $event) {
    $fieldValues = $event->fieldValues;
    $submission = $event->submission;
    $fieldMapping = $event->fieldMapping;
    $fieldSettings = $event->fieldSettings;
    $integration = $event->integration;
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
    $submission = $event->submission;
    // ...
});
```


## Miscellaneous Integration Events

### The `modifyMiscellaneousPayload` event
The event that is triggered to allow modification of the payload sent to the integration provider.

```php
use verbb\formie\events\ModifyMiscellaneousPayloadEvent;
use verbb\formie\integrations\miscellaneous\GoogleSheets;
use yii\base\Event;

Event::on(GoogleSheets::class, GoogleSheets::EVENT_MODIFY_MISCELLANEOUS_PAYLOAD, function(ModifyMiscellaneousPayloadEvent $event) {
    $payload = $event->payload;
    $submission = $event->submission;
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


