<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\SpamProtectionSettings;
use verbb\formie\content\ContentEncryption;
use verbb\formie\helpers\FieldTraversal;
use verbb\formie\helpers\FieldBuilderPolicy;
use verbb\formie\helpers\IntegrationApiErrors;
use verbb\formie\helpers\Plugin;
use verbb\formie\models\Settings;
use verbb\formie\services\SpamProtection;
use verbb\formie\variables\Formie as FormieVariable;

use Craft;
use craft\helpers\Json;

use yii\web\ForbiddenHttpException;
use yii\web\Response;

use Throwable;

class SettingsController extends SettingsAccessController
{
    // Constants
    // =========================================================================

    private const PAGE_SETTINGS = [
        'general' => ['pluginName', 'defaultPage'],
        'forms' => ['enableUnloadWarning', 'errorAriaLive', 'enableBackSubmission', 'enableMultiPageForms', 'ajaxTimeout', 'includeDraftElementUsage', 'includeRevisionElementUsage'],
        'notifications' => ['useQueueForNotifications', 'emptyValuePlaceholder', 'maxEmailAttachmentSizeMb', 'sendEmailAlerts', 'alertEmailsUserGroup', 'alertEmails'],
        'sent-notifications' => ['sentNotifications', 'maxSentNotificationsAge'],
        'submissions' => ['maxIncompleteSubmissionAge', 'enableCsrfValidationForGuests', 'submissionsBehaviour', 'submissionSidebarFormOrder', 'defaultCpSubmissionFieldConditions', 'reportTablePageSize', 'reportAsyncExportRowThreshold', 'reportInteractiveExportExpiryHours', 'reportScheduledExportExpiryHours', 'reportExportSingleUseDownload'],
        'integrations-settings' => ['useQueueForIntegrations', 'queuePriority', 'redirectUri', 'integrationApiErrorHandling', 'sendIntegrationAlerts', 'integrationAlertEmailsUserGroup', 'integrationAlertEmails'],
        'spam-protection' => SpamProtection::SETTING_KEYS,
    ];


    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $permissions = Formie::$plugin->getPermissions();
        $user = Craft::$app->getUser()->getIdentity();

