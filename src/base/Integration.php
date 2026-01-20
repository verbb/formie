<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\IntegrationConnectionEvent;
use verbb\formie\events\IntegrationFormSettingsEvent;
use verbb\formie\events\ModifyFieldIntegrationValuesEvent;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\fields\Agree;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\Phone;
use verbb\formie\models\Stencil;
use verbb\formie\records\Integration as IntegrationRecord;

use Craft;
use craft\base\SavableComponent;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\queue\JobInterface;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use craft\web\Response;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

use Error;
use Exception;
use Throwable;

use verbb\auth\Auth;
use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\base\OAuthProviderTrait;
use verbb\auth\models\Token;

abstract class Integration extends SavableComponent implements IntegrationInterface
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SEND_PAYLOAD = 'beforeSendPayload';
    public const EVENT_AFTER_SEND_PAYLOAD = 'afterSendPayload';
    public const EVENT_BEFORE_CHECK_CONNECTION = 'beforeCheckConnection';
    public const EVENT_AFTER_CHECK_CONNECTION = 'afterCheckConnection';
    public const EVENT_BEFORE_FETCH_FORM_SETTINGS = 'beforeFetchFormSettings';
    public const EVENT_AFTER_FETCH_FORM_SETTINGS = 'afterFetchFormSettings';
    public const EVENT_MODIFY_FIELD_MAPPING_VALUES = 'modifyFieldMappingValues';
    public const EVENT_MODIFY_FIELD_MAPPING_VALUE = 'modifyFieldMappingValue';
    
    public const TYPE_ADDRESS_PROVIDER = 'addressProvider';
    public const TYPE_CAPTCHA = 'captcha';
    public const TYPE_ELEMENT = 'element';
    public const TYPE_EMAIL_MARKETING = 'emailMarketing';
    public const TYPE_CRM = 'crm';
    public const TYPE_HELP_DESK = 'helpDesk';
    public const TYPE_MESSAGING = 'messaging';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_AUTOMATION = 'automation';
    public const TYPE_MISC = 'miscellaneous';
    public const TYPE_CUSTOM = 'custom';
    
    public const CATEGORY_ADDRESS_PROVIDERS = 'addressProviders';
    public const CATEGORY_CAPTCHAS = 'captchas';
    public const CATEGORY_ELEMENTS = 'elements';
    public const CATEGORY_EMAIL_MARKETING = 'emailMarketing';
    public const CATEGORY_CRM = 'crm';
    public const CATEGORY_HELP_DESK = 'helpDesk';
    public const CATEGORY_MESSAGING = 'messaging';
    public const CATEGORY_PAYMENTS = 'payments';
    public const CATEGORY_AUTOMATIONS = 'automations';
    public const CATEGORY_MISC = 'miscellaneous';
    public const CATEGORY_CUSTOM = 'custom';

    public const SCENARIO_FORM = 'form';

    public const CONNECT_SUCCESS = 'success';
    public const CONNECT_FAIL = 'fail';


    // Traits
    // =========================================================================

    use OAuthProviderTrait {
        request as OAuthRequest;
    }


    // Static Methods
    // =========================================================================

    public static function className(): string
    {
        $classNameParts = explode('\\', static::class);

        return array_pop($classNameParts);
    }

    public static function isSelectable(): bool
    {
        return false;
    }

    public static function supportsConnection(): bool
    {
        return true;
    }

    public static function supportsOAuthConnection(): bool
    {
        return false;
    }

    public static function supportsPayloadSending(): bool
    {
        return true;
    }

    public static function hasFormSettings(): bool
    {
        return true;
    }

    public static function getRequiredPlugins(): array
    {
        return [];
    }

    public static function info(IntegrationInterface $integration, string $message, bool $throwError = false): void
    {
        Formie::info($integration->name . ': ' . $message);

        if ($throwError) {
            throw new IntegrationException($message);
        }
    }

    public static function error(IntegrationInterface $integration, string $message, bool $throwError = false): void
    {
        Formie::error($integration->name . ': ' . $message);

        if ($throwError) {
            throw new IntegrationException($message);
        }
    }

    public static function apiError(IntegrationInterface $integration, Error|Exception $exception, bool $throwError = true): void
    {
        $messageText = $exception->getMessage();

        // Check for Guzzle errors, which are truncated in the exception `getMessage()`.
        if ($exception instanceof RequestException && $exception->getResponse()) {
            $messageText = (string)$exception->getResponse()->getBody()->getContents();
        }

        $message = Craft::t('formie', 'API error: “{message}” {file}:{line}', [
            'message' => $messageText,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        Formie::error($integration->name . ': ' . $message);

        if ($throwError) {
            throw new IntegrationException($message, 0, $exception);
        }
    }

    public static function convertValueForIntegration(mixed $value, IntegrationField $integrationField): mixed
    {
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            return (is_array($value)) ? $value : [$value];
        }

        if ($integrationField->getType() === IntegrationField::TYPE_DATE) {
            if ($date = DateTimeHelper::toDateTime($value)) {
                return $date->format('Y-m-d');
            }
        }

        if ($integrationField->getType() === IntegrationField::TYPE_DATETIME) {
            if ($date = DateTimeHelper::toDateTime($value)) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        if ($integrationField->getType() === IntegrationField::TYPE_DATECLASS) {
            // Always set the timezone to the system time, so it's properly saves as UTC
            if ($date = DateTimeHelper::toDateTime($value, true)) {
                return $date;
            }
        }

        if ($integrationField->getType() === IntegrationField::TYPE_NUMBER) {
            return (int)$value;
        }

        if ($integrationField->getType() === IntegrationField::TYPE_FLOAT) {
            return (float)$value;
        }

        if ($integrationField->getType() === IntegrationField::TYPE_BOOLEAN) {
            return StringHelper::toBoolean($value);
        }

        if ($integrationField->getType() === IntegrationField::TYPE_PHONE) {
            return Phone::toPhoneString($value);
        }

        // Return the string representation of it by default (also default for integration fields)
        // You could argue we should return `null`, but let's not be too strict on types.
        return $value;
    }


    // Properties
    // =========================================================================

    public ?string $name = null;
    public ?string $handle = null;
    public ?string $type = null;
    public ?int $sortOrder = null;
    public array $cache = [];
    public ?string $uid = null;
    public ?string $optInField = null;

    // Store extra context for when running the integration
    public array $context = [];

    protected ?Client $_client = null;

    // Keep track of whether run in the context of a queue job
    private ?JobInterface $_queueJob = null;
    private bool|string $_enabled = false;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // No longer required, due to Auth module
        unset($config['tokenId']);

        parent::__construct($config);
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();

        // Always have the form's scenario defined, but don't overwrite it
        $scenarios[self::SCENARIO_FORM] = $scenarios[self::SCENARIO_FORM] ?? [];

        return $scenarios;
    }

    public function settingsAttributes(): array
    {
        // These won't be picked up in a Trait
        $attributes = parent::settingsAttributes();

        if (static::supportsOAuthConnection()) {
            $attributes[] = 'clientId';
            $attributes[] = 'clientSecret';
        }

        return $attributes;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getHandle(): string
    {
        return $this->handle ?? '';
    }

    public function getType(): string
    {
        return self::TYPE_CUSTOM;
    }

    public function getClassHandle()
    {
        $classNameParts = explode('\\', static::class);
        $end = array_pop($classNameParts);

        return StringHelper::toKebabCase($end);
    }

    public function getEnabled(bool $parse = true): bool|string
    {
        if ($parse) {
            return App::parseBooleanEnv($this->_enabled) ?? false;
        }

        return $this->_enabled;
    }

    public function setEnabled(bool|string $name): void
    {
        $this->_enabled = $name;
    }

    public function getIconUrl(): string
    {
        return '';
    }

    public function getSettingsHtml(): ?string
    {
        return '';
    }

    public function getSettingsHtmlVariables(): array
    {
        return [
            'integration' => $this,
            'fieldVariables' => [
                'plugin' => 'formie',
                'name' => $this::displayName(),
            ],
        ];
    }

    public function getFormSettingsHtml(Form|Stencil $form): string
    {
        return '';
    }

    public function getFormSettingsHtmlVariables(Form|Stencil $form): array
    {
        return [
            'integration' => $this,
            'form' => $form,
            'fieldVariables' => [
                'plugin' => 'formie',
                'name' => $this::displayName(),
            ],
        ];
    }

    public function hasValidSettings(): bool
    {
        return true;
    }

    public function getQueueJob(): ?JobInterface
    {
        return $this->_queueJob;
    }

    public function setQueueJob(mixed $value): void
    {
        $this->_queueJob = $value;
    }

    public function setClient(mixed $value): void
    {
        $this->_client = $value;
    }

    public function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = $this->defineClient();
    }

    public function extraAttributes(): array
    {
        return [];
    }

    public function checkConnection(bool $useCache = true): bool
    {
        if ($useCache && $status = $this->getCache('connection')) {
            if ($status === self::CONNECT_SUCCESS) {
                return true;
            }
        }

        // Fire a 'beforeCheckConnection' event
        $event = new IntegrationConnectionEvent([
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_BEFORE_CHECK_CONNECTION, $event);

        if (!$event->isValid) {
            Integration::info($this, 'Checking connection cancelled by event hook.');

            return false;
        }

        $success = $this->fetchConnection() ? self::CONNECT_SUCCESS : self::CONNECT_FAIL;

        // Fire a 'afterCheckConnection' event
        $event = new IntegrationConnectionEvent([
            'integration' => $this,
            'success' => $success,
        ]);
        $this->trigger(self::EVENT_AFTER_CHECK_CONNECTION, $event);

        // Update the cache
        $this->setCache(['connection' => $event->success]);

        return $event->success;
    }

    public function getIsConnected(): bool
    {
        if (static::supportsOAuthConnection()) {
            return (bool)$this->getToken();
        }

        if (static::supportsConnection()) {
            return $this->getCache('connection') === self::CONNECT_SUCCESS;
        }

        return false;
    }

    public function getFormSettings(bool $useCache = true): bool|IntegrationFormSettings
    {
        // If using the cache (the default), don't fetch it automatically. Just save API requests a tad.
        if ($useCache) {
            $settings = $this->getCache('settings') ?: [];

            // Add support for emoji in cached content
            $settings = Json::decode(StringHelper::shortcodesToEmoji((string)Json::encode($settings)));

            // De-serialize it from the cache back into full, nested class objects
            $formSettings = new IntegrationFormSettings();
            $formSettings->unserialize($settings);

            // Always deal with a `IntegrationFormSettings` model
            return $formSettings;
        }

        // Fire a 'beforeFetchFormSettings' event
        $event = new IntegrationFormSettingsEvent([
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_BEFORE_FETCH_FORM_SETTINGS, $event);

        if (!$event->isValid) {
            Integration::info($this, 'Checking connection cancelled by event hook.');

            return false;
        }

        // Only proceed if the provider is connected
        if (static::supportsConnection() && !static::getIsConnected()) {
            Integration::error($this, 'Connect to the integration provider first.', true);
        }

        $settings = $this->fetchFormSettings();

        // Fire a 'afterFetchFormSettings' event
        $event = new IntegrationFormSettingsEvent([
            'integration' => $this,
            'settings' => $settings,
        ]);
        $this->trigger(self::EVENT_AFTER_FETCH_FORM_SETTINGS, $event);

        // Save a serialised version to the cache, that retains classes
        $this->setCache(['settings' => $settings->serialize()]);

        // Always deal with a `IntegrationFormSettings` model
        return $settings;
    }

    public function getFormSettingValue(string $key)
    {
        return $this->getFormSettings()->getSettingsByKey($key);
    }

    public function validateFieldMapping(string $attribute, array $fields = []): void
    {
        foreach ($fields as $field) {
            $value = $this->$attribute[$field->handle] ?? '';

            if ($field->required && $value === '') {
                $this->addError($attribute, Craft::t('formie', '{name} must be mapped.', ['name' => $field->name]));
                return;
            }
        }
    }

    public function getToken(): ?Token
    {
        if ($this->id) {
            return Auth::getInstance()->getTokens()->getTokenByOwnerReference('formie', $this->id);
        }

        return null;
    }

    public function getRedirectUri(): ?string
    {
        if (Craft::$app->getConfig()->getGeneral()->headlessMode) {
            return UrlHelper::actionUrl('formie/integrations/callback');
        }

        return UrlHelper::siteUrl('formie/integrations/callback');
    }

    public function request(string $method, string $uri, array $options = []): mixed
    {
        // If an OAuth-based integration, use the Auth module's client to do the request
        if (static::supportsOAuthConnection()) {
            return $this->OAuthRequest($method, $uri, $options);
        }

        $response = $this->getClient()->request($method, ltrim($uri, '/'), $options);

        return Json::decode($response->getBody()->getContents());
    }

    public function deliverPayload(Submission $submission, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json'): mixed
    {
        // Allow events to cancel sending
        if (!$this->beforeSendPayload($submission, $endpoint, $payload, $method, $contentType)) {
            return false;
        }

        // Return a JSON response from the provider
        $response = $this->request($method, $endpoint, [
            $contentType => $payload,
        ]);

        // Allow events to say the response is invalid
        if (!$this->afterSendPayload($submission, $endpoint, $payload, $method, $response)) {
            return false;
        }

        return $response;
    }

    public function deliverPayloadRequest(Submission $submission, string $endpoint, mixed $payload, string $method = 'POST', string $contentType = 'json'): mixed
    {
        // Allow events to cancel sending
        if (!$this->beforeSendPayload($submission, $endpoint, $payload, $method, $contentType)) {
            return false;
        }

        // Don't assume a JSON response, return the raw response to deal with later
        $response = $this->getClient()->request($method, $endpoint, [
            $contentType => $payload,
        ]);

        // Allow events to say the response is invalid
        if (!$this->afterSendPayload($submission, $endpoint, $payload, $method, $response)) {
            return false;
        }

        return $response;
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = [])
    {
        $fieldValues = [];

        if (!is_array($fieldMapping)) {
            $fieldMapping = [];
        }

        foreach ($fieldMapping as $tag => $fieldKey) {
            // Don't let in un-mapped fields
            if ($fieldKey === '') {
                continue;
            }

            // Get the type of field we are mapping to (for the integration)
            $integrationField = ArrayHelper::firstWhere($fieldSettings, 'handle', $tag) ?? new IntegrationField();

            // TODO define static integrations better
            if ($fieldKey === '{submission:dateCreated}') {
                $integrationField->type = IntegrationField::TYPE_DATETIME;
            }

            if (str_contains($fieldKey, '{')) {
                // Get the value of the mapped field, from the submission.
                $fieldValue = $this->getMappedFieldValue($fieldKey, $submission, $integrationField);

                // Be sure the check against empty values and not map them. '', null and [] are all empty
                // but 0 is a totally valid value.
                if (self::isEmpty($fieldValue)) {
                    // Check if an element integration, where we can check against overwrite values.
                    // If it's empty, and we're overwriting values, we don't care
                    if ($this instanceof Element && $this->overwriteValues) {
                        $fieldValues[$tag] = $fieldValue;
                    }
                } else {
                    $fieldValues[$tag] = $fieldValue;
                }
            } else {
                // Otherwise, might have passed in a direct, static value. But ensure they're typecasted properly
                $fieldValues[$tag] = static::convertValueForIntegration($fieldKey, $integrationField);
            }
        }

        $event = new ModifyFieldIntegrationValuesEvent([
            'fieldValues' => $fieldValues,
            'submission' => $submission,
            'fieldMapping' => $fieldMapping,
            'fieldSettings' => $fieldSettings,
            'integration' => $this,
        ]);

        $this->trigger(static::EVENT_MODIFY_FIELD_MAPPING_VALUES, $event);

        return $event->fieldValues;
    }

    public function getMappedFieldsByType(Submission $submission, array $fieldMapping, array $fieldSettings, string $type): array
    {
        $fields = [];

        foreach ($fieldMapping as $tag => $fieldKey) {
            // Don't let in un-mapped fields
            if ($fieldKey === '' || !str_starts_with($fieldKey, '{field:')) {
                continue;
            }

            $fieldInfo = $this->getMappedFieldInfo($fieldKey, $submission);
            $field = $fieldInfo['field'];

            if (!$field || get_class($field) !== $type) {
                continue;
            }

            $fields[] = [
                'integrationField' => ArrayHelper::firstWhere($fieldSettings, 'handle', $tag) ?? new IntegrationField(),
                'field' => $field,
                'value' => $submission->getFieldValue($field->fieldKey),
            ];
        }

        return $fields;
    }

    public function populateContext(): void
    {
        $request = Craft::$app->getRequest();

        // Add some extra values to integrations to record in the context of being run
        // Useful to maintain the referrer, current site, etc - things that aren't possible in a queue.
        $this->context = [
            'referrer' => $request->getReferrer(),
            'ipAddress' => $request->getUserIP(),
        ];
    }

    public function populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType): void
    {
        if (!$this->getQueueJob()) {
            return;
        }

        try {
            $config = $this->getClient()->getConfig();
        } catch (Throwable $e) {
            $config = [];
        }

        $this->getQueueJob()->payload = [
            'client' => array_filter([
                'headers' => $config['headers'] ?? null,
                'verify' => $config['verify'] ?? null,
                'base_uri' => $config['base_uri'] ?? null,
                'auth' => $config['auth'] ?? null,
            ]),
            'request' => array_filter([
                'method' => $method,
                'endpoint' => ltrim((string)$endpoint, '/'),
                'type' => $contentType,
                'data' => $payload,
            ]),
        ];
    }

    public function beforeSendPayload(Submission $submission, string &$endpoint, mixed &$payload, string &$method, string $contentType = 'json'): bool
    {
        // If in the context of a queue. save the payload for debugging
        $this->populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType);

        $event = new SendIntegrationPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
            'endpoint' => $endpoint,
            'method' => $method,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_BEFORE_SEND_PAYLOAD, $event);

        if (!$event->isValid) {
            Integration::info($this, 'Sending payload cancelled by event hook.');
        }

        // Also, check for opt-in fields. This allows the above event to potentially alter things
        if (!$this->enforceOptInField($submission)) {
            Integration::info($this, 'Sending payload cancelled by opt-in field.');

            return false;
        }

        // Allow events to alter some props
        $payload = $event->payload;
        $endpoint = $event->endpoint;
        $method = $event->method;

        return $event->isValid;
    }

    public function afterSendPayload(Submission $submission, string $endpoint, mixed $payload, string $method, mixed $response): bool
    {
        $event = new SendIntegrationPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
            'response' => $response,
            'endpoint' => $endpoint,
            'method' => $method,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_AFTER_SEND_PAYLOAD, $event);

        if (!$event->isValid) {
            Integration::info($this, 'Payload marked as invalid by event hook.');
        }

        return $event->isValid;
    }

    public function enforceOptInField(Submission $submission): bool
    {
        // Default is just always do it!
        if (!$this->optInField) {
            return true;
        }

        // Get the value of the mapped field, from the submission
        $fieldValue = $this->getMappedFieldValue($this->optInField, $submission, new IntegrationField());

        if (self::isEmpty($fieldValue)) {
            Integration::info($this, Craft::t('formie', 'Unable to find field “{field}” for opt-in in submission.', [
                'field' => $this->optInField,
            ]));

            return false;
        }

        // Do some checks depending on the field value type
        $hasOptedIn = true;

        // Fetch information about the field we've picked to opt-in with
        $fieldInfo = $this->getMappedFieldInfo($this->optInField, $submission);
        $field = $fieldInfo['field'];

        // For Checkboxes fields, we'll have an object, but let's check for anything iterable just to be safe
        if (is_iterable($fieldValue)) {
            $hasOptedIn = (bool)count($fieldValue);
        } else if ($field instanceof Agree) {
            // If this is an agree field, check this is the "Checked Value". This needs to handle strings
            // which won't work as falsey values.
            if ($field->checkedValue !== $fieldValue) {
                $hasOptedIn = false;
            }
        } else if (!$fieldValue) {
            // For everything else, just a simple 'falsey' check is good enough
            $hasOptedIn = false;
        }

        if (!$hasOptedIn) {
            Integration::info($this, Craft::t('formie', 'Opting-out. Field “{field}” has value “{value}”.', [
                'field' => $this->optInField,
                'value' => $fieldValue,
            ]));

            return false;
        }

        return true;
    }

    public function getMappedFieldInfo(string $mappedFieldValue, Submission $submission): array
    {
        // Replace how we store the value (as `{field:fieldHandle}` or `{submission:id}`)
        $fieldKey = str_replace(['{field:', '}'], ['', ''], $mappedFieldValue);

        // Change the field handle to reflect the top-level field, not the full path to the value
        // but still keep the subField path (if any) for some fields to use
        $fieldKey = explode('.', $fieldKey);
        $fieldHandle = array_shift($fieldKey);
        $fieldKey = implode('.', $fieldKey);

        // Try and get the form field we're pulling data from
        $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', $fieldHandle);

        return ['field' => $field, 'handle' => $fieldHandle, 'key' => $fieldKey];
    }

    public function getMappedFieldValue(string $mappedFieldValue, Submission $submission, IntegrationField $integrationField): mixed
    {
        try {
            if (str_starts_with($mappedFieldValue, '{submission:')) {
                $mappedFieldValue = str_replace(['{submission:', '}'], ['', ''], $mappedFieldValue);

                // Ensure the submission value is typecasted properly.
                return static::convertValueForIntegration($submission->$mappedFieldValue, $integrationField);
            }

            // Get information about the fields we're mapping to. The field key/handle will be different
            // if this is a complex field, but the handle will always be the top-level field.
            $fieldInfo = $this->getMappedFieldInfo($mappedFieldValue, $submission);
            $field = $fieldInfo['field'];
            $fieldKey = $fieldInfo['key'];
            $fieldHandle = $fieldInfo['handle'];

            // Then, allow the integration to control how to parse the field, from its type
            if ($field) {
                // Fetch the value from the submission with dot-notation
                $fieldValueKey = str_replace(['{field:', '}'], ['', ''], $mappedFieldValue);
                $value = $submission->getFieldValue($fieldValueKey);

                return $field->getValueForIntegration($value, $integrationField, $this, $submission, $fieldKey);
            }
        } catch (Throwable $e) {
            Formie::error('Error trying to fetch mapped field value: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return null;
    }

    public function allowedGqlSettings(): array
    {
        return [];
    }

    public function beforeSaveForm(array $settings): void
    {
        
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id'], 'number', 'integerOnly' => true];
        $rules[] = [['handle'], UniqueValidator::class, 'targetClass' => IntegrationRecord::class];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [['name', 'handle'], 'required'];

        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'id',
                'dateCreated',
                'dateUpdated',
                'uid',
                'title',
            ],
        ];

        if (static::supportsOAuthConnection()) {
            $rules[] = [
                ['clientId', 'clientSecret'], 'required', 'when' => function($model) {
                    return $model->enabled;
                },
            ];
        }

        return $rules;
    }

    protected function defineClient(): Client
    {
        $options = [];

        // Disable SSL verification for local dev (devMode enabled) to save some heartache.
        if (App::devMode()) {
            $options['verify'] = false;
        }

        return Craft::createGuzzleClient($options);
    }

    protected function generateSubmissionPayloadValues(Submission $submission): array
    {
        $submissionContent = $submission->getValuesAsJson();
        $formAttributes = Json::decode(Json::encode($submission->getForm()->getAttributes()));

        $submissionAttributes = $submission->toArray([
            'id',
            'formId',
            'status',
            'userId',
            'ipAddress',
            'isIncomplete',
            'isSpam',
            'spamReason',
            'title',
            'dateCreated',
            'dateUpdated',
            'dateDeleted',
            'trashed',
        ]);

        // Trim the form settings a little
        unset($formAttributes['settings']['integrations']);

        return [
            'submission' => array_merge($submissionAttributes, $submissionContent),
            'form' => $formAttributes,
        ];
    }


    // Private Methods
    // =========================================================================

    private function setCache(array $values): void
    {
        if ($this->cache === null) {
            $this->cache = [];
        }

        // Extract any settings now to prevent accidental merge issues
        $oldSettings = ArrayHelper::remove($this->cache, 'settings') ?? [];
        $newSettings = ArrayHelper::remove($values, 'settings') ?? [];

        // Shallow-merge the top-level items (connection status, etc)
        $this->cache = array_merge($this->cache, $values);

        // Merge stored and new settings (so they work across forms)
        $this->cache['settings'] = array_merge($oldSettings, $newSettings);

        // Add support for emoji in cached content
        $data = Json::encode($this->cache);
        $data = StringHelper::emojiToShortcodes((string)$data);

        // Direct DB update to keep it out of PC, plus speed
        Db::update(Table::FORMIE_INTEGRATIONS, ['cache' => $data], ['id' => $this->id]);
    }

    private function getCache(string $key): mixed
    {
        if ($this->cache === null) {
            $this->cache = [];
        }

        return $this->cache[$key] ?? null;
    }

    private static function isEmpty($value): bool
    {
        return $value === '' || $value === [] || $value === null;
    }


    // Deprecated Methods
    // =========================================================================

    public static function log(IntegrationInterface $integration, string $message, bool $throwError = false): void
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'The `log()` function is deprecated. Use `info()` instead.');

        self::info($integration, $message, $throwError);
    }
}
