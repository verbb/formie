# Field Events

## Field Registration Events

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
The event that is triggered to allow modification of a field's form builder config. Listen on the field type you want to modify.

```php
use verbb\formie\fields\SingleLineText;
use verbb\formie\events\ModifyFieldConfigEvent;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_FIELD_CONFIG, function(ModifyFieldConfigEvent $event) {
    $config = $event->config;
    // ...
});
```

### The `modifyFieldSchema` event
The event that is triggered to allow modification of a field's form builder schema. Listen on the field type you want to modify.

```php
use verbb\formie\fields\SingleLineText;
use verbb\formie\events\ModifyFieldSchemaEvent;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_FIELD_SCHEMA, function(ModifyFieldSchemaEvent $event) {
    $tabs = $event->tabs;
    // ...
});
```

## Field Element Events

These events run when a Formie field is saving, deleting, restoring, or propagating an element value. Listen on the field type you want to target.

### The `beforeElementSave` event
The event that is triggered before a field saves its value to an element.

```php
use verbb\formie\events\FieldElementEvent;
use verbb\formie\fields\SingleLineText;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_BEFORE_ELEMENT_SAVE, function(FieldElementEvent $event) {
    $field = $event->sender;
    $element = $event->element;

    // Prevent the field value from saving.
    // $event->isValid = false;
});
```

### The `afterElementSave` event
The event that is triggered after a field saves its value to an element.

```php
use verbb\formie\events\FieldElementEvent;
use verbb\formie\fields\SingleLineText;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_AFTER_ELEMENT_SAVE, function(FieldElementEvent $event) {
    $field = $event->sender;
    $element = $event->element;
    // ...
});
```

### The `afterElementPropagate` event
The event that is triggered after a field value is propagated for an element.

```php
use verbb\formie\events\FieldElementEvent;
use verbb\formie\fields\SingleLineText;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_AFTER_ELEMENT_PROPAGATE, function(FieldElementEvent $event) {
    $field = $event->sender;
    $element = $event->element;
    // ...
});
```

### The `beforeElementDelete` event
The event that is triggered before a field value is deleted from an element.

```php
use verbb\formie\events\FieldElementEvent;
use verbb\formie\fields\FileUpload;
use yii\base\Event;

Event::on(FileUpload::class, FileUpload::EVENT_BEFORE_ELEMENT_DELETE, function(FieldElementEvent $event) {
    $field = $event->sender;
    $element = $event->element;

    // Prevent the field value from being deleted.
    // $event->isValid = false;
});
```

### The `afterElementDelete` event
The event that is triggered after a field value is deleted from an element.

```php
use verbb\formie\events\FieldElementEvent;
use verbb\formie\fields\FileUpload;
use yii\base\Event;

Event::on(FileUpload::class, FileUpload::EVENT_AFTER_ELEMENT_DELETE, function(FieldElementEvent $event) {
    $field = $event->sender;
    $element = $event->element;
    // ...
});
```

### The `beforeElementRestore` event
The event that is triggered before a field value is restored for an element.

```php
use verbb\formie\events\FieldElementEvent;
use verbb\formie\fields\FileUpload;
use yii\base\Event;

Event::on(FileUpload::class, FileUpload::EVENT_BEFORE_ELEMENT_RESTORE, function(FieldElementEvent $event) {
    $field = $event->sender;
    $element = $event->element;
    // ...
});
```

### The `afterElementRestore` event
The event that is triggered after a field value is restored for an element.

```php
use verbb\formie\events\FieldElementEvent;
use verbb\formie\fields\FileUpload;
use yii\base\Event;

Event::on(FileUpload::class, FileUpload::EVENT_AFTER_ELEMENT_RESTORE, function(FieldElementEvent $event) {
    $field = $event->sender;
    $element = $event->element;
    // ...
});
```

## Field Events

### The `modifyDefaultValue` event
The event that is triggered when preparing a field's default value. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\SingleLineText;
use verbb\formie\events\ModifyFieldValueEvent;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_DEFAULT_VALUE, function(ModifyFieldValueEvent $event) {
    $field = $event->field;
    $value = $event->value;

    // Overwrite the value
    $event->value = 'My Custom Value';
});
```

### The `modifySlotTag` event
The event that is triggered when preparing a field slot tag for rendering. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `tag` event property to change how a field is rendered.

The older `modifyHtmlTag` event name remains as a deprecated alias.

For more examples, consult the [Theme Config](/theming/theme-config) docs.

```php
use verbb\formie\fields\SingleLineText;
use verbb\formie\events\ModifyFieldSlotTagEvent;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_SLOT_TAG, function(ModifyFieldSlotTagEvent $event) {
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
The event that is triggered when preparing a field's value to be represented as a string. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\SingleLineText;
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
The event that is triggered when preparing a field's value to be represented as a JSON object. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\SingleLineText;
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

### The `modifyValueAsArray` event
The event that is triggered when preparing a field's value to be represented as an array. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\SingleLineText;
use verbb\formie\events\ModifyFieldValueEvent;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_VALUE_AS_ARRAY, function(ModifyFieldValueEvent $event) {
    $field = $event->field;
    $value = $event->value;
    $submission = $event->submission;

    // Overwrite the value
    $event->value = ['My Custom Value'];
});
```

### The `modifyValueForExport` event
The event that is triggered when preparing a field's value to be exported. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\MultiLineText;
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
The event that is triggered when preparing a field's value to be used in integrations. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\MultiLineText;
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
The event that is triggered when preparing a field's value to be represented in the Summary field. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\Dropdown;
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

### The `modifyValueForEmail` event
The event that is triggered when preparing a field's value to be used in an Email Notification. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\Dropdown;
use verbb\formie\events\ModifyFieldEmailValueEvent;
use yii\base\Event;

Event::on(Dropdown::class, Dropdown::EVENT_MODIFY_VALUE_FOR_EMAIL, function(ModifyFieldEmailValueEvent $event) {
    $field = $event->field;
    $value = $event->value;
    $submission = $event->submission;

    // Overwrite the value
    $event->value = 'My Custom Value';
});
```

