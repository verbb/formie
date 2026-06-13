<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\FormInterface;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\errors\IntegrationException;
use verbb\formie\models\IntegrationSettingsContext;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\base\UnknownPropertyException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use Exception;
use Throwable;

use verbb\auth\Auth;
use verbb\auth\helpers\Session;

class IntegrationsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['callback'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        // Don't require CSRF validation for callback requests
        if ($action->id === 'callback') {
            $this->enableCsrfValidation = false;
        }

        if (in_array($action->id, ['save-integration', 'reorder-integrations', 'delete-integration', 'check-connection', 'connect', 'disconnect'], true)) {
            $this->requirePermission('formie-accessSettings');
        }

        return parent::beforeAction($action);
    }

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
            'sortOrder' => $savedIntegration?->sortOrder,
            'enabled' => (bool)$this->request->getParam('enabled'),
            'settings' => $settings,
            'uid' => $savedIntegration?->uid,
            'scope' => $savedIntegration?->scope ?? $this->request->getParam('scope'),
        ];

        $integration = $integrationsService->createIntegration($integrationData);

        if (!$integrationsService->saveIntegration($integration)) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save integration.'));

            // Send the integration back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'integration' => $integration,
            ]);

            Formie::error(Json::encode($integration->getErrors()));

            return null;
        }

        $this->setSuccessFlash(Craft::t('formie', 'Integration saved.'));

        return $this->redirectToPostedUrl($integration);
    }

    public function actionReorderIntegrations(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $integrationIds = Json::decode($this->request->getRequiredParam('ids'));
        Formie::$plugin->getIntegrations()->reorderIntegrations($integrationIds);

        return $this->asJson(['success' => true]);
    }

    public function actionDeleteIntegration(): Response
    {
        $this->requirePostRequest();

        $request = $this->request;
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

        try {
            $request = $this->request;
            $handle = $request->getParam('integration');
            $settings = $request->getParam('settings');

            if (!$handle) {
                return $this->asFailure(Craft::t('formie', 'Unknown integration: “{handle}”', ['handle' => $handle]));
            }

            $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

            // Apply any settings provided by the payload. Particularly if we're enabling/disabling objects to fetch for.
            if ($settings) {
                $integration->setAttributes($settings, false);
            }

            // Apply any extra settings to the integration, useful when fetching specific data objects
            $integration->settingsContext = new IntegrationSettingsContext([
                'dataKey' => $request->getParam('dataKey'),
            ]);

            // Handball to the integration class to deal with the return.
            return $this->asJson($integration->getFormSettings(false)->getSettings());
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function actionGetIntegrationFormSettingsConfig(): Response
    {
        $this->requireAcceptsJson();

        try {
            $handle = (string)($this->request->getBodyParam('handle') ?? $this->request->getQueryParam('handle') ?? '');
            $formId = (int)($this->request->getBodyParam('formId') ?? $this->request->getQueryParam('formId') ?? 0);

            $handle = trim($handle);

            if ($handle === '') {
                throw new BadRequestHttpException('Missing required param: handle.');
            }
            if ($formId <= 0) {
                throw new BadRequestHttpException('Missing or invalid param: formId.');
            }

            $form = Craft::$app->getElements()->getElementById($formId, Form::class);

            if (!$form) {
                $form = Formie::$plugin->getStencils()->getStencilById($formId);
            }

            if (!($form instanceof FormInterface)) {
                throw new BadRequestHttpException('Form or stencil not found.');
            }

            $config = Formie::$plugin->getIntegrations()->getIntegrationFormSettingsConfig($handle, $form);

            if ($config === null) {
                return $this->asJson([
                    'success' => false,
                    'message' => Craft::t('formie', 'Unknown integration: “{handle}”.', ['handle' => $handle]),
                ]);
            }

            return $this->asJson($config);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function actionCheckConnection(): Response
    {
        $this->requirePostRequest();

        $request = $this->request;
        $type = (string)$request->getParam('type');
        $integrationId = (int)$request->getParam('id');

        if (!$integrationId) {
            return $this->asFailure(Craft::t('formie', 'Unknown integration: “{id}”', ['id' => $integrationId]));
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationById($integrationId);
        if (!$integration) {
            return $this->asFailure(Craft::t('formie', 'Unknown integration: “{id}”', ['id' => $integrationId]));
        }

        // Build a temporary integration instance with the currently posted settings
        // so connection checks reflect unsaved values.
        $settings = $request->getParam('types.' . $type, []);
        if ($type && is_array($settings)) {
            $integrationData = [
                'id' => $integration->id,
                'name' => $request->getParam('name', $integration->name),
                'handle' => $request->getParam('handle', $integration->handle),
                'type' => $type,
                'sortOrder' => $integration->sortOrder,
                'enabled' => (bool)$request->getParam('enabled', $integration->enabled),
                'settings' => array_merge($integration->settings ?? [], $settings),
                'uid' => $integration->uid,
            ];

            $integration = Formie::$plugin->getIntegrations()->createIntegration($integrationData);
        }

        if (!$integration::supportsConnection()) {
            return $this->asFailure(Craft::t('formie', '“{id}” does not support connection.', ['id' => $integrationId]));
        }

        try {
            // Check to see if it's valid. Exceptions help to provide errors nicely
            return $this->asJson([
                'success' => $integration->checkConnection(false),
            ]);
        } catch (Throwable $e) {
            throw $e;
        }
    }


    // OAuth Methods
    // =========================================================================

    public function actionConnect(): ?Response
    {
        $integrationHandle = $this->request->getRequiredParam('integration');

        try {
            if (!($integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($integrationHandle))) {
                return $this->asFailure(Craft::t('formie', 'Unable to find integration “{integration}”.', ['integration' => $integrationHandle]));
            }

            // Keep track of which integration instance is for, so we can fetch it in the callback
            Session::set('integrationHandle', $integrationHandle);

            return Auth::getInstance()->getOAuth()->connect('formie', $integration);
        } catch (Throwable $e) {
            $error = Craft::t('formie', 'Unable to authorize connect “{integration}”: “{message}” {file}:{line}', [
                'integration' => $integrationHandle,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Log the full error
            Formie::error($error);

            // Show an error when connecting to OAuth, instead of just in logs
            Craft::$app->getSession()->setFlash('formie-error', $error);

            return $this->asFailure(Craft::t('formie', 'Unable to authorize connect “{integration}”.', ['integration' => $integrationHandle]));
        }
    }

    public function actionCallback(): ?Response
    {
        // Restore the session data that we saved before authorization redirection from the cache back to session
        Session::restoreSession($this->request->getParam('state'));
        
        // Get both the origin (failure) and redirect (success) URLs
        $origin = Session::get('origin');
        $redirect = Session::get('redirect');

        // Get the integration we're current authorizing
        if (!($integrationHandle = Session::get('integrationHandle'))) {
            Session::setError('formie', Craft::t('formie', 'Unable to find integration.'), true);

            return $this->redirect($origin);
        }

        if (!($integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($integrationHandle))) {
            Session::setError('formie', Craft::t('formie', 'Unable to find integration “{integration}”.', ['integration' => $integrationHandle]), true);

            return $this->redirect($origin);
        }

        try {
            // Fetch the access token from the integration and create a Token for us to use
            $token = Auth::getInstance()->getOAuth()->callback('formie', $integration);

            if (!$token) {
                Session::setError('formie', Craft::t('formie', 'Unable to fetch token.'), true);

                return $this->redirect($origin);
            }

            // Save the token to the Auth plugin, with a reference to this integration
            $token->reference = $integration->id;
            Auth::getInstance()->getTokens()->upsertToken($token);
        } catch (Throwable $e) {
            // Check if there are any meaningful errors returned from providers
            $message = implode(', ', array_filter([$e->getMessage(), $this->request->getParam('error'), $this->request->getParam('error_description')]));

            $error = Craft::t('formie', 'Unable to process callback for “{integration}”: “{message}” {file}:{line}', [
                'integration' => $integrationHandle,
                'message' => $message,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Formie::error($error);

            // Show the error detail in the CP
            Craft::$app->getSession()->setFlash('formie-error', $error);

            return $this->redirect($origin);
        }

        Session::setNotice('formie', Craft::t('formie', '{name} connected.', ['name' => $integration->name]), true);

        return $this->redirect($redirect);
    }

    public function actionDisconnect(): ?Response
    {
        $integrationHandle = $this->request->getRequiredParam('integration');

        if (!($integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($integrationHandle))) {
            return $this->asFailure(Craft::t('formie', 'Unable to find integration “{integration}”.', ['integration' => $integrationHandle]));
        }

        // Delete all tokens for this integration
        Auth::getInstance()->getTokens()->deleteTokenByOwnerReference('formie', $integration->id);

        return $this->asModelSuccess($integration, Craft::t('formie', '{name} disconnected.', ['name' => $integration->name]), 'integration');
    }
}
