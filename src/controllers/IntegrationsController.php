<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\OauthTokenEvent;
use verbb\formie\models\Settings;
use verbb\formie\models\Token;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
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

    /**
     * Saves an integration.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveIntegration()
    {
        $this->requirePostRequest();

        $integrationsService = Formie::$plugin->getIntegrations();
        $type = $this->request->getParam('type');
        $integrationId = $this->request->getParam('id') ?: null;

        $settings = $this->request->getParam('types.' . $type);

        if ($integrationId) {
            $savedIntegration = $integrationsService->getIntegrationById($integrationId);

            if (!$savedIntegration) {
                throw new BadRequestHttpException("Invalid integration ID: $integrationId");
            }

            // Be sure to merge with any existing settings
            $settings = array_merge($savedIntegration->settings, $settings);
        }

        $integrationData = [
            'id' => $integrationId,
            'name' => $this->request->getParam('name'),
            'handle' => $this->request->getParam('handle'),
            'type' => $type,
            'sortOrder' => $savedIntegration->sortOrder ?? null,
            'enabled' => $this->request->getParam('enabled'),
            'settings' => $settings,
            'tokenId' => $savedIntegration->tokenId ?? null,
            'uid' => $savedIntegration->uid ?? null,
        ];

        $integration = $integrationsService->createIntegration($integrationData);

        if (!$integrationsService->saveIntegration($integration)) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save integration.'));

            // Send the integration back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'integration' => $integration,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('formie', 'Integration saved.'));

        return $this->redirectToPostedUrl($integration);
    }

    /**
     * Reorders integrations.
     *
     * @return Response
     */
    public function actionReorderIntegrations(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $integrationIds = Json::decode($this->request->getRequiredParam('ids'));
        Formie::$plugin->getIntegrations()->reorderIntegrations($integrationIds);

        return $this->asJson(['success' => true]);
    }

    /**
     * Deletes an integration.
     *
     * @return Response
     */
    public function actionDeleteIntegration(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $integrationId = $request->getRequiredParam('id');

        Formie::$plugin->getIntegrations()->deleteIntegrationById($integrationId);

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        $this->setSuccessFlash(Craft::t('formie', 'Integration deleted.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @inheritDoc
     */
    public function actionFormSettings()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $handle = $request->getParam('integration');

        if (!$handle) {
            return $this->asErrorJson(Craft::t('formie', 'Unknown integration: “{handle}”', ['handle' => $handle]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

        // Handball to the integration class to deal with the return
        return $this->asJson($integration->getFormSettings(false)->getSettings());
    }

    /**
     * @inheritDoc
     */
    public function actionCheckConnection()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $type = $request->getParam('type');
        $integrationId = $request->getParam('id');

        if (!$integrationId) {
            return $this->asErrorJson(Craft::t('formie', 'Unknown integration: “{id}”', ['id' => $integrationId]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);

        if (!$integration->supportsConnection()) {
            return $this->asErrorJson(Craft::t('formie', '“{id}” does not support connection.', ['id' => $integrationId]));
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


    // OAuth Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function actionConnect(): Response
    {
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $integrationId = $request->getParam('integrationId');

        if (!$integrationId) {
            throw new Exception(Craft::t('formie', 'Unknown integration: “{id}”', ['id' => $integrationId]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);

        if (!$integration) {
            throw new Exception(Craft::t('formie', 'Unknown integration: “{id}”', ['id' => $integrationId]));
        }

        // Setup for OAuth
        $controllerUrl = UrlHelper::actionUrl('formie/integrations/connect', ['integrationId' => $integrationId]);

        $session->set('formie.controllerUrl', $controllerUrl);

        $this->originUrl = $session->get('formie.originUrl');

        if (!$this->originUrl) {
            $this->originUrl = $request->referrer;
            $session->set('formie.originUrl', $this->originUrl);
        }

        $this->redirect = (string)$request->getParam('redirect');

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

            Formie::error(Craft::t('formie', 'Couldn’t connect to “{name}”: “{message}” {file}:{line}', [
                'name' => $integration->name,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

            if ($errorTitle || $errorDescription) {
                $errorMsg = $errorTitle . ' ' . $errorDescription;
            }

            Formie::error(Craft::t('formie', '“{name}” response: “{errorMsg}”', [
                'name' => $integration->name,
                'errorMsg' => $errorMsg,
            ]));

            // Show an error when connecting to OAuth, instead of just in logs
            $session->setFlash('formie-error', $errorMsg);
            $session->setError(Craft::t('formie', 'Unable to connect to “{name}”.', [
                'name' => $integration->name,
            ]));

            $this->_cleanSession();

            return $this->redirect($this->originUrl);
        }
    }

    /**
     * @inheritDoc
     */
    public function actionDisconnect(): Response
    {
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $integrationId = $request->getParam('integrationId');

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);

        $this->_deleteToken($integration);

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true
            ]);
        }

        $session->setNotice(Craft::t('formie', '“{name}” disconnected.', [
            'name' => $integration->name,
        ]));

        return $this->redirect($request->referrer);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    private function _createToken($response, $integration)
    {
        $session = Craft::$app->getSession();

        $token = new Token();
        $token->type = get_class($integration);

        switch ($integration->oauthVersion()) {
            case 1: {
                $token->accessToken = $response['token']->getIdentifier();
                $token->secret = $response['token']->getSecret();

                break;
            }
            case 2: {
                $token->accessToken = $response['token']->getToken();
                $token->endOfLife = $response['token']->getExpires();
                $token->refreshToken = $response['token']->getRefreshToken();

                break;
            }
        }

        // Fire a 'afterOauthCallback' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_OAUTH_CALLBACK)) {
            $this->trigger(self::EVENT_AFTER_OAUTH_CALLBACK, new OauthTokenEvent([
                'token' => $token,
            ]));
        }

        if (!Formie::$plugin->getTokens()->saveToken($token)) {
            $error = Craft::t('formie', 'Unable to save token - {errors}.', [
                'errors' => Json::encode($token->getErrors()),
            ]);

            Formie::error($error);
            $session->setError($error);
        
            return null;
        }

        $integration->tokenId = $token->id;

        if (!Formie::$plugin->getIntegrations()->saveIntegration($integration)) {
            $error = Craft::t('formie', 'Unable to save integration - {errors}.', [
                'errors' => Json::encode($integration->getErrors()),
            ]);

            Formie::error($error);
            $session->setError($error);

            return null;
        }

        $this->_cleanSession();

        if (!$this->redirect) {
            $this->redirect = $this->originUrl;
        }

        $session->setNotice(Craft::t('formie', '“{name}” connected.', [
            'name' => $integration->name,
        ]));

        return $this->redirect($this->redirect);
    }

    /**
     * @inheritDoc
     */
    private function _deleteToken($integration)
    {
        $session = Craft::$app->getSession();
        
        if (!Formie::$plugin->getTokens()->deleteTokenById($integration->tokenId)) {
            $error = Craft::t('formie', 'Unable to delete token - {errors}.', [
                'errors' => Json::encode($token->getErrors()),
            ]);

            Formie::error($error);
            $session->setError($error);
        
            return null;
        }

        $integration->tokenId = null;

        if (!Formie::$plugin->getIntegrations()->saveIntegration($integration)) {
            $error = Craft::t('formie', 'Unable to update integration - {errors}.', [
                'errors' => Json::encode($integration->getErrors()),
            ]);

            Formie::error($error);
            $session->setError($error);
        
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    private function _cleanSession()
    {
        Craft::$app->getSession()->remove('formie.originUrl');
    }
}