### The `modifyValueForEmailPreview` event
The event that is triggered when preparing a field's value to be used in an Email Notification preview. You can use this on any class that extends the `verbb\formie\base\Field` class.

Modify the `value` event property to set the value used.

```php
use verbb\formie\fields\MultiLineText;
use verbb\formie\events\ModifyFieldEmailValueEvent;
use yii\base\Event;

Event::on(MultiLineText::class, MultiLineText::EVENT_MODIFY_VALUE_FOR_EMAIL_PREVIEW, function(ModifyFieldEmailValueEvent $event) {
    $event->value = $event->faker->realText;
});
```

## Address Field Events

### The `modifyNestedFieldLayout` event
The event that is triggered to modify the nested field layout for the field.

```php
use verbb\formie\events\ModifyNestedFieldLayoutEvent;
use verbb\formie\fields\Address;
use yii\base\Event;

Event::on(Address::class, Address::EVENT_MODIFY_NESTED_FIELD_LAYOUT, function(ModifyNestedFieldLayoutEvent $event) {
    // Lookup the last name sub-field. We can no longer rely on a static order
    $address1 = $event->fieldLayout->getFieldByHandle('address1');

    // Modify the `address1` field - a `SingleLineText` field.
    $address1->label = 'Address Line 1';
});
```

### The `modifyAddressCountries` event
The event that is triggered to modify the available countries the address field has access to.

```php
use verbb\formie\events\ModifyAddressCountriesEvent;
use verbb\formie\services\Countries;
use yii\base\Event;

Event::on(Countries::class, Countries::EVENT_MODIFY_ADDRESS_COUNTRIES, function(ModifyAddressCountriesEvent $event) {
    $countries = $event->countries;
    // ...
});
```

## Date Field Events

### The `modifyDateFormat` event
The event that is triggered to modify the Date Format for the field.

```php
use verbb\formie\events\ModifyDateTimeFormatEvent;
use verbb\formie\fields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_MODIFY_DATE_FORMAT, function(ModifyDateTimeFormatEvent $event) {
    $event->dateFormat = 'Y-m-d';
});
```

### The `modifyTimeFormat` event
The event that is triggered to modify the Time Format for the field.

```php
use verbb\formie\events\ModifyDateTimeFormatEvent;
use verbb\formie\fields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_MODIFY_TIME_FORMAT, function(ModifyDateTimeFormatEvent $event) {
    $event->timeFormat = 'H:i';
});
```

### The `registerDateFormatOptions` event
The event that is triggered to register the available options to select for date formatting.

```php
use verbb\formie\events\RegisterDateTimeFormatOptionsEvent;
use verbb\formie\fields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_REGISTER_DATE_FORMAT_OPTIONS, function(RegisterDateTimeFormatOptionsEvent $event) {
    $event->options[] = ['label' => 'Standard Formatting', 'value' => 'Y-m-d'];
});
```

### The `registerTimeFormatOptions` event
The event that is triggered to register the available options to select for time formatting.

```php
use verbb\formie\events\RegisterDateTimeFormatOptionsEvent;
use verbb\formie\fields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_REGISTER_TIME_FORMAT_OPTIONS, function(RegisterDateTimeFormatOptionsEvent $event) {
    $event->options[] = ['label' => 'Standard Formatting', 'value' => 'H:i:s'];
});
```

### The `modifyNestedFieldLayout` event
The event that is triggered to modify the nested field layout for the field.

```php
use verbb\formie\events\ModifyNestedFieldLayoutEvent;
use verbb\formie\fields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_MODIFY_NESTED_FIELD_LAYOUT, function(ModifyNestedFieldLayoutEvent $event) {
    // Lookup the last name sub-field. We can no longer rely on a static order
    $year = ArrayHelper::firstWhere($event->fields, 'handle', 'year');

    $yearOptions = [];
    $minYear = 2000;
    $maxYear = 2050;

    for ($y = $minYear; $y < $maxYear; ++$y) {
        $yearOptions[] = ['value' => $y, 'label' => $y];
    }

    // Modify the `year` field - a `SingleLineText` field.
    $year->options = $yearOptions;
    $year->min = $minYear;
    $year->max = $maxYear;
});
```

