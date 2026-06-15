<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\FieldBuilderPolicy;
use verbb\formie\helpers\IntegrationApiErrors;
use verbb\formie\helpers\Plugin;
use verbb\formie\models\Settings;

use Craft;
use craft\helpers\Json;

use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SettingsController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $permissions = Formie::$plugin->getPermissions();
        $user = Craft::$app->getUser()->getIdentity();

        if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges && $permissions->canAccessSettingsPage($user, 'general')) {
            /* @var Settings $settings */
            $settings = Formie::$plugin->getSettings();

            return $this->renderTemplate('formie/settings/general', compact('settings'));
        }

        foreach (array_keys($permissions->getSettingsPageDefinitions()) as $page) {
            if (!$permissions->canAccessSettingsPage($user, $page)) {
                continue;
            }

            return $this->redirect("formie/settings/$page");
        }

        throw new ForbiddenHttpException('User is not permitted to perform this action');
    }

    public function actionForms(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/forms', compact('settings'));
    }

    public function actionDefaults(): Response
    {
        Plugin::registerCpDefaultsAssets();

        $editorConfig = Formie::$plugin->getFormDefaults()->getEditorConfig();

        $this->view->registerJs('new Craft.Formie.Defaults(' . Json::encode($editorConfig) . ');');

        return $this->renderTemplate('formie/settings/defaults', [
            'defaultsSettingsPayload' => Json::encode($editorConfig['values']),
        ]);
    }

    public function actionFields(): Response
    {
        Plugin::registerCpFieldPaletteAssets();

        $editorConfig = Formie::$plugin->getFieldPalette()->getEditorConfig();

        $this->view->registerJs('new Craft.Formie.FieldPalette(' . Json::encode($editorConfig) . ');');

        return $this->renderTemplate('formie/settings/fields/index', [
            'settings' => Formie::$plugin->getSettings(),
            'fieldPalettePayload' => Json::encode(Formie::$plugin->getFieldPalette()->getSavePayload()),
        ]);
    }

    public function actionSaveFieldPalette(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $this->setFailFlash(Craft::t('formie', 'Field palette cannot be saved when allowAdminChanges is disabled.'));

            return null;
        }

        $paletteJson = $this->request->getBodyParam('palette');

        if (!is_string($paletteJson) || $paletteJson === '') {
            $this->setFailFlash(Craft::t('formie', 'Invalid field palette payload.'));

            return null;
        }

        try {
            $palette = Json::decode($paletteJson);
        } catch (\Throwable) {
            $this->setFailFlash(Craft::t('formie', 'Invalid field palette payload.'));

            return null;
        }

        if (!is_array($palette) || !Formie::$plugin->getFieldPalette()->savePalette($palette)) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save field palette.'));

            return null;
        }

        if (!$this->_saveFieldBuilderPolicySettings()) {
            return null;
        }

        $this->setSuccessFlash(Craft::t('formie', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionSaveDefaults(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $settingsJson = $this->request->getBodyParam('settings');

        if (!is_string($settingsJson) || $settingsJson === '') {
            $this->setFailFlash(Craft::t('formie', 'Invalid defaults settings payload.'));

            return null;
        }

        try {
            $settings = Json::decode($settingsJson);
        } catch (\Throwable) {
            $this->setFailFlash(Craft::t('formie', 'Invalid defaults settings payload.'));

            return null;
        }

        if (!is_array($settings) || !Formie::$plugin->getFormDefaults()->saveEditorSettings($settings)) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save settings.'));

            return null;
        }

        $this->setSuccessFlash(Craft::t('formie', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionSubmissions(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/submissions', [
            'settings' => $settings,
        ]);
    }

    public function actionIntegrationsSettings(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/integrations-settings', [
            'settings' => $settings,
            'integrationApiErrorSeverityOptions' => IntegrationApiErrors::severityOptions(),
            'integrationApiErrorActionOptions' => IntegrationApiErrors::actionOptions(),
        ]);
    }

    public function actionSpam(): Response
    {
        return $this->redirect('formie/settings/spam-protection');
    }

    public function actionSpamProtection(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $groupedIntegrations = Formie::$plugin->getIntegrations()->getAllGroupedCaptchas();

        return $this->renderTemplate('formie/settings/spam-protection/index', compact(
            'settings',
            'groupedIntegrations',
        ));
    }

    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $permissions = Formie::$plugin->getPermissions();
        $user = Craft::$app->getUser()->getIdentity();
        $page = $permissions->normalizeSettingsPage(
            $permissions->resolveSettingsPageFromUrl((string)$this->request->getParam('redirect', '')) ?? 'general',
        );

        if (!$permissions->canAccessSettingsPage($user, $page)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $request = $this->request;

        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $settingsParams = $request->getParam('settings');

        if (is_array($settingsParams)) {
            $settingsParams = Formie::$plugin->getFormDefaults()->normalizeSettingsPayload($settingsParams);
        }

        $settings->setAttributes($settingsParams, false);

        if ($page === 'spam' || $page === 'spam-protection') {
            if (!$settings->validate()) {
                $this->setFailFlash(Craft::t('formie', 'Couldn’t save settings.'));

                Craft::$app->getUrlManager()->setRouteParams([
                    'settings' => $settings,
                ]);

                return null;
            }

            if (!Formie::$plugin->getSpamProtection()->saveFromSettings($settings)) {
                $this->setFailFlash(Craft::t('formie', 'Couldn’t save settings.'));

                Craft::$app->getUrlManager()->setRouteParams([
                    'settings' => $settings,
                ]);

                return null;
            }

            if ($page === 'spam-protection' && !$this->_saveCaptchaIntegrations($request->getParam('integrations'))) {
                $this->setFailFlash(Craft::t('formie', 'Couldn’t save settings.'));

                Craft::$app->getUrlManager()->setRouteParams([
                    'settings' => $settings,
                ]);

                return null;
            }

            $this->setSuccessFlash(Craft::t('formie', 'Settings saved.'));

            return $this->redirectToPostedUrl();
        }

        if (!$settings->validate()) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(
            Formie::$plugin,
            Formie::$plugin->getSpamProtection()->stripFromPluginSettingsArray($settings->toArray()),
        );

        if (!$pluginSettingsSaved) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('formie', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    private function _saveCaptchaIntegrations(mixed $integrations): bool
    {
        if (!is_array($integrations) || $integrations === []) {
            return true;
        }

        $integrationsService = Formie::$plugin->getIntegrations();
        $errors = [];

        foreach ($integrations as $integrationConfig) {
            if (isset($integrationConfig['saveSpam'])) {
                $integrationConfig['saveSpam'] = (bool)$integrationConfig['saveSpam'];
            }

            $integration = $integrationsService->createIntegration($integrationConfig);

            if (!$integrationsService->saveCaptcha($integration)) {
                $errors[] = $integration->getErrors();
            }
        }

        if ($errors) {
            Formie::error('Couldn’t save captcha settings - {e}.', ['e' => Json::encode($errors)]);

            return false;
        }

        return true;
    }

    private function _saveFieldBuilderPolicySettings(): bool
    {
        $settingsParams = $this->request->getParam('settings');

        if (!is_array($settingsParams)) {
            return true;
        }

        /** @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $settingsParams = Formie::$plugin->getFormDefaults()->normalizeSettingsPayload($settingsParams);

        foreach (FieldBuilderPolicy::settingsKeys() as $key) {
            if (array_key_exists($key, $settingsParams)) {
                $settings->$key = $settingsParams[$key];
            }
        }

        if (!$settings->validate()) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return false;
        }

        if (!Craft::$app->getPlugins()->savePluginSettings(Formie::$plugin, $settings->toArray())) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return false;
        }

        return true;
    }

}
