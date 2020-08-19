<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\helpers\UrlHelper;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper as CraftUrlHelper;
use craft\web\Response;

use League\OAuth2\Client\Provider\GenericProvider;

abstract class EmailMarketing extends Integration implements IntegrationInterface
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SEND_PAYLOAD = 'beforeSendPayload';
    const EVENT_AFTER_SEND_PAYLOAD = 'afterSendPayload';
    const CONNECT_SUCCESS = 'success';


    // Properties
    // =========================================================================

    public $listId;
    public $optInField;
    public $fieldMapping;

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


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/emailmarketing/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
            'listOptions' => $this->getListOptions(),
        ]);
    }

    /**
     * Returns the frontend HTML.
     *
     * @param Form $form
     * @return string
     */
    public function getFrontEndHtml(Form $form): string
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
        $cacheKey = 'formie-email-' . $this->handle . '-connection';
        $cache = Craft::$app->getCache();

        if ($useCache && $status = $cache->get($cacheKey)) {
            if ($status === self::CONNECT_SUCCESS) {
                return true;
            }
        }

        $success = $this->fetchConnection();

        if ($success) {
            Craft::$app->getCache()->set($cacheKey, self::CONNECT_SUCCESS);
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function getConnectionStatus()
    {
        // Just try and fetch from the cache
        $cacheKey = 'formie-email-' . $this->handle . '-connection';
        $cache = Craft::$app->getCache();

        if ($cache->get($cacheKey)) {
            // Lack of translation is deliberate
            return 'Connected';
        }

        // Lack of translation is deliberate
        return 'Not connected';
    }

    /**
     * @inheritDoc
     */
    public function getAllLists($useCache = true)
    {
        $cacheKey = 'formie-email-' . $this->handle . '-lists';
        $cache = Craft::$app->getCache();

        if ($useCache && $lists = $cache->get($cacheKey)) {
            return $lists;
        }

        $lists = $this->fetchLists();

        $cache->set($cacheKey, $lists);

        return $lists;
    }

    /**
     * @inheritDoc
     */
    public function getListFields($listId = null)
    {
        $fields = [];

        if (!$listId) {
            $listId = $this->listId;
        }

        $list = $this->getListById($listId);

        foreach ($list->fields as $listField) {
            $fields[] = [
                'name' => $listField->name,
                'handle' => $listField->tag,
                'required' => $listField->required,
            ];
        }

        return $fields;
    }

    /**
     * @inheritDoc
     */
    public function validateFieldMapping($attribute)
    {
        if ($this->enabled) {
            // Ensure we check against any required fields
            $list = $this->getListById($this->listId);

            foreach ($list->fields as $field) {
                $value = $this->fieldMapping[$field->tag] ?? '';

                if ($field->required && $value === '') {
                    $this->addError($attribute, Craft::t('formie', '{name} must be mapped.', ['name' => $field->name]));
                    return;
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['listId'], 'required'];
        $rules[] = [['fieldMapping'], 'validateFieldMapping'];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getElementFieldsFromRequest($request)
    {
        $listId = $request->getParam('listId');

        if (!$listId) {
            return ['error' => Craft::t('formie', 'No “{listId}” provided.')];
        }

        return $this->getListFields($listId);
    }

    /**
     * @inheritDoc
     */
    public function getListOptions($useCache = true): array
    {
        $options = [
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
        ];

        $lists = $this->getAllLists($useCache);

        foreach ($lists as $list) {
             $options[] = ['label' => $list->name, 'value' => $list->id];
        }

        return $options;
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
    public function getClientId(): string
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
        return UrlHelper::siteActionUrl('formie/integrations/callback');
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
    private function oauth2Connect(): Response
    {
        $provider = $this->getOauthProvider();

        Craft::$app->getSession()->set('formie.oauthState', $provider->getState());

        $authorizationUrl = $provider->getAuthorizationUrl();

        return Craft::$app->getResponse()->redirect($authorizationUrl);
    }

    /**
     * @inheritDoc
     */
    private function oauth2Callback(): array
    {
        $provider = $this->getOauthProvider();

        $code = Craft::$app->getRequest()->getParam('code');

        // Try to get an access token (using the authorization code grant)
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        return [
            'success' => true,
            'token' => $token
        ];
    }

    /**
     * @inheritDoc
     */
    public function getToken()
    {
        return Formie::$plugin->getTokens()->getLatestToken($this->handle);
    }

    /**
     * @inheritDoc
     */
    public function getOauthConnected()
    {
        return $this->getToken();
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getListById($listId)
    {
        $lists = $this->getAllLists();

        foreach ($lists as $list) {
            if ($list->id === $listId) {
                return $list;
            }
        }

        return new EmailMarketingList();
    }

    /**
     * @inheritDoc
     */
    protected function getFieldMappingValues(Submission $submission)
    {
        $fieldValues = [];

        foreach ($this->fieldMapping as $tag => $formFieldHandle) {
            if ($formFieldHandle) {
                $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);

                // Convert to string. We'll introduce more complex field handling in the future, but this will
                // be controlled at the integration-level. Some providers might only handle an address as a string
                // others might accept an array of content. The integration should handle this...
                $fieldValues[$tag] = (string)$submission->getFieldValue($formFieldHandle);
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

        // Also, check for opt-in fields. This allows the above event to potentially alter things
        if (!$this->enforceOptInField($submission)) {
            Integration::log($this, 'Sending payload cancelled by opt-in field.');

            return false;
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

    /**
     * @inheritDoc
     */
    protected function enforceOptInField(Submission $submission)
    {
        // Default is just always do it!
        if (!$this->optInField) {
            return true;
        }

        $fieldValues = $this->getFieldMappingValues($submission);
        $fieldValue = $fieldValues[$this->optInField] ?? null;

        if ($fieldValue === null) {
            Integration::log($this, Craft::t('formie', 'Unable to find field “{field}” for opt-in in submission.', [
                'field' => $this->optInField,
            ]));

            return false;
        }

        // Just a simple 'falsey' check is good enough
        if (!$fieldValue) {
            Integration::log($this, Craft::t('formie', 'Opting-out. Field “{field}” has value “{value}”.', [
                'field' => $this->optInField,
                'value' => $fieldValue,
            ]));

            return false;
        }

        return true;
    }
}
