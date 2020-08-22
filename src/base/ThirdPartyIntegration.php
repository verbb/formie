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

abstract class ThirdPartyIntegration extends Integration implements IntegrationInterface
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SEND_PAYLOAD = 'beforeSendPayload';
    const EVENT_AFTER_SEND_PAYLOAD = 'afterSendPayload';
    const CONNECT_SUCCESS = 'success';


    // Properties
    // =========================================================================

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
        $cacheKey = 'formie-integration-' . $this->handle . '-connection';
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
        $cacheKey = 'formie-integration-' . $this->handle . '-connection';
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
    public function getFormSettings($useCache = true)
    {
        $cacheKey = 'formie-integration-' . $this->handle . '-settings';
        $cache = Craft::$app->getCache();

        // If using the cache (the default), don't fetch it automatically. Just save API requests a tad.
        if ($useCache) {
            return $cache->get($cacheKey) ?: [];
        }

        $settings = $this->fetchFormSettings();

        $cache->set($cacheKey, $settings);

        return $settings;
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
}