        if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges && $permissions->canAccessSettingsPage($user, 'general')) {
            /* @var Settings $settings */
            $settings = Formie::$plugin->getSettings();

            return $this->renderTemplate('formie/settings/general', [
                'settings' => $settings,
            ]);
        }

        // Follow the visible navigation so production never redirects back to General Settings.
        foreach ((new FormieVariable())->getSettingsNavItems() as $page => $item) {
            if (isset($item['heading'])) {
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

    public function actionEncryption(): Response
    {
        return $this->renderTemplate('formie/settings/encryption', [
            'encryptionStatus' => $this->_encryptionStatus(),
            'hasEncryptedFields' => $this->_hasEncryptedFields(),
        ]);
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
        } catch (Throwable) {
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

        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Form builder defaults cannot be changed when allowAdminChanges is disabled.');
        }

        $settingsJson = $this->request->getBodyParam('settings');

        if (!is_string($settingsJson) || $settingsJson === '') {
            $this->setFailFlash(Craft::t('formie', 'Invalid defaults settings payload.'));

            return null;
        }

        try {
            $settings = Json::decode($settingsJson);
        } catch (Throwable) {
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
        return $this->redirect('formie/spam-protection/rules');
    }

    public function actionSpamProtection(): Response
    {
        return $this->redirect('formie/spam-protection');
    }

    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $permissions = Formie::$plugin->getPermissions();
        $user = Craft::$app->getUser()->getIdentity();
        $page = $permissions->normalizeSettingsPage(
            (string)$this->request->getBodyParam('page', 'general'),
        );

        $canSavePage = $page === 'spam-protection'
            ? $permissions->canManageSpamRules($user)
            : $permissions->canAccessSettings($user) && isset(self::PAGE_SETTINGS[$page]);

        if (!$canSavePage) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $request = $this->request;

        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $settingsParams = $request->getParam('settings');

        if (is_array($settingsParams)) {
            // The generic settings action must not bypass the protected Fields page.
            if (array_intersect(array_keys($settingsParams), array_keys(FieldBuilderPolicy::legacyTemplateSettings()))) {
                if (!$permissions->canAccessSettingsPage($user, 'fields')) {
                    throw new ForbiddenHttpException('User is not permitted to change field types and restrictions.');
                }

                if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
                    throw new ForbiddenHttpException('Field template settings cannot be changed when allowAdminChanges is disabled.');
                }
            }

            if (!$permissions->canAccessSettings($user) && array_diff(array_keys($settingsParams), self::PAGE_SETTINGS[$page] ?? [])) {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }

            $settingsParams = Formie::$plugin->getFormDefaults()->normalizeSettingsPayload($settingsParams);
        }

        $settings->setAttributes($settingsParams, false);

        if ($page === 'spam' || $page === 'spam-protection') {
            SpamProtectionSettings::requireManager(SpamProtectionSettings::RULES);
            SpamProtectionSettings::atomic(function() use ($settings, $request) {
                SpamProtectionSettings::assertCurrent(SpamProtectionSettings::RULES, (string)$request->getBodyParam('token', ''));

                if (!$settings->validate(SpamProtection::SETTING_KEYS) || !Formie::$plugin->getSpamProtection()->canEdit()) {
                    throw new ForbiddenHttpException('These spam rules cannot be saved. Reload Spam Protection and try again.');
                }

                $integrations = $request->getBodyParam('integrations');

                if ($integrations !== null && !Formie::$plugin->getIntegrations()->savePostedCaptchaConfigs($integrations)) {
                    throw new \yii\web\BadRequestHttpException('Couldn’t save CAPTCHA settings.');
                }

                if (!Formie::$plugin->getSpamProtection()->saveFromSettings($settings)) {
                    throw new \yii\web\BadRequestHttpException('Couldn’t save spam rules.');
                }
            });

            $this->setSuccessFlash(Craft::t('formie', 'Settings saved.'));
            return $this->redirect('formie/spam-protection');
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
            Formie::$plugin->getCaptchaProviders()->stripFromPluginSettingsArray(
                Formie::$plugin->getSpamProtection()->stripFromPluginSettingsArray($settings->toArray()),
            ),
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


    // Private Methods
    // =========================================================================

    private function _encryptionStatus(): array
    {
        try {
            $key = ContentEncryption::currentKey();

            return [
                'state' => $key ? (ContentEncryption::activeVersion() >= 2 ? 'active' : 'ready') : 'notConfigured',
                'keyId' => $key?->id,
                'expectedKeyId' => ContentEncryption::expectedKeyId(),
                'error' => null,
            ];
        } catch (Throwable $exception) {
            return [
                'state' => 'error',
                'keyId' => null,
                'expectedKeyId' => ContentEncryption::expectedKeyId(),
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function _hasEncryptedFields(): bool
    {
        foreach (FieldTraversal::recursively(Formie::$plugin->getFields()->getAllFields()) as $field) {
            if ($field->enableContentEncryption) {
                return true;
            }
        }

        return false;
    }

    private function _saveCaptchaIntegrations(mixed $integrations): bool
    {
        $saved = Formie::$plugin->getIntegrations()->savePostedCaptchaConfigs($integrations);

        if (!$saved) {
            Formie::error('Couldn’t save captcha settings - {e}.', [
                'e' => Json::encode([
                    'integrationsPosted' => is_array($integrations),
                    'integrationCount' => is_array($integrations) ? count($integrations) : 0,
                    'errors' => Formie::$plugin->getIntegrations()->getLastCaptchaSaveErrors(),
                ]),
            ]);
        }

        return $saved;
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
