<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\IntegrationConnectionEvent;
use verbb\formie\events\IntegrationFormSettingsEvent;
use verbb\formie\events\ModifyFieldIntegrationValuesEvent;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\helpers\UrlHelper as FormieUrlHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\records\Integration as IntegrationRecord;

use Craft;
use craft\base\SavableComponent;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use craft\web\Response;

use League\OAuth2\Client\Provider\GenericProvider;
use GuzzleHttp\Exception\RequestException;

abstract class Integration extends SavableComponent implements IntegrationInterface
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SEND_PAYLOAD = 'beforeSendPayload';
    const EVENT_AFTER_SEND_PAYLOAD = 'afterSendPayload';
    const EVENT_BEFORE_CHECK_CONNECTION = 'beforeCheckConnection';
    const EVENT_AFTER_CHECK_CONNECTION = 'afterCheckConnection';
    const EVENT_BEFORE_FETCH_FORM_SETTINGS = 'beforeFetchFormSettings';
    const EVENT_AFTER_FETCH_FORM_SETTINGS = 'afterFetchFormSettings';
    const EVENT_MODIFY_FIELD_MAPPING_VALUES = 'modifyFieldMappingValues';
    const EVENT_MODIFY_FIELD_MAPPING_VALUE = 'modifyFieldMappingValue';
    
    const TYPE_ADDRESS_PROVIDER = 'addressProvider';
    const TYPE_CAPTCHA = 'captcha';
    const TYPE_ELEMENT = 'element';
    const TYPE_EMAIL_MARKETING = 'emailMarketing';
    const TYPE_CRM = 'crm';
    const TYPE_WEBHOOK = 'webhook';
    const TYPE_MISC = 'miscellaneous';

    const SCENARIO_FORM = 'form';

    const CONNECT_SUCCESS = 'success';


    // Properties
    // =========================================================================

    public $name;
    public $handle;
    public $type;
    public $enabled;
    public $sortOrder;
    public $cache = [];
    public $tokenId;
    public $uid;

    // Used to retain the referrer URL from submissions
    public $referrer = '';

    protected $_client;

    // Keep track of whether run in the context of a queue job
    private $_queueJob;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function isSelectable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function supportsConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function supportsOauthConnection(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function supportsPayloadSending(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function hasFormSettings(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function log($integration, $message, $throwError = false)
    {
        Formie::log($integration->name . ': ' . $message);

        if ($throwError) {
            throw new IntegrationException($message);
        }
    }

    /**
     * @inheritDoc
     */
    public static function error($integration, $message, $throwError = false)
    {
        Formie::error($integration->name . ': ' . $message);

        if ($throwError) {
            throw new IntegrationException($message);
        }
    }

    /**
     * @inheritDoc
     */
    public static function apiError($integration, $exception, $throwError = true)
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
            throw new IntegrationException($message);
        }
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if ($this->cache) {
            $this->cache = Json::decodeIfJson($this->cache);
        }

        if (is_string($this->enabled)) {
            $this->enabled = (bool)$this->enabled;
        }
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();

        // Always have the form's scenario defined, but don't overwrite it
        $scenarios[self::SCENARIO_FORM] = $scenarios[self::SCENARIO_FORM] ?? [];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
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
            ]
        ];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getHandle(): string
    {
        return $this->handle ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml($form): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function hasValidSettings(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function setQueueJob($value)
    {
        $this->_queueJob = $value;
    }

    /**
     * @inheritDoc
     */
    public function getQueueJob()
    {
        return $this->_queueJob;
    }

    /**
     * @inheritDoc
     */
    public function setClient($value)
    {
        $this->_client = $value;
    }

    /**
     * @inheritDoc
     */
    public function checkConnection($useCache = true): bool
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
            Integration::log($this, 'Checking connection cancelled by event hook.');

            return false;
        }

        $success = $this->fetchConnection();

        // Fire a 'afterCheckConnection' event
        $event = new IntegrationConnectionEvent([
            'integration' => $this,
            'success' => $success,
        ]);
        $this->trigger(self::EVENT_AFTER_CHECK_CONNECTION, $event);

        if ($event->success) {
            $this->setCache(['connection' => self::CONNECT_SUCCESS]);
        }

        return $event->success;
    }

    /**
     * @inheritDoc
     */
    public function getIsConnected()
    {
        if ($this->supportsOauthConnection()) {
            return (bool)$this->getToken(false);
        }

        if ($this->supportsConnection()) {
            return (bool)($this->getCache('connection') === self::CONNECT_SUCCESS);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFormSettings($useCache = true)
    {
        // If using the cache (the default), don't fetch it automatically. Just save API requests a tad.
        if ($useCache) {
            $settings = $this->getCache('settings') ?: [];
            
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
            Integration::log($this, 'Checking connection cancelled by event hook.');

            return false;
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

    /**
     * @inheritDoc
     */
    public function getFormSettingValue($key)
    {
        return $this->getFormSettings()->getSettingsByKey($key);
    }

    /**
     * @inheritDoc
     */
    public function validateFieldMapping($attribute, $fields = [])
    {
        foreach ($fields as $field) {
            $value = $this->$attribute[$field->handle] ?? '';

            if ($field->required && $value === '') {
                $this->addError($attribute, Craft::t('formie', '{name} must be mapped.', ['name' => $field->name]));
                return;
            }
        }
    }


    // OAuth Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getAuthorizeUrl(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getResourceOwner(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getOauthScope(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getOauthAuthorizationOptions(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function oauthVersion(): int
    {
        return 2;
    }

    /**
     * @inheritDoc
     */
    public function oauth2Legged(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function oauthConnect()
    {
        switch ($this->oauthVersion()) {
            case 1: {
                return $this->oauth1Connect();
            }
            case 2: {
                return $this->oauth2Connect();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function oauthCallback()
    {
        switch ($this->oauthVersion()) {
            case 1: {
                return $this->oauth1Callback();
            }
            case 2: {
                return $this->oauth2Callback();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUri()
    {
        return FormieUrlHelper::siteActionUrl('formie/integrations/callback');
    }

    /**
     * @inheritDoc
     */
    public function getOauthProviderConfig()
    {
        return [
            'urlAuthorize' => $this->getAuthorizeUrl(),
            'urlAccessToken' => $this->getAccessTokenUrl(),
            'urlResourceOwnerDetails' => $this->getResourceOwner(),
            'clientId' => $this->getClientId(),
            'clientSecret' => $this->getClientSecret(),
            'redirectUri' => $this->getRedirectUri(),
            'scopes' => $this->getOauthScope(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getOauthProvider()
    {
        return new GenericProvider($this->getOauthProviderConfig());
    }

    /**
     * @inheritDoc
     */
    public function beforeFetchAccessToken(&$provider)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function afterFetchAccessToken($token)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    private function oauth1Connect(): Response
    {
        $provider = $this->getOauthProvider();

        // Obtain temporary credentials
        $temporaryCredentials = $provider->getTemporaryCredentials();

        // Store credentials in the session
        Craft::$app->getSession()->set('oauth.temporaryCredentials', $temporaryCredentials);

        // Redirect to login screen
        $authorizationUrl = $provider->getAuthorizationUrl($temporaryCredentials);

        return Craft::$app->getResponse()->redirect($authorizationUrl);
    }

    /**
     * @inheritDoc
     */
    private function oauth2Connect(): Response
    {
        $provider = $this->getOauthProvider();

        Craft::$app->getSession()->set('formie.oauthState', $provider->getState());

        $options = $this->getOauthAuthorizationOptions();
        $options['scope'] = $this->getOauthScope();

        $authorizationUrl = $provider->getAuthorizationUrl($options);

        return Craft::$app->getResponse()->redirect($authorizationUrl);
    }

    /**
     * @inheritDoc
     */
    private function oauth1Callback(): array
    {
        $provider = $this->getOauthProvider();

        $oauthToken = Craft::$app->getRequest()->getParam('oauth_token');
        $oauthVerifier = Craft::$app->getRequest()->getParam('oauth_verifier');

        // Retrieve the temporary credentials we saved before.
        $temporaryCredentials = Craft::$app->getSession()->get('oauth.temporaryCredentials');

        // Obtain token credentials from the server.
        $token = $provider->getTokenCredentials($temporaryCredentials, $oauthToken, $oauthVerifier);

        return [
            'success' => true,
            'token' => $token
        ];
    }

    /**
     * @inheritDoc
     */
    private function oauth2Callback(): array
    {
        $provider = $this->getOauthProvider();

        $code = Craft::$app->getRequest()->getParam('code');

        $this->beforeFetchAccessToken($provider);

        // Try to get an access token (using the authorization code grant)
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        $this->afterFetchAccessToken($token);

        return [
            'success' => true,
            'token' => $token,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getToken($refresh = true)
    {
        if ($this->tokenId) {
            return Formie::$plugin->getTokens()->getTokenById($this->tokenId, $refresh);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function request(string $method, string $uri, array $options = [])
    {
        $response = $this->getClient()->request($method, ltrim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }

    /**
     * @inheritDoc
     */
    public function deliverPayload($submission, $endpoint, $payload, $method = 'POST', $contentType = 'json')
    {
        // Allow events to cancel sending
        if (!$this->beforeSendPayload($submission, $endpoint, $payload, $method)) {
            return false;
        }

        $response = $this->request($method, $endpoint, [
            $contentType => $payload,
        ]);

        // Allow events to say the response is invalid
        if (!$this->afterSendPayload($submission, $endpoint, $payload, $method, $response)) {
            return false;
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function getFieldMappingValues(Submission $submission, $fieldMapping, $fieldSettings = [])
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

            if (strstr($fieldKey, '{')) {
                // Get the type of field we are mapping to (for the integration)
                $integrationField = ArrayHelper::firstWhere($fieldSettings, 'handle', $tag) ?? new IntegrationField();

                // Get the value of the mapped field, from the submission.
                $fieldValue = $this->getMappedFieldValue($fieldKey, $submission, $integrationField);

                // Be sure the check against empty values and not map them. '', null and [] are all empty
                // but 0 is a totally valid value.
                if (!self::isEmpty($fieldValue)) {
                    $fieldValues[$tag] = $fieldValue;
                }
            } else {
                // Otherwise, might have passed in a direct, static value
                $fieldValues[$tag] = $fieldKey;
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

    /**
     * @inheritDoc
     */
    public function beforeSendPayload(Submission $submission, &$endpoint, &$payload, &$method)
    {
        // If in the context of a queue. save the payload for debugging
        if ($this->getQueueJob()) {
            $this->getQueueJob()->payload = $payload;
        }

        $event = new SendIntegrationPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
            'endpoint' => $endpoint,
            'method' => $method,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_BEFORE_SEND_PAYLOAD, $event);

        if (!$event->isValid) {
            Integration::log($this, 'Sending payload cancelled by event hook.');
        }

        // Allow events to alter some props
        $payload = $event->payload;
        $endpoint = $event->endpoint;
        $method = $event->method;

        return $event->isValid;
    }

    /**
     * @inheritDoc
     */
    public function afterSendPayload(Submission $submission, $endpoint, $payload, $method, $response)
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
            Integration::log($this, 'Payload marked as invalid by event hook.');
        }

        return $event->isValid;
    }

    /**
     * @inheritDoc
     */
    public function enforceOptInField(Submission $submission)
    {
        // Default is just always do it!
        if (!$this->optInField) {
            return true;
        }

        // Get the value of the mapped field, from the submission
        $fieldValue = $this->getMappedFieldValue($this->optInField, $submission, new IntegrationField());

        if (self::isEmpty($fieldValue)) {
            Integration::log($this, Craft::t('formie', 'Unable to find field “{field}” for opt-in in submission.', [
                'field' => $this->optInField,
            ]));

            return false;
        }

        // Do some checks depending on the field value type
        $hasOptedIn = true;

        // For Checkboxes fields, we'll have an object, but let's check for anything iterable just to be safe
        if (is_iterable($fieldValue)) {
            $hasOptedIn = (bool)count($fieldValue);
        } else if (!$fieldValue) {
            $hasOptedIn = false;
        }

        // For everything else, just a simple 'falsey' check is good enough
        if (!$hasOptedIn) {
            Integration::log($this, Craft::t('formie', 'Opting-out. Field “{field}” has value “{value}”.', [
                'field' => $this->optInField,
                'value' => $fieldValue,
            ]));

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMappedFieldValue($mappedFieldValue, $submission, $integrationField)
    {
        try {
            // Replace how we store the value (as `{field_handle}` or `{submission:id}`)
            $fieldKey = str_replace(['{', '}'], ['', ''], $mappedFieldValue);

            // If this is a submission attribute, fetch it - easy!
            if (StringHelper::startsWith($fieldKey, 'submission:')) {
                $fieldKey = str_replace('submission:', '', $fieldKey);

                return $submission->$fieldKey;
            }

            // Check for nested fields (as `group[name[prefix]]`) - convert to dot-notation
            if (strstr($fieldKey, '[')) {
                $fieldKey = str_replace(['[', ']'], ['.', ''], $fieldKey);

                // Change the field handle to reflect the top-level field, not the full path to the value
                // but still keep the sub-field path (if any) for some fields to use
                $fieldKey = explode('.', $fieldKey);
                $fieldHandle = array_shift($fieldKey);
                $fieldKey = implode('.', $fieldKey);
            } else {
                $fieldHandle = $fieldKey;
                $fieldKey = '';
            }

            // Fetch all custom fields here for efficiency
            $formFields = ArrayHelper::index($submission->getFieldLayout()->getFields(), 'handle');

            // Try and get the form field we're pulling data from
            $field = $formFields[$fieldHandle] ?? null;

            // Then, allow the integration to control how to parse the field, from its type
            if ($field) {
                $value = $submission->getFieldValue($fieldHandle);

                return $field->getValueForIntegration($value, $integrationField, $this, $submission, $fieldKey);
            }
        } catch (\Throwable $e) {
            Formie::error(Craft::t('formie', 'Error trying to fetch mapped field value: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return null;
    }

    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function setCache($values)
    {
        if ($this->cache === null) {
            $this->cache = [];
        }

        $this->cache = array_merge($this->cache, $values);

        // Direct DB update to keep it out of PC, plus speed
        Craft::$app->getDb()->createCommand()
            ->update('{{%formie_integrations}}', ['cache' => Json::encode($this->cache)], ['id' => $this->id])
            ->execute();
    }

    /**
     * @inheritDoc
     */
    private function getCache($key)
    {
        if ($this->cache === null) {
            $this->cache = [];
        }

        return $this->cache[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    private static function isEmpty($value)
    {
        if ($value === '' || $value === [] || $value === null) {
            return true;
        }

        return false;
    }
}
