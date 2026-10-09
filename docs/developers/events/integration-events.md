# Integration Events

## Integration Model Events

### The `registerFormieIntegrations` Event
The event that is triggered for registering integrations.

```php
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
    $event->addressProviders[] = ExampleAddressProvider::class;
    $event->automations[] = ExampleAutomation::class;
    $event->captchas[] = ExampleCaptcha::class;
    $event->crm[] = ExampleCrm::class;
    $event->elements[] = ExampleElement::class;
    $event->emailMarketing[] = ExampleEmailMarketing::class;
    $event->helpDesk[] = ExampleHelpDesk::class;
    $event->messaging[] = ExampleMessaging::class;
    $event->miscellaneous[] = ExampleMiscellaneous::class;
    $event->payments[] = ExamplePayment::class;
    // ...
});
```

### The `modifyFormIntegrations` Event
The event that is triggered when all enabled integrations for a form is prepared for the front-end. This does not change the settings shown for the integration in the form builder.

```php
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, function(ModifyFormIntegrationsEvent $event) {
    $integrations = $event->integrations;
    // ...
});
```

### The `modifyFormIntegration` Event
The event that is triggered when an integration instance is created for a form. If you want to modify the settings of an integration for a form, this would be a good event to do so.

```php
use verbb\formie\events\ModifyFormIntegrationEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATION, function(ModifyFormIntegrationEvent $event) {
    $integration = $event->integration;
    // ...
});
```

### The `beforeSaveIntegration` Event
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

### The `afterSaveIntegration` Event
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

### The `beforeDeleteIntegration` Event
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

### The `beforeApplyIntegrationDelete` Event
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

### The `afterDeleteIntegration` Event
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
The below events are examples using the `Mailchimp` class, but any class that inherits from the `verbb\formie\base\Integration` class can use these events.

### The `beforeSendPayload` Event
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

### The `afterSendPayload` Event
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

### The `modifyPaymentPayload` Event
The event that is triggered for Payment integrations, before sends its payload to the provider. Each provider may provide different events and objects to modify as part of the payment process.

```php
use verbb\formie\events\ModifyPaymentPayloadEvent;
use verbb\formie\integrations\payments\Stripe;
use yii\base\Event;

Event::on(Stripe::class, Stripe::EVENT_MODIFY_SINGLE_PAYLOAD, function(ModifyPaymentPayloadEvent $event) {
    $submission = $event->submission;
    $payload = $event->payload;
    $integration = $event->integration;

    // Modify the payload sent to Stripe for a single payment
});

Event::on(Stripe::class, Stripe::EVENT_MODIFY_SUBSCRIPTION_PAYLOAD, function(ModifyPaymentPayloadEvent $event) {
    $submission = $event->submission;
    $payload = $event->payload;
    $integration = $event->integration;

    // Modify the payload sent to Stripe for a subscription payment
});

Event::on(Stripe::class, Stripe::EVENT_MODIFY_PLAN_PAYLOAD, function(ModifyPaymentPayloadEvent $event) {
    $submission = $event->submission;
    $payload = $event->payload;
    $integration = $event->integration;

    // Modify the payload sent to Stripe for a subscription payment, when creating the plan
});
```

### The `beforeValidateSubmission` Event
The event that is triggered before a captcha integration validates a submission.

```php
use verbb\formie\events\CaptchaValidateSubmissionEvent;
use verbb\formie\integrations\captchas\Recaptcha;
use yii\base\Event;

Event::on(Recaptcha::class, Recaptcha::EVENT_BEFORE_VALIDATE_SUBMISSION, function(CaptchaValidateSubmissionEvent $event) {
    $submission = $event->submission;

    // Prevent this captcha validation from passing.
    // $event->isValid = false;
});
```

### The `afterValidateSubmission` Event
The event that is triggered after a captcha integration validates a submission.