## Email Field Events

### The `modifyUniqueQuery` event
The event that is triggered to modify the Submission query that determines whether this email is unique. You can modify the query to add your own logic.

```php
use verbb\formie\events\ModifyFieldUniqueQueryEvent;
use verbb\formie\fields\Email;
use yii\base\Event;

Event::on(Email::class, Email::EVENT_MODIFY_UNIQUE_QUERY, function(ModifyFieldUniqueQueryEvent $event) {
    $query = $event->query;
    $field = $event->field;
    // ...

    // e.g. change the query to only look at today's submissions, to allow only one per day.
    $event->query->andWhere('DATE(e.dateCreated) = UTC_DATE()');
});
```

### The `modifyUniqueUserQuery` event
The event that is triggered to modify the Craft user query that determines whether an email address is already associated with a user account. You can modify the query to add your own logic.

```php
use verbb\formie\events\ModifyFieldUniqueUserQueryEvent;
use verbb\formie\fields\Email;
use yii\base\Event;

Event::on(Email::class, Email::EVENT_MODIFY_UNIQUE_USER_QUERY, function(ModifyFieldUniqueUserQueryEvent $event) {
    $query = $event->query;
    $field = $event->field;
    $element = $event->element;
    // ...

    // e.g. allow the currently logged-in user to submit their own email address.
    if (($userId = Craft::$app->getUser()->getId()) !== null) {
        $event->query->andWhere(['not', ['users.id' => $userId]]);
    }
});
```

## Element Field Events

### The `modifyElementFieldQuery` event
The event that is triggered to modify the query for element fields, for when rendering options on the front-end.

```php
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\fields\Entries;
use yii\base\Event;

Event::on(Entries::class, Entries::EVENT_MODIFY_ELEMENT_QUERY, function(ModifyElementFieldQueryEvent $event) {
    $query = $event->query;
    $field = $event->field;
    // ...
});
```

### The `defineSelectionCriteria` event
The event that is triggered to define additional selection criteria for element fields.

```php
use craft\events\ElementCriteriaEvent;
use verbb\formie\fields\Entries;
use yii\base\Event;

Event::on(Entries::class, Entries::EVENT_DEFINE_SELECTION_CRITERIA, function(ElementCriteriaEvent $event) {
    $event->criteria['section'] = 'news';
});
```

## HTML Field Events

### The `modifyPurifierConfig` event
The event that is triggered to modify the HTML Purifier config.

```php
use verbb\formie\events\ModifyPurifierConfigEvent;
use verbb\formie\fields\Html;
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
use Craft;
use verbb\formie\events\ModifyNamePrefixOptionsEvent;
use verbb\formie\fields\subfields\NamePrefix;
use yii\base\Event;

Event::on(NamePrefix::class, NamePrefix::EVENT_MODIFY_PREFIX_OPTIONS, function(ModifyNamePrefixOptionsEvent $event) {
    $event->options[] = ['label' => Craft::t('formie', 'Mx.'), 'value' => 'mx'];
});
```

### The `modifyNestedFieldLayout` event
The event that is triggered to modify the nested field layout for the field.

```php
use verbb\formie\events\ModifyNestedFieldLayoutEvent;
use verbb\formie\fields\Name;
use yii\base\Event;

Event::on(Name::class, Name::EVENT_MODIFY_NESTED_FIELD_LAYOUT, function(ModifyNestedFieldLayoutEvent $event) {
    // Lookup the last name sub-field. We can no longer rely on a static order
    $lastName = $event->fieldLayout->getFieldByHandle('lastName');

    // Modify the `lastName` field - a `SingleLineText` field.
    $lastName->label = 'Surname';
});
```

## Phone Field Events

### The `modifyPhoneCountries` event
The event that is triggered to modify the available countries the phone field has access to.

```php
use verbb\formie\events\ModifyPhoneCountriesEvent;
use verbb\formie\services\Countries;
use yii\base\Event;

Event::on(Countries::class, Countries::EVENT_MODIFY_PHONE_COUNTRIES, function(ModifyPhoneCountriesEvent $event) {
    $countries = $event->countries;
    // ...
});
```

## Predefined Field Options

### The `registerPredefinedOptions` event
The event that is triggered for registering predefined options for Dropdown, Radio Button and Checkboxes fields.

```php
use verbb\formie\events\RegisterPredefinedOptionsEvent;
use verbb\formie\services\OptionSources;
use yii\base\Event;

Event::on(OptionSources::class, OptionSources::EVENT_REGISTER_PREDEFINED_OPTIONS, function(RegisterPredefinedOptionsEvent $event) {
    $event->options[] = CustomOptions::class;
});
```
