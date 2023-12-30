# Upgrading from v2
While the [changelog](https://github.com/verbb/formie/blob/craft-5/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## General

Old | What to do instead
--- | ---
| `Formie::log` | `Formie::info`


## Repeater & Group Fields
Repeater and Group fields have had a major overhaul in Formie 3, most notably that their values are no longer elements. This is to simplify the field, provide a lower learning curve to dealing with them, and performance. They also don't use nested field layouts for the same goals. Instead, they are simple arrays.

This should only effect users who customise behaviour of these fields, or when working with the values of these fields. However, it should be much more simplified to do so.

```twig
// Formie v2
{% set groupValue = myGroupField.one() %}
{{ groupValue.innerField1 }}
{{ groupValue.innerField2 }}

{% set repeaterValue = myRepeaterField.all() %}

{% for row in repeaterValue %}
    {{ row.innerField1 }}
    {{ row.innerField2 }}
{% endfor %}

// Formie v3
{{ myGroupField.innerField1 }}
{{ myGroupField.innerField2 }}

{% for row in myRepeaterField %}
    {{ row.innerField1 }}
    {{ row.innerField2 }}
{% endfor %}
```

In addition, you can also now access field value directly from the submission, with dot-notation syntax.

```twig
{{ submission.getFieldValue('multiName.firstName') }}
{{ submission.getFieldValue('myGroupField.innerField1') }}
{{ submission.getFieldValue('myRepeaterField.0.innerField1') }}
{{ submission.getFieldValue('myRepeaterField.1.innerField1') }}
```

The latter example of a Repeater includes the zero-based index of the row you're fetching values for.

## Custom Fields

Old | What to do instead
--- | ---
| `Field::hasSubfields()` | `Field::hasSubFields()`
| `Field::getBaseFieldConfig()` | `Field::getFieldTypeConfig()`
| `Field::getExtraBaseFieldConfig()` | `Field::getFieldTypeConfigData()`
| `Field::getSavedFieldConfig()` | `Field::getFormBuilderConfig()`
| `Field::getFieldDefaults()` | `Field::getFieldTypeConfigDefaults()`
| `Field::getAllFieldDefaults()` | `Field::getFieldTypeConfigDefaults()`
| `Field::getFieldValue()` | `$element->getFieldValue($field->handle)`

### Method Signatures
To adhere to more strict typing introduced in PHP 8, we've modified some method signatures. Your custom field classes will need to be updated to reflect these changes.

```php
// Formie v2 vs Formie v3
public function getInputHtml($value, ElementInterface $element = null): string
protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string

// Formie v2 vs Formie v3
public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
public function normalizeValue(mixed $value, ElementInterface $element = null): mixed

// Formie v2 vs Formie v3
public function getValueForIntegration(mixed $value, $integrationField, $integration, ?ElementInterface $element = null, $fieldKey = ''): mixed
public function getValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ?ElementInterface $element = null, string $fieldKey = ''): mixed

// Formie v2 vs Formie v3
public function getValueForEmail(mixed $value, $notification, ?ElementInterface $element = null): mixed
public function getValueForEmail(mixed $value, Notification $notification, ?ElementInterface $element = null): mixed

// Formie v2 vs Formie v3
public function populateValue($value): void
public function populateValue(mixed $value): void

// Formie v2 vs Formie v3
public function setForm($value): void
public function setForm(?Form $value): void

// Formie v2 vs Formie v3
public function getDefaultValue($attributePrefix = '')
public function getDefaultValue(string $attributePrefix = ''): mixed

// Formie v2 vs Formie v3
public function setNamespace($value): void
public function setNamespace(string|bool|null $value): void

// Formie v2 vs Formie v3
public function getConditionsJson($element = null): ?string
public function getConditionsJson(): ?string

// Formie v2 vs Formie v3
public function isConditionallyHidden(Submission $element): bool
public function isConditionallyHidden(Submission $submission): bool

// Formie v2 vs Formie v3
protected function setPrePopulatedValue($value)
protected function setPrePopulatedValue(mixed $value): mixed

// Formie v2 vs Formie v3
protected function defineValueAsString($value, ElementInterface $element = null): string
protected function defineValueAsString(mixed $value, ElementInterface $element = null): string

// Formie v2 vs Formie v3
protected function defineValueAsJson($value, ElementInterface $element = null): mixed
protected function defineValueAsJson(mixed $value, ElementInterface $element = null): mixed

// Formie v2 vs Formie v3
protected function defineValueForExport($value, ElementInterface $element = null): mixed
protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed

// Formie v2 vs Formie v3
protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed

// Formie v2 vs Formie v3
protected function defineValueForSummary($value, ElementInterface $element = null): string
protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string

// Formie v2 vs Formie v3
protected function defineValueForEmail($value, $notification, ElementInterface $element = null): string
protected function defineValueForEmail(mixed $value, Notification $notification, ElementInterface $element = null): string
```


## Integrations

Old | What to do instead
--- | ---
| `Integration::log` | `Integration::info`
| `Integration::supportsOauthConnection` | `Integration::supportsOAuthConnection`
| `Integration::deliverPayloadRequest` | `Integration::deliverPayload`

### Method Signatures
To adhere to more strict typing introduced in PHP 8, we've modified some method signatures. Your custom integration classes will need to be updated to reflect these changes.

```php
// Formie v2 vs Formie v3
public static function log($integration, $message, $throwError = false): void
public static function info(IntegrationInterface $integration, string $message, bool $throwError = false): void

// Formie v2 vs Formie v3
public static function error($integration, $message, $throwError = false): void
public static function error(IntegrationInterface $integration, string $message, bool $throwError = false): void

// Formie v2 vs Formie v3
public static function apiError($integration, $exception, $throwError = true): void
public static function apiError(IntegrationInterface $integration, Error|Exception $exception, bool $throwError = true): void

// Formie v2 vs Formie v3
public static function convertValueForIntegration($value, $integrationField): mixed
public static function convertValueForIntegration(mixed $value, IntegrationField $integrationField): mixed

// Formie v2 vs Formie v3
public function getFormSettingsHtml($form): string
public function getFormSettingsHtml(Form $form): string

// Formie v2 vs Formie v3
public function setQueueJob($value): void
public function setQueueJob(mixed $value): void

// Formie v2 vs Formie v3
public function setClient($value): void
public function setClient(mixed $value): void

// Formie v2 vs Formie v3
public function checkConnection($useCache = true): bool
public function checkConnection(bool $useCache = true): bool

// Formie v2 vs Formie v3
public function getFormSettings($useCache = true): bool|IntegrationFormSettings
public function getFormSettings(bool $useCache = true): bool|IntegrationFormSettings

// Formie v2 vs Formie v3
public function getFormSettingValue($key)
public function getFormSettingValue(string $key)

// Formie v2 vs Formie v3
public function validateFieldMapping($attribute, $fields = []): void
public function validateFieldMapping(string $attribute, array $fields = []): void

// Formie v2 vs Formie v3
public function request(string $method, string $uri, array $options = [])
public function request(string $method, string $uri, array $options = [], bool $decodeJson = true): mixed

// Formie v2 vs Formie v3
public function deliverPayload($submission, $endpoint, $payload, $method = 'POST', $contentType = 'json')
public function deliverPayload(Submission $submission, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json', bool $decodeJson = true): mixed

// Formie v2 vs Formie v3
public function getFieldMappingValues(Submission $submission, $fieldMapping, $fieldSettings = [])
public function getFieldMappingValues(Submission $submission, array $fieldMapping, mixed $fieldSettings = [])

// Formie v2 vs Formie v3
public function beforeSendPayload(Submission $submission, &$endpoint, &$payload, &$method): bool
public function beforeSendPayload(Submission $submission, string &$endpoint, mixed &$payload, string &$method): bool

// Formie v2 vs Formie v3
public function afterSendPayload(Submission $submission, $endpoint, $payload, $method, $response): bool
public function afterSendPayload(Submission $submission, string $endpoint, mixed $payload, string $method, mixed $response): bool

// Formie v2 vs Formie v3
public function getMappedFieldInfo($mappedFieldValue, $submission): array
public function getMappedFieldInfo(string $mappedFieldValue, Submission $submission): array

// Formie v2 vs Formie v3
public function getMappedFieldValue($mappedFieldValue, $submission, $integrationField)
public function getMappedFieldValue(string $mappedFieldValue, Submission $submission, IntegrationField $integrationField)

// Formie v2 vs Formie v3
private function setCache($values): void
private function setCache(array $values): void

// Formie v2 vs Formie v3
private function getCache($key)
private function getCache(string $key): mixed
```

### Auth Module
To simplify and streamline integration logic for OAuth-based providers, we're using the [Auth Module](https://verbb.io/packages/auth) to handle the authentication flow and token handling. For non-OAuth-based providers, there will be no change.

While things should be seamlessly migrated from Formie to Auth, if you have custom integrations that use OAuth, you'll need to integrate those with the Auth module.

In addition, if you were using any of the OAuth-specific functions in Formie, those are now removed.

The following classes have been removed
- `verbb\formie\events\OauthTokenEvent`
- `verbb\formie\events\TokenEvent`
- `verbb\formie\models\Token`
- `verbb\formie\records\Token`
- `verbb\formie\services\Tokens`

The `formie_tokens` database table still exists, but is no longer used, and will be removed in upcoming Formie versions. Refer to the `auth_oauth_tokens` database table.

For the `Integration` class, the following applies:

- `Integration::tokenId` property removed
- `Integration::getAuthorizeUrl()` method removed
- `Integration::getAccessTokenUrl()` method removed
- `Integration::getResourceOwner()` method removed
- `Integration::getOauthScope()` method removed
- `Integration::getOauthAuthorizationOptions()` method removed
- `Integration::oauthVersion()` method removed
- `Integration::oauth2Legged()` method removed
- `Integration::oauthConnect()` method removed
- `Integration::getOauthProviderConfig()` method removed
- `Integration::getOauthProvider()` method removed
- `Integration::beforeFetchAccessToken()` method removed
- `Integration::afterFetchAccessToken()` method removed


## Field Layouts

### Pages
Pages no longer have a numerical `id`. Please use their `handle` instead, which is automatically generated from their **Label**.

### Rows
In Formie v2 and below, rows were just arrays sitting between Pages and Fields. They are now proper `FormRow` objects.

## Fields
getCustomFields => getFields



```php
// Formie v2
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_MODIFY_FIELD_CONFIG, function(ModifyFieldConfigEvent $event) {
    $config = $event->config;
    // ...
});

// Formie v3
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\fields\formfields\SingleLineText;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_FIELD_CONFIG, function(ModifyFieldConfigEvent $event) {
    $config = $event->config;
    // ...
});
```
