<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\Plugin;
use verbb\formie\models\Settings;

use Craft;
use craft\helpers\Json;

use yii\web\Response;

class SettingsController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Find the first available settings
        if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return $this->renderTemplate('formie/settings/general', compact('settings'));
        }

        return $this->redirect('formie/settings/address-providers');
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

        $this->setSuccessFlash(Craft::t('formie', 'Field palette saved.'));

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

        return $this->renderTemplate('formie/settings/submissions', compact('settings'));
    }

    public function actionSpam(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return $this->renderTemplate('formie/settings/spam', compact('settings'));
    }

    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;

        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $settingsParams = $request->getParam('settings');

        if (is_array($settingsParams)) {
            $settingsParams = Formie::$plugin->getFormDefaults()->normalizeSettingsPayload($settingsParams);
        }

        $settings->setAttributes($settingsParams, false);

        if (!$settings->validate()) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Formie::$plugin, $settings->toArray());

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

}
