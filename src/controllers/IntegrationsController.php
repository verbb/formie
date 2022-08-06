<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\OauthTokenEvent;
use verbb\formie\models\Token;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\base\UnknownPropertyException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use Exception;
use Throwable;


class IntegrationsController extends Controller
{
    // Constants
    // =========================================================================

    public const EVENT_AFTER_OAUTH_CALLBACK = 'afterOauthCallback';

    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['callback'];

    private ?string $originUrl = null;


    // Public Methods
    // =========================================================================
    /**
     * Saves an integration.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws Throwable
     * @throws UnknownPropertyException
     */
    public function actionSaveIntegration(): ?Response
    {
        $savedIntegration = null;
        $this->requirePostRequest();

        $integrationsService = Formie::$plugin->getIntegrations();
        $type = $this->request->getParam('type');
        $integrationId = (int)$this->request->getParam('id');

        $settings = $this->request->getParam('types.' . $type, []);

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
            'enabled' => (bool)$this->request->getParam('enabled'),
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

    public function actionFormSettings(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $handle = $request->getParam('integration');

        if (!$handle) {
            return $this->asFailure(Craft::t('formie', 'Unknown integration: “{handle}”', ['handle' => $handle]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

        // Handball to the integration class to deal with the return
        return $this->asJson($integration->getFormSettings(false)->getSettings());
    }

    public function actionCheckConnection(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $type = $request->getParam('type');
        $integrationId = $request->getParam('id');

        if (!$integrationId) {
            return $this->asFailure(Craft::t('formie', 'Unknown integration: “{id}”', ['id' => $integrationId]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);

        if (!$integration::supportsConnection()) {
            return $this->asFailure(Craft::t('formie', '“{id}” does not support connection.', ['id' => $integrationId]));
        }

        try {
            // Check to see if it's valid. Exceptions help to provide errors nicely
            return $this->asJson([
                'success' => $integration->checkConnection(false),
            ]);
        } catch (IntegrationException $e) {
            return $this->asFailure($e->getMessage());
        }
    }


    // OAuth Methods
    // =========================================================================

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

        try {
            // Redirect to provider’s authorization page
            $session->set('formie.provider', $integration->handle);

            if (!$session->get('formie.callback')) {
                // Some providers (2-legged) might not return a connect response
                if (!$integration->oauth2Legged()) {
                    return $integration->oauthConnect();
                }
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

    public function actionDisconnect(): Response
    {
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $integrationId = $request->getParam('integrationId');

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);

        $this->_deleteToken($integration);

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        $session->setNotice(Craft::t('formie', '“{name}” disconnected.', [
            'name' => $integration->name,
        ]));

        return $this->redirect($request->referrer);
    }

    public function actionCallback(): Response
    {
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $session->set('formie.callback', true);

        $url = $session->get('formie.controllerUrl');

        if (!str_contains($url, '?')) {
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

    private function _createToken($response, $integration): ?Response
    {
        $session = Craft::$app->getSession();

        $token = new Token();
        $token->type = $integration::class;

        switch ($integration->oauthVersion()) {
            case 1:
            {
                $token->accessToken = $response['token']->getIdentifier();
                $token->secret = $response['token']->getSecret();

                break;
            }
            case 2:
            {
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

        if (!Formie::$plugin->getIntegrations()->updateIntegrationToken($integration, $token->id)) {
            $error = Craft::t('formie', 'Unable to save integration - {errors}.', [
                'errors' => Json::encode($integration->getErrors()),
            ]);

            Formie::error($error);
            $session->setError($error);

            return null;
        }

        $this->_cleanSession();

        $session->setNotice(Craft::t('formie', '“{name}” connected.', [
            'name' => $integration->name,
        ]));

        return $this->redirect($this->originUrl);
    }

    private function _deleteToken($integration): void
    {
        $session = Craft::$app->getSession();

        // It's okay if this fails. Maybe this token doesn't exist on this environment?
        Formie::$plugin->getTokens()->deleteTokenById($integration->tokenId);

        // Update the integration settings directly, outside of project config
        if (!Formie::$plugin->getIntegrations()->updateIntegrationToken($integration, null)) {
            $error = Craft::t('formie', 'Unable to update integration - {errors}.', [
                'errors' => Json::encode($integration->getErrors()),
            ]);

            Formie::error($error);
            $session->setError($error);
        }
    }

    private function _cleanSession(): void
    {
        Craft::$app->getSession()->remove('formie.originUrl');
    }
}