```php
use verbb\formie\events\CaptchaValidateSubmissionEvent;
use verbb\formie\integrations\captchas\Recaptcha;
use yii\base\Event;

Event::on(Recaptcha::class, Recaptcha::EVENT_AFTER_VALIDATE_SUBMISSION, function(CaptchaValidateSubmissionEvent $event) {
    $submission = $event->submission;
    $success = $event->success;
    // ...
});
```

### The `beforeProcessPayment` Event
Runs before Formie creates a payment intent or calls the provider. Set `isValid` to `false` to skip payment processing.

```php
use verbb\formie\events\PaymentIntegrationProcessEvent;
use verbb\formie\integrations\payments\Stripe;
use yii\base\Event;

Event::on(Stripe::class, Stripe::EVENT_BEFORE_PROCESS_PAYMENT, function(PaymentIntegrationProcessEvent $event) {
    $submission = $event->submission;
    $integration = $event->integration;

    // Prevent this payment from processing.
    // $event->isValid = false;
});
```

### The `afterProcessPayment` Event
Runs after processing a payment, including when Formie reuses a stored success to retry submission completion. `result` is `true` for a successful payment and `false` for other outcomes. Set `isValid` to `false` to block local completion. This does not undo a charge or change the stored financial result. Failed, cancelled and uncertain payments retain their outcome.

```php
use verbb\formie\events\PaymentIntegrationProcessEvent;
use verbb\formie\integrations\payments\Stripe;
use yii\base\Event;

Event::on(Stripe::class, Stripe::EVENT_AFTER_PROCESS_PAYMENT, function(PaymentIntegrationProcessEvent $event) {
    $submission = $event->submission;
    $integration = $event->integration;
    $result = $event->result;
    // ...
});
```

### The `modifyCurrencyOptions` Event
The event that is triggered when payment currency options are prepared.

```php
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\integrations\payments\Stripe;
use yii\base\Event;

Event::on(Stripe::class, Stripe::EVENT_MODIFY_CURRENCY_OPTIONS, function(ModifyPaymentCurrencyOptionsEvent $event) {
    $event->currencies[] = [
        'label' => 'Australian Dollar',
        'value' => 'AUD',
    ];
});
```

## Payment Model Events

### The `beforeSavePayment` Event
The event that is triggered before a payment record is saved.

```php
use verbb\formie\events\PaymentEvent;
use verbb\formie\services\Payments;
use yii\base\Event;

Event::on(Payments::class, Payments::EVENT_BEFORE_SAVE_PAYMENT, function(PaymentEvent $event) {
    $payment = $event->payment;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSavePayment` Event
The event that is triggered after a payment record is saved.

```php
use verbb\formie\events\PaymentEvent;
use verbb\formie\services\Payments;
use yii\base\Event;

Event::on(Payments::class, Payments::EVENT_AFTER_SAVE_PAYMENT, function(PaymentEvent $event) {
    $payment = $event->payment;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeletePayment` Event
The event that is triggered before a payment record is deleted.

```php
use verbb\formie\events\PaymentEvent;
use verbb\formie\services\Payments;
use yii\base\Event;

Event::on(Payments::class, Payments::EVENT_BEFORE_DELETE_PAYMENT, function(PaymentEvent $event) {
    $payment = $event->payment;
    // ...
});
```

### The `afterDeletePayment` Event
The event that is triggered after a payment record is deleted.

```php
use verbb\formie\events\PaymentEvent;
use verbb\formie\services\Payments;
use yii\base\Event;

Event::on(Payments::class, Payments::EVENT_AFTER_DELETE_PAYMENT, function(PaymentEvent $event) {
    $payment = $event->payment;
    // ...
});
```

## Payment Plan Events

### The `beforeSavePlan` Event
The event that is triggered before a payment plan is saved.

```php
use verbb\formie\events\PlanEvent;
use verbb\formie\services\Plans;
use yii\base\Event;

Event::on(Plans::class, Plans::EVENT_BEFORE_SAVE_PLAN, function(PlanEvent $event) {
    $plan = $event->plan;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSavePlan` Event
The event that is triggered after a payment plan is saved.

