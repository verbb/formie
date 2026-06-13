<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\FormInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\IntegrationConnectionEvent;
use verbb\formie\events\IntegrationFormSettingsEvent;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\events\ModifyFieldIntegrationValuesEvent;
use verbb\formie\events\ModifyIntegrationSlotTagEvent;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\IntegrationHelper;
use verbb\formie\helpers\IntegrationApiErrors;
use verbb\formie\helpers\IntegrationRerunPolicies;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\fields\Agree;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\SlotTag;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\events\ModifyIntegrationFormSettingsSchemaEvent;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationSettingsContext;
use verbb\formie\models\Phone;
use verbb\formie\models\Stencil;
use verbb\formie\options\IntegrationOptionSourceHelper;
use verbb\formie\options\OptionList;
use verbb\formie\records\Integration as IntegrationRecord;
use verbb\formie\services\Integrations as IntegrationsService;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\SavableComponent;
use craft\helpers\App;
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
    public const EVENT_MODIFY_INTEGRATION_FORM_SETTINGS_SCHEMA = 'modifyIntegrationFormSettingsSchema';
    public const EVENT_MODIFY_SLOT_TAG = 'modifySlotTag';

    
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
    public const OAUTH_CALLBACK_ACTION = 'formie/integrations/callback';


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

    public static function kebabClassName(): string
    {
        return StringHelper::toKebabCase(static::className());
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

    public static function getOptionSourceDefinitions(): array
    {
        $definitions = [];

        foreach (static::defineOptionSources() as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $handle = trim((string)($definition['handle'] ?? ''));
            $label = trim((string)($definition['label'] ?? ''));

            if ($handle === '' || $label === '') {
                continue;
            }

            $publicDefinition = [
                'handle' => $handle,
                'label' => $label,
            ];

            if (isset($definition['optionSourceUsages'])) {
                $publicDefinition['optionSourceUsages'] = (array)$definition['optionSourceUsages'];
            }

            $definitions[] = $publicDefinition;
        }

        return $definitions;
    }

    public function getOptionSourceBuilderConfig(string $provider): array
    {
        $definition = static::_getOptionSourceDefinition($provider);

        if (!$definition) {
            return [
                'error' => Craft::t('formie', 'Unknown integration option provider.'),
            ];
        }

        $settings = $this->getFormSettings();

        if ($settings === false) {
            return [
                'error' => Craft::t('formie', 'Refresh the integration settings first.'),
            ];
        }

        return $this->buildOptionSourceBuilderConfig($provider, $settings, $definition);
    }

    public function resolveOptionSourceOptions(string $provider, array $params = []): OptionList
    {
        $definition = static::_getOptionSourceDefinition($provider);

        if (!$definition) {
            return OptionList::error(Craft::t('formie', 'Unknown integration option provider.'));
        }

        $collectionParam = (string)($definition['collectionParam'] ?? 'collectionId');
        $remoteHandleParam = (string)($definition['remoteHandleParam'] ?? 'remoteHandle');
        $collectionId = (string)($params[$collectionParam] ?? '');
        $remoteHandle = (string)($params[$remoteHandleParam] ?? '');

        if ($collectionId === '') {
            return OptionList::error((string)($definition['collectionRequiredMessage'] ?? Craft::t('formie', 'Select a list.')));
        }

        if ($remoteHandle === '') {
            return OptionList::error((string)($definition['remoteHandleRequiredMessage'] ?? Craft::t('formie', 'Select an option source.')));
        }

        try {
            $settings = $this->getFormSettings();

            if ($settings === false) {
                return OptionList::error(Craft::t('formie', 'Refresh the integration settings first.'));
            }

            foreach ($this->getOptionSourceCollections($settings, $definition) as $collection) {
                if ((string)$collection['id'] !== $collectionId) {
                    continue;
                }

                foreach ($collection['fields'] as $field) {
                    if (!$field instanceof IntegrationField || $field->handle !== $remoteHandle) {
                        continue;
                    }

                    return OptionList::fromRows(IntegrationOptionSourceHelper::flattenIntegrationFieldOptions($field->options));
                }
            }

            return OptionList::error((string)($definition['notFoundMessage'] ?? Craft::t('formie', 'Option source not found. Refresh the integration data.')));
        } catch (Throwable $e) {
            Craft::error('Integration option source failed to resolve: ' . $e->getMessage(), __METHOD__);

            return OptionList::error(Craft::t('formie', 'Unable to resolve integration options.'));
        }
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

    public static function warning(IntegrationInterface $integration, string $message, bool $throwError = false): void
    {
        Formie::warning($integration->name . ': ' . $message);

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

    public static function apiError(IntegrationInterface $integration, Error|Exception $exception, bool $throwError = true, ?Submission $submission = null): void
    {
        $messageText = self::getExceptionLogMessage($exception);

        $message = Craft::t('formie', 'API error: “{message}” {file}:{line}', [
            'message' => $messageText,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        $context = self::formatSubmissionLogContext($submission);
        if ($context !== '') {
            $message .= ' ' . $context;
        }

        Formie::error($integration->name . ': ' . $message);

        if ($throwError) {
            throw new IntegrationException($message, 0, $exception);
        }
    }

    public static function formatSubmissionLogContext(?Submission $submission): string
    {
        if (!$submission) {
            return '';
        }

        $form = $submission->getForm();

        if (!$form) {
            return Craft::t('formie', '[Submission #{submissionId}]', [
                'submissionId' => $submission->id,
            ]);
        }

        return Craft::t('formie', '[Form “{handle}” (#{formId}) submission #{submissionId}]', [
            'handle' => $form->handle,
            'formId' => $form->id,
            'submissionId' => $submission->id,
        ]);
    }

    public static function getExceptionLogMessage(Throwable $exception): string
    {
        if ($exception instanceof RequestException && $exception->getResponse()) {
            $response = $exception->getResponse();
            $statusCode = $response->getStatusCode();
            $reason = trim((string)$response->getReasonPhrase());
            $body = trim((string)$response->getBody());

            if (mb_strlen($body) > 2000) {
                $body = mb_substr($body, 0, 2000) . '...';
            }

            return trim("HTTP {$statusCode}" . ($reason !== '' ? " {$reason}" : '') . ($body !== '' ? " ({$body})" : ''));
        }

        return $exception->getMessage();
    }

    public static function convertValueForIntegration(mixed $value, IntegrationField $integrationField): mixed
    {
        return IntegrationHelper::convertValueForIntegration($value, $integrationField);
    }

    private static function isEmpty($value): bool
    {
        return $value === '' || $value === [] || $value === null;
    }


    // Properties
    // =========================================================================

    public ?string $name = null;
    public ?string $handle = null;
    public ?string $scope = null;
    public ?string $type = null;
    public ?int $sortOrder = null;
    public array $cache = [];
    public ?string $uid = null;
    public ?string $optInField = null;
    public bool $enableConditions = false;
    public ?array $conditions = null;

    // Store extra context for when running the integration
    public array $context = [];

    // Store extra context when configuring the integration in the form builder
    public IntegrationSettingsContext $settingsContext;

    protected ?Client $_client = null;

    // Keep track of whether run in the context of a queue job
    private ?JobInterface $_queueJob = null;
    private bool|string $_enabled = false;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        if (!isset($config['settingsContext'])) {
            $config['settingsContext'] = new IntegrationSettingsContext();
        }

        parent::__construct($config);
    }

    public function isProjectScope(): bool
    {
        return $this->getScope() === IntegrationsService::SCOPE_PROJECT;
    }

    public function isSiteScope(): bool
    {
        return $this->getScope() === IntegrationsService::SCOPE_SITE;
    }

    public function getScope(): string
    {
        $scope = $this->scope ?? IntegrationsService::SCOPE_PROJECT;

        return in_array($scope, [IntegrationsService::SCOPE_PROJECT, IntegrationsService::SCOPE_SITE], true)
            ? $scope
            : IntegrationsService::SCOPE_PROJECT;
    }

    public function getScopeLabel(): string
    {
        if ($this->isSiteScope()) {
            return '';
        }

        return Craft::t('formie', 'Project');
    }

    public function canEdit(): bool
    {
        if ($this->isSiteScope()) {
            return true;
        }

        return (bool)Craft::$app->getConfig()->getGeneral()->allowAdminChanges;
    }

    public function canDelete(): bool
    {
        return $this->canEdit();
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

    public function supportsIntegrationApiErrorSeverity(): bool
    {
        return false;
    }

    /**
     * Classify a submission-time API error for integrations that opt into severity handling.
     *
     * Return `null` to treat the error as a failure.
     */
    public function classifyIntegrationApiError(Throwable $exception): ?string
    {
        return null;
    }

    /**
     * Handle a submission-time API error using plugin severity settings.
     *
     * @return bool Whether the integration send should be treated as successful.
     */
    public function handleSubmissionApiError(Throwable $exception, Submission $submission): bool
    {
        if (!$this->supportsIntegrationApiErrorSeverity()) {
            throw new IntegrationException(Craft::t('formie', 'This integration does not support API error severity handling.'));
        }

        $severity = $this->classifyIntegrationApiError($exception) ?? IntegrationApiErrors::SEVERITY_FAILURE;

        return IntegrationApiErrors::applySubmissionErrorAction($this, $exception, $submission, $severity);
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

    public function getTypeHandle(): string
    {
        return StringHelper::toKebabCase($this->getType());
    }

    public function getCategory(): string
    {
        return '';
    }

    public function getCategoryHandle(): string
    {
        return StringHelper::toKebabCase($this->getCategory());
    }

    public function getCpIconPath(): string
    {
        $category = trim((string)$this->getCategoryHandle());
        $handle = trim((string)$this->getClassHandle());

        if ($category === '' || $handle === '') {
            return '';
        }

        return "icons/{$category}/{$handle}.svg";
    }

    public function getCpIconUrl(?string $distBaseUrl = null): string
    {
        $path = $this->getCpIconPath();
        if ($path === '') {
            return $this->getIconUrl();
        }

        static $cachedDistBaseUrl = null;
        $base = $distBaseUrl !== null ? rtrim((string)$distBaseUrl, '/') : '';

        if ($base === '') {
            if ($cachedDistBaseUrl === null) {
                $resolved = (string)Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true);
                $cachedDistBaseUrl = rtrim($resolved, '/');
            }

            $base = $cachedDistBaseUrl;
        }

        if ($base !== '') {
            return "{$base}/{$path}";
        }

        // Fallback: should rarely happen, but keep behavior safe.
        return (string)Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, $path);
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

    public function renderSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $event = new ModifyIntegrationSlotTagEvent([
            'integration' => $this,
            'tag' => $this->defineFieldSlotTag($key, $context),
            'key' => $key,
            'context' => $context->toArray(),
        ]);

        $this->trigger(static::EVENT_MODIFY_SLOT_TAG, $event);

        return $event->tag;
    }

    public function getIntegrationFieldMappingField(array $config = []): array
    {
        $dataLabel = isset($config['dataLabel']) ? trim((string)$config['dataLabel']) : '';
        $dataKey = isset($config['dataKey']) ? trim((string)$config['dataKey']) : '';

        return SchemaHelper::integrationFieldMappingField(array_merge([
            'label' => Craft::t('formie', '{name} Field Mapping', ['name' => $dataLabel]),
            'instructions' => Craft::t('formie', 'Choose how your form fields should map to your {name} {label} fields.', ['name' => $this->displayName(), 'label' => $dataLabel]),
            'integrationLabel' => Craft::t('formie', '{name} Field', ['name' => $dataLabel]),
            'integrationFields' => $this->defineFieldMappingSchema($dataKey),
        ], $config));
    }

    public function getFormSettingsSchema(FormInterface $form): array
    {
        $schema = $this->defineFormSettingsSchema($form);
        $schema = SchemaHelper::schemaNode($schema);
        $event = new ModifyIntegrationFormSettingsSchemaEvent([
            'schema' => $schema,
            'integration' => $this,
            'form' => $form,
        ]);
        $this->trigger(self::EVENT_MODIFY_INTEGRATION_FORM_SETTINGS_SCHEMA, $event);

        return $event->schema;
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
    {
        return null;
    }

    public function hasValidSettings(): bool
    {
        return true;
    }

    public function supportsFormSettingsRefresh(): bool
    {
        return false;
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
        $mapping = $this->$attribute;
        if (!is_array($mapping)) {
            $mapping = [];
        }

        foreach ($fields as $field) {
            $rawValue = $mapping[$field->handle] ?? '';
            $value = $this->normalizeFieldMappingValue($rawValue);

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
        $redirectUri = App::parseEnv(Formie::$plugin->getSettings()->redirectUri);

        if (is_string($redirectUri) && $redirectUri !== '') {
            return $redirectUri;
        }

        return UrlHelper::actionUrl(self::OAUTH_CALLBACK_ACTION);
    }

    public function request(string $method, string $uri, array $options = []): mixed
    {
        // If an OAuth-based integration, use the Auth module's client to do the request
        if (static::supportsOAuthConnection()) {
            return $this->OAuthRequest($method, $uri, $options);
        }

        $response = $this->getClient()->request($method, ltrim($uri, '/'), $options);
        $text = (string)$response->getBody()->getContents();

        if (Json::isJsonObject($text)) {
            return Json::decode($text);
        }

        return $text;
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

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = [])
    {
        $fieldValues = [];

        if (!is_array($fieldMapping)) {
            $fieldMapping = [];
        }

        foreach ($fieldMapping as $tag => $rawFieldKey) {
            $fieldKey = $this->normalizeFieldMappingValue($rawFieldKey);

            // Don't let in un-mapped fields
            if ($fieldKey === '') {
                continue;
            }

            // Get the type of field we are mapping to (for the integration)
            $integrationField = ArrayHelper::firstWhere($fieldSettings, 'handle', $tag) ?? new IntegrationField();

            if (str_contains($fieldKey, '{')) {
                // Reference tokens opt into Formie's field-resolution pipeline,
                // including field-specific integration projections, while plain
                // strings are treated as authored literals.
                $resolved = Variables::getFieldAndValueForReference($fieldKey, $submission);
                $value = static::convertValueForIntegration($resolved['value'], $integrationField);
                $field = $resolved['field'];

                // Most integrations treat empty values as "leave unmapped" to
                // avoid clearing remote data unintentionally. Element syncing is
                // the main exception because overwriteValues explicitly opts into
                // sending blanks as real updates.
                $shouldSet = !self::isEmpty($value) || ($this instanceof Element && $this->overwriteValues);
            } else {
                $value = static::convertValueForIntegration($fieldKey, $integrationField);
                $field = null;
                $shouldSet = true;
            }

            if ($shouldSet) {
                $fieldValues[$tag] = $value;

                $eventConfig = [
                    'value' => $value,
                    'submission' => $submission,
                    'integrationField' => $integrationField,
                    'integration' => $this,
                ];

                if ($field !== null) {
                    $eventConfig['field'] = $field;
                }

                $this->trigger(static::EVENT_MODIFY_FIELD_MAPPING_VALUE, new ModifyFieldIntegrationValueEvent($eventConfig));
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

    public function shouldTrigger(Submission $submission, array $context = []): bool
    {
        $form = $submission->getForm();

        if ($form) {
            $triggerEvent = (string)($context['triggerEvent'] ?? IntegrationTriggerEvents::SUBMIT);
            $operatorInitiated = (bool)($context['operatorInitiated'] ?? false);

            if (!IntegrationRerunPolicies::isEventAllowed($form, $this, $triggerEvent, $operatorInitiated)) {
                return false;
            }
        }

        if (!$this->enableConditions) {
            return true;
        }

        $conditionSettings = $this->conditions ?? [];
        $conditions = $conditionSettings['conditions'] ?? [];

        if (!$conditionSettings || !$conditions) {
            return true;
        }

        $result = ConditionsHelper::getConditionalTestResult($conditionSettings, $submission);
        $triggerRule = (string)($conditionSettings['triggerRule'] ?? 'trigger');

        if ($triggerRule === 'trigger') {
            return $result;
        }

        return !$result;
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
        if (!$this->getQueueJob() || !method_exists($this, 'getClient')) {
            return;
        }

        $config = $this->getClient()->getConfig();

        $this->getQueueJob()->payload = [
            'client' => array_filter([
                'headers' => self::_sanitizeQueueHeaders($config['headers'] ?? null),
                'verify' => $config['verify'] ?? null,
                'base_uri' => $config['base_uri'] ?? null,
                'auth' => isset($config['auth']) ? '[redacted]' : null,
            ]),
            'request' => array_filter([
                'method' => $method,
                'endpoint' => ltrim((string)$endpoint, '/'),
                'type' => $contentType,
                'data' => '[redacted]',
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

    public function enforceOptInField(Submission $submission, ?array $variables = null): bool
    {
        // Default is just always do it!
        if (!$this->optInField) {
            return true;
        }

        // Get the value of the mapped field (resolved via getFieldValue when field is found, else variables)
        $optInField = $this->normalizeFieldMappingValue($this->optInField);
        $resolved = Variables::getFieldAndValueForReference($optInField, $submission, $variables);
        $field = $resolved['field'];
        $rawValue = $resolved['value'];

        if ($field === null && self::isEmpty($rawValue)) {
            Integration::info($this, Craft::t('formie', 'Unable to find field “{field}” for opt-in in submission.', [
                'field' => $optInField,
            ]));

            return false;
        }

        if ($field !== null && $field->isValueEmpty($rawValue, $submission)) {
            Integration::info($this, Craft::t('formie', 'Opting-out. Field “{field}” is empty.', [
                'field' => $optInField,
            ]));

            return false;
        }

        $fieldValue = static::convertValueForIntegration($rawValue, new IntegrationField(['type' => IntegrationField::TYPE_BOOLEAN]));
        $hasOptedIn = (bool)$fieldValue;

        if (!$hasOptedIn) {
            Integration::info($this, Craft::t('formie', 'Opting-out. Field “{field}” has value “{value}”.', [
                'field' => $optInField,
                'value' => $fieldValue,
            ]));

            return false;
        }

        return true;
    }

    public function getMappedFieldValue(string $fieldKey, Submission $submission, IntegrationField $integrationField): mixed
    {
        $fieldKey = $this->normalizeFieldMappingValue($fieldKey);
        $resolved = Variables::getFieldAndValueForReference($fieldKey, $submission);
        $fieldValue = static::convertValueForIntegration($resolved['value'], $integrationField);
        $field = $resolved['field'];

        $event = new ModifyFieldIntegrationValueEvent([
            'value' => $fieldValue,
            'rawValue' => $resolved['value'],
            'field' => $field,
            'submission' => $submission,
            'integrationField' => $integrationField,
            'integration' => $this,
        ]);
        $this->trigger(static::EVENT_MODIFY_FIELD_MAPPING_VALUE, $event);

        return $event->value;
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

    protected function defineFieldMappingSchema(string $settingsKey, ?string $selectedCollectionField = null): array
    {
        $integrationFields = $this->getFormSettingValue($settingsKey);

        if ($selectedCollectionField) {
            $selectedCollectionId = (string)($this->{$selectedCollectionField} ?? '');
            $selectedFields = [];

            if ($selectedCollectionId !== '' && is_array($integrationFields)) {
                foreach ($integrationFields as $collection) {
                    $id = is_array($collection) ? ($collection['id'] ?? null) : ($collection->id ?? null);
                    if ((string)$id !== $selectedCollectionId) {
                        continue;
                    }

                    $selectedFields = is_array($collection) ? ($collection['fields'] ?? []) : ($collection->fields ?? []);
                    break;
                }
            }

            $integrationFields = $selectedFields;
        }

        if (!is_array($integrationFields) || !$integrationFields) {
            return [];
        }

        return $this->convertIntegrationFieldsToSchema($integrationFields);
    }

    protected function convertIntegrationFieldsToSchema(array $integrationFields): array
    {
        $fields = [];

        foreach ($integrationFields as $integrationField) {
            $handle = is_array($integrationField) ? ($integrationField['handle'] ?? null) : ($integrationField->handle ?? null);
            $name = is_array($integrationField) ? ($integrationField['name'] ?? null) : ($integrationField->name ?? null);
            $required = (bool)(is_array($integrationField) ? ($integrationField['required'] ?? false) : ($integrationField->required ?? false));
            $options = is_array($integrationField) ? ($integrationField['options'] ?? null) : ($integrationField->options ?? null);

            if (!$handle) {
                continue;
            }

            $label = $name ?: (string)$handle;

            $fieldMeta = [
                'handle' => (string)$handle,
                'name' => $label,
                'required' => $required,
            ];

            if (!empty($options)) {
                $fieldMeta['options'] = $options;
            }

            $fields[] = $fieldMeta;
        }

        return $fields;
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enabled'),
                'instructions' => Craft::t('formie', 'Whether the integration should be enabled.'),
                'name' => 'enabled',
            ]),
            SchemaHelper::enableConditionsField([
                'instructions' => Craft::t('formie', 'Whether to enable conditional logic to control when this integration is triggered.'),
            ]),
            [
                '$field' => 'integrationConditions',
                'name' => 'conditions',
                'if' => 'enableConditions',
                'fieldOptions' => ConditionsHelper::getConditionFieldOptions($this->_getConditionFieldOptionConfig()),
                'conditionOptions' => ConditionsHelper::getConditionOptions(),
            ],
        ];
    }

    protected function _getConditionFieldOptionConfig(): array
    {
        return [
            'includeSubmissionDate' => true,
            'siteNameOptions' => array_merge([
                ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
            ], array_map(function($site) {
                return [
                    'label' => $site->name,
                    'value' => $site->name,
                ];
            }, Craft::$app->getSites()->getAllSites())),
            'siteHandleOptions' => array_merge([
                ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
            ], array_map(function($site) {
                return [
                    'label' => $site->name,
                    'value' => $site->handle,
                ];
            }, Craft::$app->getSites()->getAllSites())),
            'statusOptions' => array_merge([
                ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
            ], array_map(function($status) {
                return [
                    'label' => $status->name,
                    'value' => $status->handle,
                ];
            }, Formie::$plugin->getStatuses()->getAllStatuses())),
        ];
    }

    protected static function defineOptionSources(): array
    {
        return [];
    }

    protected function getOptionSourceCollections(IntegrationFormSettings $settings, array $definition): array
    {
        $storage = (string)($definition['storage'] ?? 'collections');

        if ($storage === 'objects') {
            return $this->_getOptionSourceObjectCollections($settings, $definition);
        }

        return $this->_getOptionSourceIntegrationCollections($settings, $definition);
    }

    protected function buildOptionSourceBuilderConfig(string $provider, IntegrationFormSettings $settings, array $definition): array
    {
        $collectionParam = (string)($definition['collectionParam'] ?? 'collectionId');
        $remoteHandleParam = (string)($definition['remoteHandleParam'] ?? 'remoteHandle');
        $collections = [];
        $remoteHandles = [];
        $remoteHandlesByCollection = [];

        foreach ($this->getOptionSourceCollections($settings, $definition) as $collection) {
            $collectionRemoteHandles = [];

            foreach ($collection['fields'] as $field) {
                if (!$field instanceof IntegrationField) {
                    continue;
                }

                $option = [
                    'label' => (string)$field->name,
                    'value' => (string)$field->handle,
                ];

                $collectionRemoteHandles[$field->handle] = $option;
                $remoteHandles[$field->handle] = $option;
            }

            $collectionId = (string)$collection['id'];
            $remoteHandlesByCollection[$collectionId] = array_values($collectionRemoteHandles);
            $collections[] = [
                'label' => (string)$collection['name'],
                'value' => $collectionId,
                'remoteHandleOptions' => array_values($collectionRemoteHandles),
            ];
        }

        $remoteHandleOptions = array_values($remoteHandles);
        $defaultCollectionId = null;
        $defaultRemoteHandle = null;

        foreach ($collections as $collection) {
            $collectionRemoteHandleOptions = $collection['remoteHandleOptions'] ?? [];

            if (!$collectionRemoteHandleOptions) {
                continue;
            }

            $defaultCollectionId = $collection['value'];
            $defaultRemoteHandle = $collectionRemoteHandleOptions[0]['value'] ?? null;
            break;
        }

        return [
            'collectionOptions' => $collections,
            'remoteHandleOptions' => $remoteHandleOptions,
            'sourceOptions' => $remoteHandleOptions,
            'paramFields' => [
                [
                    'handle' => $collectionParam,
                    'type' => 'select',
                    'label' => (string)($definition['collectionLabel'] ?? Craft::t('formie', 'List')),
                    'instructions' => (string)($definition['collectionInstructions'] ?? Craft::t('formie', 'Choose the integration list or collection.')),
                    'placeholder' => (string)($definition['collectionPlaceholder'] ?? Craft::t('formie', 'Select a list')),
                    'options' => $collections,
                    'required' => true,
                ],
                [
                    'handle' => $remoteHandleParam,
                    'type' => 'select',
                    'label' => (string)($definition['remoteHandleLabel'] ?? Craft::t('formie', 'Option Source')),
                    'instructions' => (string)($definition['remoteHandleInstructions'] ?? Craft::t('formie', 'Choose which remote field supplies the options.')),
                    'placeholder' => (string)($definition['remoteHandlePlaceholder'] ?? Craft::t('formie', 'Select an option source')),
                    'dependsOn' => $collectionParam,
                    'options' => $remoteHandleOptions,
                    'optionsByParam' => [
                        $collectionParam => $remoteHandlesByCollection,
                    ],
                    'required' => true,
                ],
            ],
            'defaults' => [
                $collectionParam => $defaultCollectionId,
                $remoteHandleParam => $defaultRemoteHandle,
            ],
            'warning' => $collections === []
                ? (string)($definition['emptyCollectionsWarning'] ?? Craft::t('formie', 'No lists available. Refresh the integration data first.'))
                : ($remoteHandleOptions === []
                    ? (string)($definition['emptySourcesWarning'] ?? Craft::t('formie', 'No option sources available. Refresh the integration data first.'))
                    : null),
        ];
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        return null;
    }

    protected function getOptInFieldSchema(): array
    {
        return SchemaHelper::fieldSelectField([
            'label' => Craft::t('formie', 'Opt-In Field'),
            'instructions' => Craft::t('formie', 'Choose a field to opt-in to {name}. For example, you might only wish to subscribe users if they provide a value for a field of your choice - commonly, an Agree field.', ['name' => $this::displayName()]),
            'name' => 'optInField',
            'placeholder' => Craft::t('formie', 'Always Opt-in'),
            'variableConfig' => [
                'types' => [Variables::TYPE_BOOLEAN],
            ],
            'topLevelOnly' => true,
        ]);
    }

    protected function generateSubmissionPayloadValues(Submission $submission): array
    {
        $user = $submission->getUser();
        $submissionContent = $submission->getValuesAsArray();
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

        $submissionAttributes['user'] = $user ? [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'fullName' => $user->fullName,
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
        ] : null;

        // Trim the form settings a little
        unset($formAttributes['settings']['integrations']);

        return [
            'submission' => array_merge($submissionAttributes, $submissionContent),
            'form' => $formAttributes,
        ];
    }

    protected function normalizeFieldMappingValue(mixed $value): string
    {
        if (is_array($value)) {
            $mappingType = $value['type'] ?? null;

            if ($mappingType === 'none') {
                return '';
            }

            $mappingValue = $value['value'] ?? '';

            return is_scalar($mappingValue) ? $this->replaceProviderOptionTokens((string)$mappingValue) : '';
        }

        if (is_object($value)) {
            $mappingType = $value->type ?? null;

            if ($mappingType === 'none') {
                return '';
            }

            $mappingValue = $value->value ?? '';

            return is_scalar($mappingValue) ? $this->replaceProviderOptionTokens((string)$mappingValue) : '';
        }

        return is_scalar($value) ? $this->replaceProviderOptionTokens((string)$value) : '';
    }

    protected function replaceProviderOptionTokens(string $value): string
    {
        return preg_replace_callback('/\{providerOption:([^}]+)\}/', function($matches) {
            $encoded = $matches[1] ?? '';
            
            if (!is_string($encoded) || $encoded === '') {
                return '';
            }

            return rawurldecode($encoded);
        }, $value) ?? $value;
    }


    // Private Methods
    // =========================================================================

    private function _getOptionSourceIntegrationCollections(IntegrationFormSettings $settings, array $definition): array
    {
        $collectionKey = (string)($definition['collectionKey'] ?? 'lists');
        $collections = [];

        foreach ($settings->getSettingsByKey($collectionKey) as $collection) {
            if (!$collection instanceof IntegrationCollection) {
                continue;
            }

            $fields = $this->_filterOptionSourceFields($collection->fields, $definition);

            if (!$fields) {
                continue;
            }

            $collections[] = [
                'id' => (string)$collection->id,
                'name' => (string)$collection->name,
                'fields' => $fields,
            ];
        }

        return $collections;
    }

    private function _getOptionSourceObjectCollections(IntegrationFormSettings $settings, array $definition): array
    {
        $objectKeys = (array)($definition['objectKeys'] ?? []);
        $objectLabels = (array)($definition['objectLabels'] ?? []);
        $collections = [];

        foreach ($objectKeys as $objectKey) {
            $objectKey = (string)$objectKey;
            $fields = $settings->getSettingsByKey($objectKey);

            if (!is_array($fields) || !$fields) {
                continue;
            }

            $filteredFields = $this->_filterOptionSourceFields($fields, $definition);

            if (!$filteredFields) {
                continue;
            }

            $collections[] = [
                'id' => $objectKey,
                'name' => (string)($objectLabels[$objectKey] ?? ucwords(str_replace(['_', '-'], ' ', $objectKey))),
                'fields' => $filteredFields,
            ];
        }

        return $collections;
    }

    private function _filterOptionSourceFields(array $fields, array $definition): array
    {
        $sourceTypes = (array)($definition['optionSourceTypes'] ?? []);
        $filtered = [];

        foreach ($fields as $field) {
            if (!$field instanceof IntegrationField || empty($field->options)) {
                continue;
            }

            if ($sourceTypes !== [] && !in_array((string)$field->sourceType, $sourceTypes, true)) {
                continue;
            }

            $filtered[] = $field;
        }

        return $filtered;
    }

    private static function _getOptionSourceDefinition(string $provider): ?array
    {
        foreach (static::defineOptionSources() as $definition) {
            if (!is_array($definition) || (string)($definition['handle'] ?? '') !== $provider) {
                continue;
            }

            return $definition;
        }

        return null;
    }

    private static function _sanitizeQueueHeaders(mixed $headers): mixed
    {
        if (!is_array($headers)) {
            return $headers;
        }

        foreach ($headers as $key => $value) {
            if (in_array(mb_strtolower((string)$key), ['authorization', 'x-api-key', 'api-key'], true)) {
                $headers[$key] = '[redacted]';
            }
        }

        return $headers;
    }

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
}
