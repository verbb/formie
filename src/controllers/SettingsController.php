<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\fields\formfields\MissingField;
use verbb\formie\models\Settings;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\Response;

class SettingsController extends Controller
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
        $formTemplates = Formie::$plugin->getFormTemplates()->getAllTemplates();
        $emailTemplates = Formie::$plugin->getEmailTemplates()->getAllTemplates();

        return $this->renderTemplate('formie/settings/forms', compact('settings', 'formTemplates', 'emailTemplates'));
    }

    public function actionFields(): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        $disabledFields = [];

        foreach (Formie::$plugin->getFields()->getRegisteredFields(false) as $field) {
            if ($field instanceof MissingField) {
                continue;
            }

            $disabledFields[] = [
                'label' => $field::displayName(),
                'value' => get_class($field),
            ];
        }

        return $this->renderTemplate('formie/settings/fields', compact('settings', 'disabledFields'));
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

    /**
     * @return Response|null
     * @throws MissingComponentException
     * @throws BadRequestHttpException
     */
    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $settings->setAttributes($request->getParam('settings'), false);

        if (!$settings->validate()) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Formie::$plugin, $settings->toArray());

        if (!$pluginSettingsSaved) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

}