```php
use verbb\formie\events\PlanEvent;
use verbb\formie\services\Plans;
use yii\base\Event;

Event::on(Plans::class, Plans::EVENT_AFTER_SAVE_PLAN, function(PlanEvent $event) {
    $plan = $event->plan;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeletePlan` Event
The event that is triggered before a payment plan is deleted.

```php
use verbb\formie\events\PlanEvent;
use verbb\formie\services\Plans;
use yii\base\Event;

Event::on(Plans::class, Plans::EVENT_BEFORE_DELETE_PLAN, function(PlanEvent $event) {
    $plan = $event->plan;
    // ...
});
```

### The `afterDeletePlan` Event
The event that is triggered after a payment plan is deleted.

```php
use verbb\formie\events\PlanEvent;
use verbb\formie\services\Plans;
use yii\base\Event;

Event::on(Plans::class, Plans::EVENT_AFTER_DELETE_PLAN, function(PlanEvent $event) {
    $plan = $event->plan;
    // ...
});
```

### The `archivePlan` Event
The event that is triggered when a payment plan is archived.

```php
use verbb\formie\events\PlanEvent;
use verbb\formie\services\Plans;
use yii\base\Event;

Event::on(Plans::class, Plans::EVENT_ARCHIVE_PLAN, function(PlanEvent $event) {
    $plan = $event->plan;
    // ...
});
```

## Subscription Events

### The `beforeSaveSubscription` Event
The event that is triggered before a subscription is saved.

```php
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\services\Subscriptions;
use yii\base\Event;

Event::on(Subscriptions::class, Subscriptions::EVENT_BEFORE_SAVE_SUBSCRIPTION, function(SubscriptionEvent $event) {
    $subscription = $event->subscription;
    $isNew = $event->isNew;
    // ...
});
```

### The `afterSaveSubscription` Event
The event that is triggered after a subscription is saved.

```php
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\services\Subscriptions;
use yii\base\Event;

Event::on(Subscriptions::class, Subscriptions::EVENT_AFTER_SAVE_SUBSCRIPTION, function(SubscriptionEvent $event) {
    $subscription = $event->subscription;
    $isNew = $event->isNew;
    // ...
});
```

### The `beforeDeleteSubscription` Event
The event that is triggered before a subscription is deleted.

```php
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\services\Subscriptions;
use yii\base\Event;

Event::on(Subscriptions::class, Subscriptions::EVENT_BEFORE_DELETE_SUBSCRIPTION, function(SubscriptionEvent $event) {
    $subscription = $event->subscription;
    // ...
});
```

### The `afterDeleteSubscription` Event
The event that is triggered after a subscription is deleted.

```php
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\services\Subscriptions;
use yii\base\Event;

Event::on(Subscriptions::class, Subscriptions::EVENT_AFTER_DELETE_SUBSCRIPTION, function(SubscriptionEvent $event) {
    $subscription = $event->subscription;
    // ...
});
```

### The `afterExpireSubscription` Event
The event that is triggered after a subscription expires.

```php
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\services\Subscriptions;
use yii\base\Event;

Event::on(Subscriptions::class, Subscriptions::EVENT_AFTER_EXPIRE_SUBSCRIPTION, function(SubscriptionEvent $event) {
    $subscription = $event->subscription;
    // ...
});
```

### The `beforeUpdateSubscription` Event
The event that is triggered before a subscription is updated.

```php
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\services\Subscriptions;
use yii\base\Event;

Event::on(Subscriptions::class, Subscriptions::EVENT_BEFORE_UPDATE_SUBSCRIPTION, function(SubscriptionEvent $event) {
    $subscription = $event->subscription;
    // ...
});
```

### The `receiveSubscriptionPayment` Event
The event that is triggered when a subscription receives a payment.

```php
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\services\Subscriptions;
use yii\base\Event;

Event::on(Subscriptions::class, Subscriptions::EVENT_RECEIVE_SUBSCRIPTION_PAYMENT, function(SubscriptionEvent $event) {
    $subscription = $event->subscription;
    // ...
});
```

