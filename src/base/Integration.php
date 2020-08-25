<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\records\Integration as IntegrationRecord;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\helpers\UrlHelper as FormieUrlHelper;

use Craft;
use craft\base\SavableComponent;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Response;

use League\OAuth2\Client\Provider\GenericProvider;

abstract class Integration extends SavableComponent implements IntegrationInterface
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SEND_PAYLOAD = 'beforeSendPayload';
    const EVENT_AFTER_SEND_PAYLOAD = 'afterSendPayload';
    
    const TYPE_ADDRESS_PROVIDER = 'addressProvider';
    const TYPE_CAPTCHA = 'captcha';
    const TYPE_ELEMENT = 'element';
    const TYPE_EMAIL_MARKETING = 'emailMarketing';
    const TYPE_CRM = 'crm';
    const TYPE_WEBHOOK = 'webhook';

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

    protected $_client;


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
    public function getFormSettingsHtml(Form $form): string
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
    public function checkConnection($useCache = true): bool
    {
        if ($useCache && $status = $this->getCache('connection')) {
            if ($status === self::CONNECT_SUCCESS) {
                return true;
            }
        }

        $success = $this->fetchConnection();

        if ($success) {
            $this->setCache(['connection' => self::CONNECT_SUCCESS]);
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function getIsConnected()
    {
        if ($this->supportsOauthConnection()) {
            return (bool)$this->getToken();
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
            return $this->getCache('settings') ?: [];
        }

        $settings = $this->fetchFormSettings();

        $this->setCache(['settings' => $settings]);

        return $settings;
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
        // For now, just support OAuth2
        return 2;
    }

    /**
     * @inheritDoc
     */
    public function oauthConnect()
    {
        // For now, just support OAuth2
        return $this->oauth2Connect();
    }

    /**
     * @inheritDoc
     */
    public function oauthCallback()
    {
        // For now, just support OAuth2
        return $this->oauth2Callback();
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
    public function getToken()
    {
        if ($this->tokenId) {
            return Formie::$plugin->getTokens()->getTokenById($this->tokenId);
        }

        return null;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function request(string $method, string $uri, array $options = [])
    {
        $response = $this->getClient()->request($method, trim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }

    /**
     * @inheritDoc
     */
    protected function deliverPayload($submission, $endpoint, $payload, $method = 'POST')
    {
        // Allow events to cancel sending
        if (!$this->beforeSendPayload($submission, $payload)) {
            return false;
        }

        $response = $this->request($method, $endpoint, [
            'json' => $payload,
        ]);

        // Allow events to say the response is invalid
        if (!$this->afterSendPayload($submission, $payload, $response)) {
            return false;
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    protected function getFieldMappingValues(Submission $submission, $fieldMapping)
    {
        $fieldValues = [];

        foreach ($fieldMapping as $tag => $formFieldHandle) {
            // Don't let in un-mapped fields
            if ($formFieldHandle !== '') {
                // See if this is mapping a custom field
                if (strstr($formFieldHandle, '{')) {
                    $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);

                    // Convert to string. We'll introduce more complex field handling in the future, but this will
                    // be controlled at the integration-level. Some providers might only handle an address as a string
                    // others might accept an array of content. The integration should handle this...
                    try {
                        $fieldValues[$tag] = (string)$submission->getFieldValue($formFieldHandle);
                    } catch (\Throwable $e) {}

                    try {
                        $fieldValues[$tag] = (string)$submission->$formFieldHandle;
                    } catch (\Throwable $e) {}
                } else {
                    // Otherwise, might have passed in a direct value
                    $fieldValues[$tag] = $formFieldHandle;
                }
            }
        }

        return $fieldValues;
    }

    /**
     * @inheritDoc
     */
    protected function beforeSendPayload(Submission $submission, $payload)
    {
        $event = new SendIntegrationPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_BEFORE_SEND_PAYLOAD, $event);

        if (!$event->isValid) {
            Integration::log($this, 'Sending payload cancelled by event hook.');
        }

        return $event->isValid;
    }

    /**
     * @inheritDoc
     */
    protected function afterSendPayload(Submission $submission, $payload, $response)
    {
        $event = new SendIntegrationPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
            'response' => $response,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_AFTER_SEND_PAYLOAD, $event);

        if (!$event->isValid) {
            Integration::log($this, 'Payload marked as invalid by event hook.');
        }

        return $event->isValid;
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
}
