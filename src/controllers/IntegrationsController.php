<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\OauthTokenEvent;
use verbb\formie\models\Settings;
use verbb\formie\models\Token;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\HttpException;
use yii\web\Response;

use Exception;
use Throwable;

class IntegrationsController extends Controller
{
    // Constants
    // =========================================================================

    const EVENT_AFTER_OAUTH_CALLBACK = 'afterOauthCallback';

    // Properties
    // =========================================================================

    protected $allowAnonymous = ['callback'];
    private $redirect;
    private $originUrl;


    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $integrations = Formie::$plugin->getintegrations()->getAllIntegrations();
        $groupedIntegrations = Formie::$plugin->getintegrations()->getAllGroupedIntegrations();
        
        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'groupedIntegrations'));
    }

    public function actionSaveSettings()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $errors = [];

        foreach ($request->getParam('integrations') as $handle => $integrationConfig) {
            $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);
            $integration->enabled = $integrationConfig['enabled'] ?? false;
            $integration->settings = $integrationConfig['settings'] ?? [];

            if (!Formie::$plugin->getIntegrations()->saveIntegrationSettings($integration)) {
                $errors[] = true;
            }
        }
        
        if ($errors) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save integration settings.'));

            return null;
        }
    
        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Integration settings saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionRefreshList()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $handle = $request->getParam('integration');

        if (!$handle) {
            return $this->asErrorJson(Craft::t('formie', 'Unknown integration: “{handle}”', ['handle' => $handle]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

        // Force getting all list options, re-generating cache
        return $this->asJson([
            'success' => true,
            'listOptions' => $integration->getListOptions(false),
        ]);
    }

    public function actionCheckConnection()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $handle = $request->getParam('integration');

        if (!$handle) {
            return $this->asErrorJson(Craft::t('formie', 'Unknown integration: “{handle}”', ['handle' => $handle]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

        if (!$integration->supportsConnection()) {
            return $this->asErrorJson(Craft::t('formie', '“{handle}” does not support connection.', ['handle' => $handle]));
        }

        // Populate the integration's settings with the real-time values on the settings screen
        $settings = $request->getParam("integrations.{$handle}.settings", []);

        if ($settings) {
            $integration->settings = array_merge($integration->settings, $settings);
        }

        try {
            // Check to see if it's valid. Exceptions help to provide errors nicely
            return $this->asJson([
                'success' => $integration->checkConnection(false),
            ]);
        } catch (IntegrationException $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    public function actionElementFields()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $handle = $request->getParam('integration');

        if (!$handle) {
            return $this->asErrorJson(Craft::t('formie', 'Unknown integration: “{handle}”', ['handle' => $handle]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

        // Handball to the integration class to deal with the return
        return $this->asJson($integration->getElementFieldsFromRequest($request));
    }



    // OAuth Methods
    // =========================================================================

    public function actionConnect(): Response
    {
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $handle = $request->getParam('handle');
        $controllerUrl = UrlHelper::actionUrl('formie/integrations/connect', ['handle' => $handle]);

        $session->set('formie.controllerUrl', $controllerUrl);

        $this->originUrl = $session->get('formie.originUrl');

        if (!$this->originUrl) {
            $this->originUrl = $request->referrer;
            $session->set('formie.originUrl', $this->originUrl);
        }

        $this->redirect = (string)$request->getParam('redirect');

        if (!$handle) {
            throw new Exception(Craft::t('formie', 'Unknown integration: “{handle}”', ['handle' => $handle]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

        if (!$integration) {
            throw new Exception(Craft::t('formie', '“{handle}” integration not found.', ['handle' => $handle]));
        }

        try {
            // Redirect to provider’s authorization page
            $session->set('formie.provider', $integration->handle);

            if (!$session->get('formie.callback')) {
                return $integration->oauthConnect();
            }

            // Callback
            $session->remove('formie.callback');

            $callbackResponse = $integration->oauthCallback();

            if ($callbackResponse['success']) {
                return $this->_createToken($callbackResponse, $integration);
            }

            throw new Exception($callbackResponse['errorMsg']);
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();

            // Try and get a more meaningful error message
            $errorTitle = $request->getParam('error');
            $errorDescription = $request->getParam('error_description');

            Formie::error(Craft::t('formie', 'Couldn’t connect to “{handle}”: “{message}” {file}:{line}', [
                'handle' => $integration->handle,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

            if ($errorTitle || $errorDescription) {
                $errorMsg = $errorTitle . ' ' . $errorDescription;
            }

            Formie::error(Craft::t('formie', '“{handle}” response: “{errorMsg}”', [
                'handle' => $integration->handle,
                'errorMsg' => $errorMsg,
            ]));

            $session->setFlash('error', $errorMsg);

            $this->_cleanSession();

            return $this->redirect($this->originUrl);
        }
    }

    public function actionDisconnect(): Response
    {
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $handle = $request->getParam('handle');

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

        $this->_deleteToken($integration);

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true
            ]);
        }

        $session->setNotice(Craft::t('formie', 'Integration disconnected.'));

        return $this->redirect($request->referrer);
    }

    public function actionCallback(): Response
    {
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $session->set('formie.callback', true);

        $url = $session->get('formie.controllerUrl');

        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }

        $queryParams = $request->getQueryParams();

        if (isset($queryParams['p'])) {
            unset($queryParams['p']);
        }

        $url .= http_build_query($queryParams);

        return $this->redirect($url);
    }


    // Private Methods
    // =========================================================================

    private function _createToken($response, $integration)
    {
        $session = Craft::$app->getSession();

        $token = new Token();
        $token->integrationHandle = $integration->handle;
        $token->accessToken = $response['token']->getToken();
        $token->endOfLife = $response['token']->getExpires();
        $token->refreshToken = $response['token']->getRefreshToken();

        // Fire a 'afterOauthCallback' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_OAUTH_CALLBACK)) {
            $this->trigger(self::EVENT_AFTER_OAUTH_CALLBACK, new OauthTokenEvent([
                'token' => $token,
            ]));
        }

        if (!Formie::$plugin->getTokens()->saveToken($token)) {
            Formie::error('Unable to save token - ' . json_encode($token->getErrors()) . '.');
        
            return null;
        }

        $this->_cleanSession();

        if (!$this->redirect) {
            $this->redirect = $this->originUrl;
        }

        $session->setNotice(Craft::t('formie', 'Integration connected.'));

        return $this->redirect($this->redirect);
    }

    private function _deleteToken($integration)
    {
        if (!Formie::$plugin->getTokens()->deleteTokenByHandle($integration->handle)) {
            Formie::error('Unable to delete token - ' . json_encode($token->getErrors()) . '.');
        
            return null;
        }
    }

    private function _cleanSession()
    {
        Craft::$app->getSession()->remove('formie.originUrl');
    }
}