### The `afterApplySubscriptionSnapshot` Event
The event that is triggered after a current provider snapshot has been accepted and persisted. Duplicate events, stale observations and attempted terminal regressions do not trigger it. The event exposes the previous and current canonical status, immutable provider snapshot and source label.

```php
use verbb\formie\events\SubscriptionEvent;
use verbb\formie\services\Subscriptions;
use yii\base\Event;

Event::on(Subscriptions::class, Subscriptions::EVENT_AFTER_APPLY_SUBSCRIPTION_SNAPSHOT, function(SubscriptionEvent $event) {
    $subscription = $event->subscription;
    $previousStatus = $event->previousStatus;
    $currentStatus = $event->currentStatus;
    $providerStatus = $event->snapshot?->providerStatus;
    $source = $event->source;
    // ...
});
```

## Integration Connection Events

The following events use the `Mailchimp` class as an example, but any class that inherits from the `verbb\formie\base\Integration` class can use these events.

### The `beforeCheckConnection` Event
The event that is triggered before an integration has checked its connection.

The `isValid` event property can be set to `false` to prevent the connection check.

```php
use verbb\formie\events\IntegrationConnectionEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_BEFORE_CHECK_CONNECTION, function(IntegrationConnectionEvent $event) {
    $integration = $event->integration;
    // ...
});
```

### The `afterCheckConnection` Event
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

## Integration Config Events

The following events use the `Mailchimp` class as an example, but any class that inherits from the `verbb\formie\base\Integration` class can use these events.


### The `beforeFetchConfig` Event

The event that is triggered before an integration fetches the metadata used to configure it in the form builder.

The `isValid` event property can be set to `false` to prevent the config refresh.

```php
use verbb\formie\events\IntegrationConfigEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_BEFORE_FETCH_CONFIG, function(IntegrationConfigEvent $event) {
    $integration = $event->integration;
    // ...
});
```

### The `afterFetchConfig` Event

The event that is triggered after an integration fetches its configuration metadata.

```php
use verbb\formie\events\IntegrationConfigEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_AFTER_FETCH_CONFIG, function(IntegrationConfigEvent $event) {
    $integration = $event->integration;
    $config = $event->config;
    // ...
});
```

### The `modifyMappedFieldValue` Event
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

### The `modifyMappedFieldValues` Event
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

### The `modifyIntegrationFormSettingsSchema` Event
The event that is triggered to allow modification of an integration's form settings schema.

```php
use verbb\formie\events\ModifyIntegrationFormSettingsSchemaEvent;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use yii\base\Event;

Event::on(Mailchimp::class, Mailchimp::EVENT_MODIFY_INTEGRATION_FORM_SETTINGS_SCHEMA, function(ModifyIntegrationFormSettingsSchemaEvent $event) {
    $schema = $event->schema;
    $integration = $event->integration;
    $form = $event->form;
    // ...
});
```

### The `modifySlotTag` Event
The event that is triggered when preparing an integration slot tag for rendering.

This is most useful for integrations that participate in field rendering, such as payment integrations. Modify the `tag` event property to change how the integration-provided slot is rendered.

```php
use verbb\formie\events\ModifyIntegrationSlotTagEvent;
use verbb\formie\integrations\payments\Stripe;
use yii\base\Event;

Event::on(Stripe::class, Stripe::EVENT_MODIFY_SLOT_TAG, function(ModifyIntegrationSlotTagEvent $event) {
    $integration = $event->integration;
    $tag = $event->tag;
    $key = $event->key;
    $context = $event->context;

    if ($event->key === 'cardNumber') {
        $event->tag->attributes['class'][] = 'my-payment-input';
    }
});
```

## Element Integration Events

### The `modifyElementFields` Event
The event that is triggered for an Element integration, which returns the available fields to map Formie field values to for the element.

```php
use verbb\formie\events\ModifyElementFieldsEvent;
use verbb\formie\integrations\elements\Entry;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_MODIFY_ELEMENT_FIELDS, function(ModifyElementFieldsEvent $event) {
    $fieldLayout = $event->fieldLayout;
    $fields = $event->fields;
    // ...
});
```

