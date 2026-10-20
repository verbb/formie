<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\SpamProtectionEditor;
use verbb\formie\helpers\SpamProtectionSettings;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\captchas\Akismet;
use verbb\formie\integrations\captchas\CleanTalk;
use verbb\formie\integrations\captchas\OopSpam;
use verbb\formie\models\MissingIntegration;
use verbb\formie\services\SpamProtection;

use Craft;
use craft\db\Query;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SpamProtectionController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $permissions = Formie::$plugin->getPermissions();
        $user = Craft::$app->getUser()->getIdentity();

        if ($permissions->canManageSpamRules($user)) {
            return $this->redirect('formie/spam-protection/rules');
        }

        SpamProtectionSettings::requireManager('captcha:all');
        return $this->redirect('formie/spam-protection/captchas');
    }

    public function actionRules(): Response
    {
        SpamProtectionSettings::requireManager(SpamProtectionSettings::RULES);
        $settings = clone Formie::$plugin->getSettings();
        $settings->setAttributes(Formie::$plugin->getSpamProtection()->getSettingsValues(), false);

        return $this->renderTemplate('formie/spam-protection/rules', [
            ...$this->_variables(SpamProtectionSettings::RULES),
            'settings' => $settings,
        ]);
    }

    public function actionCaptchas(?string $handle = null): Response
    {
        SpamProtectionSettings::requireManager('captcha:all');
        $providers = Formie::$plugin->getIntegrations()->getAllCaptchas();
        $handle ??= $providers[0]->handle ?? '';
        $provider = Formie::$plugin->getIntegrations()->getCaptchaByHandle($handle);
        $available = $provider && !$provider instanceof MissingIntegration;
        $provider = $available ? clone $provider : null;
        $warning = $provider ? null : Craft::t('formie', 'Provider unavailable. Saved settings and form references have been preserved.');

        if ($provider && $provider->getEnabled() && (!$provider->hasValidSettings() || SpamProtectionEditor::hasMissingEnvironmentVariable($provider))) {
            $warning = SpamProtectionEditor::availability($provider);
        }
        $secrets = $provider ? SpamProtectionEditor::mask($provider) : [];
        $variables = $this->_variables('captcha:' . $handle);
        $items = $variables['providers'];

        if (!isset($items[$handle])) {
            throw new BadRequestHttpException('Unknown CAPTCHA provider.');
        }

        return $this->renderTemplate('formie/spam-protection/captchas', [
            ...$variables,
            'provider' => $provider,
            'providerName' => $items[$handle]['name'],
            'handle' => $handle,
            'secrets' => $secrets,
            'warning' => $warning,
        ]);
    }

    public function actionSaveRules(): Response
    {
        $this->requirePostRequest();
        SpamProtectionSettings::requireManager(SpamProtectionSettings::RULES);
        $values = $this->request->getRequiredBodyParam('settings');

        if (!is_array($values) || array_diff(array_keys($values), SpamProtection::SETTING_KEYS)) {
            throw new BadRequestHttpException('Invalid spam rules.');
        }

        SpamProtectionSettings::atomic(function() use ($values) {
            SpamProtectionSettings::assertCurrent(SpamProtectionSettings::RULES, (string)$this->request->getRequiredBodyParam('token'));
            $settings = clone Formie::$plugin->getSettings();
            $settings->setAttributes(Formie::$plugin->getSpamProtection()->getSettingsValues(), false);
            $settings->setAttributes($values, false);

            if (!$settings->validate(SpamProtection::SETTING_KEYS)) {
                throw new BadRequestHttpException(Craft::t('formie', 'Couldn’t save spam rules. Check the entered values.'));
            }

            if (!Formie::$plugin->getSpamProtection()->saveFromSettings($settings)) {
                throw new ForbiddenHttpException('These spam rules could not be saved.');
            }
        });

        $this->setSuccessFlash(Craft::t('formie', 'Spam rules saved.'));
        return $this->redirect('formie/spam-protection/rules');
    }

    public function actionSaveCaptcha(): Response
    {
        $this->requirePostRequest();
        $handle = (string)$this->request->getRequiredBodyParam('handle');
        SpamProtectionSettings::requireManager('captcha:' . $handle);
        $config = $this->request->getRequiredBodyParam('provider');

        if (!is_array($config)) {
            throw new BadRequestHttpException('Invalid CAPTCHA settings.');
        }

        SpamProtectionSettings::atomic(function() use ($handle, $config) {
            SpamProtectionSettings::assertCurrent('captcha:' . $handle, (string)$this->request->getRequiredBodyParam('token'));
            $provider = SpamProtectionEditor::postedProvider($handle, $config, clearEmptySecrets: true);

            if (!Formie::$plugin->getCaptchaProviders()->saveProvider($provider)) {
                throw new ForbiddenHttpException('These CAPTCHA settings cannot be saved.');
            }
        });

        $this->setSuccessFlash(Craft::t('formie', 'CAPTCHA settings saved.'));
        return $this->redirect('formie/spam-protection/captchas/' . $handle);
    }

    // Private Methods
    // =========================================================================

    private function _variables(string $subject): array
    {
        $permissions = Formie::$plugin->getPermissions();
        $user = Craft::$app->getUser()->getIdentity();
        $providers = [];
        $legacyCaptchaUrls = [];

        if ($permissions->canManageCaptchas($user)) {
            $legacyCaptchaUrls['captchas'] = UrlHelper::cpUrl('formie/spam-protection/captchas');

            foreach (Formie::$plugin->getIntegrations()->getAllCaptchas() as $provider) {
                $providers[$provider->handle] = [
                    'handle' => $provider->handle,
                    'name' => $provider->getName(),
                    'enabled' => (bool)$provider->getEnabled(),
                    'iconUrl' => $provider instanceof MissingIntegration ? '' : $provider->getCpIconUrl(),
                    'group' => $provider instanceof Akismet || $provider instanceof CleanTalk || $provider instanceof OopSpam ? 'Spam Filtering Services' : 'CAPTCHAs',
                ];
                $legacyCaptchaUrls['tab-' . $provider->handle] = UrlHelper::cpUrl('formie/spam-protection/captchas/' . $provider->handle);
            }

            foreach ((new Query())->select('handle')->from(Table::FORMIE_CAPTCHA_PROVIDERS)->column() as $handle) {
                if (!isset($providers[$handle])) {
                    $providers[$handle] = ['handle' => $handle, 'name' => $handle . ' (' . Craft::t('formie', 'Unavailable') . ')', 'enabled' => false, 'iconUrl' => '', 'group' => 'CAPTCHAs'];
                }
            }
        }

        return [
            'legacyCaptchaUrls' => $legacyCaptchaUrls,
            'subject' => $subject,
            'settingsToken' => SpamProtectionSettings::token($subject),
            'canManageSpamRules' => $permissions->canManageSpamRules($user),
            'providers' => $providers,
        ];
    }


}