### The `modifyElementMatch` Event
The event that is triggered for an Element integration, when matching against an existing element. This determines whether the integration should create a new element, or update an existing one.

```php
use verbb\formie\events\ModifyElementMatchEvent;
use verbb\formie\integrations\elements\Entry;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_MODIFY_ELEMENT_MATCH, function(ModifyElementMatchEvent $event) {
    $elementType = $event->elementType;
    $identifier = $event->identifier;
    $submission = $event->submission;
    $criteria = $event->criteria;
    $element = $event->element;
    // ...
});
```

## Microsoft Dynamics 365 Events

### The `modifyEntities` Event
The event that is triggered when Formie defines the Microsoft Dynamics 365 entities available for field mapping and record creation. Add a custom entity here to give each form its own enable switch and field mapping interface without creating a custom integration class.

Use a stable array key as the entity handle. The `logicalName` identifies the entity in the Dynamics metadata API. Formie can discover `entitySetName` and `primaryIdAttribute` when the mapping is refreshed, or you can provide them explicitly. `deliveryOrder` controls when the record is created relative to other enabled entities.

```php
use verbb\formie\events\MicrosoftDynamics365EntitiesEvent;
use verbb\formie\integrations\crm\MicrosoftDynamics365;
use verbb\formie\models\MicrosoftDynamics365Entity;
use yii\base\Event;

Event::on(MicrosoftDynamics365::class, MicrosoftDynamics365::EVENT_MODIFY_ENTITIES, function(MicrosoftDynamics365EntitiesEvent $event) {
    $event->entities['event'] = new MicrosoftDynamics365Entity([
        'label' => 'Event',
        'pluralLabel' => 'Events',
        'logicalName' => 'new_event',
        'entitySetName' => 'new_events',
        'primaryIdAttribute' => 'new_eventid',
        'deliveryOrder' => 60,
    ]);
});
```

The entity set name and primary ID attribute are optional. Providing them avoids an additional metadata lookup if a form delivers before its mapping has been refreshed.

For integrations with specialised delivery logic, extend `MicrosoftDynamics365` and override `defineEntities()`. Entities added there and through this event use the same mapping, validation and delivery pipeline.

### The `modifyRequiredLevels` Event
The event that is triggered to allow modification of the fields that are marked as required during field mapping in the model.

Microsoft Dynamics 365 has [four AttributeRequiredLevel values](https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/reference/attributerequiredlevel). By default `SystemRequired` and `ApplicationRequired` fields will be marked as mandatory in the field mapping model. You can override this, by passing a specific array of values.

You should always provide at minimum `SystemRequired` fields to avoid API errors. `ApplicationRequired` values can technically be bypassed through the API, but will be defined as Business Rules within Microsoft Dynamics 365. You can also do the reverse and also force `Recommended` values, however this will usually require a lot of unnecessary fields to be mapped.

```php
use verbb\formie\events\MicrosoftDynamics365RequiredLevelsEvent;
use verbb\formie\integrations\crm\MicrosoftDynamics365;
use yii\base\Event;

Event::on(MicrosoftDynamics365::class, MicrosoftDynamics365::EVENT_MODIFY_REQUIRED_LEVELS, function(MicrosoftDynamics365RequiredLevelsEvent $event) {
    // Set required level to SystemRequired only
    $event->requiredLevels = ['SystemRequired'];
});
```

### The `modifyTargetSchemas` Event
The event that is triggered to allow modification of the target schemas when populating lookup/relational fields in the field mapping.

By default Formie populates lookup/relational field values of entities discovered from the metadata of the Microsoft Dynamics 365 environment, but this event gives you a chance to modify both standard and custom entities to customise the values shown in the field mapping.

Any modifications made to the standard entities such as `systemuser` or `campaign` will be merged, the values set in this event take priority.

The following additional parameters are available for each entity for further customisation:

* `expand` - For performing more complex queries related to one or more entities.
* `filter` - Specify a filter criteria to modify the returned values.
* `limit` - Set the amount of values to return (defaults to 100 if not specifically set).
* `orderby` - Order the returned values by a specific criteria e.g. `name asc`.
* `select` - Array of values to return in the lookup e.g. `['fullname', 'systemuserid']`

When using the `limit` option and increasing the amount of values returned, be mindful of the performance impact when refreshing the integration.

```php
use verbb\formie\events\MicrosoftDynamics365TargetSchemasEvent;
use verbb\formie\integrations\crm\MicrosoftDynamics365;
use yii\base\Event;

Event::on(MicrosoftDynamics365::class, MicrosoftDynamics365::EVENT_MODIFY_TARGET_SCHEMAS, function(MicrosoftDynamics365TargetSchemasEvent $event) {
    $event->targetSchemas = [
        // Filter to modify the returned values for the systemuser entity
        'systemuser' => [
            'filter' => 'isdisabled eq false and invitestatuscode eq 4 or accessmode eq 4',
        ],
        // Set the orderby criteria be something more logical for this entity and allow more than 100 values
        'campaign' => [
            'orderby' => 'createdon desc',
            'limit' => 150
        ],
        // A custom entity with a custom orderby and filter
        'ccl1000_enquirytype' => [
            'orderby' => 'ccl1000_name asc',
            'filter' => 'statecode eq 0',
        ]
    ];
});
```

## Automations Integration Events

### The `modifyAutomationPayload` Event
The event that is triggered to allow modification of the payload sent to your defined automation URL.

```php
use verbb\formie\events\ModifyAutomationPayloadEvent;
use verbb\formie\integrations\automations\Zapier;
use yii\base\Event;

Event::on(Zapier::class, Zapier::EVENT_MODIFY_AUTOMATION_PAYLOAD, function(ModifyAutomationPayloadEvent $event) {
    $payload = $event->payload;
    $submission = $event->submission;
    // ...
});
```

## Miscellaneous Integration Events

### The `modifyMiscellaneousPayload` Event
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

## Semantic Delivery Events

Subscribe to `IntegrationRunner::EVENT_EVALUATED`, `EVENT_SKIPPED`, `EVENT_RESULT`, `EVENT_QUEUED` and `EVENT_BATCH_COMPLETED`; to `DeliveryAttempts::EVENT_OPERATION_START`, `EVENT_ATTEMPT_COMPLETED`, `EVENT_RETRY_DECISION` and `EVENT_RECONCILIATION`; or to `IntegrationDispatcher::EVENT_FINALIZED` when all configured integrations meet the form's terminal policy. Queueing is a scheduling event, not remote completion.

These events receive `IntegrationDeliveryEvent`, with immutable `context`, optional normalized `result`, optional `batch` and the durable `attemptUid` where applicable. Read status, eligibility, execution identity, reason and explicit force overrides from these contracts. They do not require access to mutable provider internals or queue payloads. A batch preserves individual results; `EVENT_FINALIZED` is withheld for an unknown outcome and is checkpointed once per execution identity. Existing before/after payload hooks remain available for provider-specific payload modification.

`IntegrationRunner::EVENT_RESULT` fires once for each normalized run result, including unmet conditions, invalid settings, missing or disabled integrations, steps stopped by policy, unresolved previous delivery and normalized provider exceptions. A skipped run also emits `EVENT_SKIPPED` before its result event. Denied force authority and invalid retry authority throw before a run is authorized; they are not reported as completed deliveries. A lock-contention result has no durable attempt UID.

```php
use verbb\formie\events\IntegrationDeliveryEvent;
use verbb\formie\services\DeliveryAttempts;
use yii\base\Event;

Event::on(DeliveryAttempts::class, DeliveryAttempts::EVENT_ATTEMPT_COMPLETED, function(IntegrationDeliveryEvent $event) {
    $status = $event->result->status->value;
    $submissionId = $event->context->submissionId;
    // Update local monitoring using the durable attempt UID.
});
```
