<?php
namespace verbb\formie\services;

use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\events\IntegrationEvent;
use verbb\formie\events\ModifyIntegrationsEvent;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\integrations\captchas\Duplicate;
use verbb\formie\integrations\captchas\Honeypot;
use verbb\formie\integrations\captchas\Javascript;
use verbb\formie\integrations\captchas\Recaptcha;

use Craft;
use craft\helpers\ArrayHelper;

use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

class Integrations extends Component
{
    // Constants
    // =========================================================================

    const EVENT_REGISTER_INTEGRATIONS = 'registerFormieIntegrations';
    const EVENT_MODIFY_INTEGRATIONS = 'modifyIntegrations';
    const EVENT_BEFORE_SAVE_INTEGRATION = 'beforeSaveIntegration';
    const EVENT_AFTER_SAVE_INTEGRATION = 'afterSaveIntegration';
    const CONFIG_INTEGRATIONS_KEY = 'formie.integrations';


    // Properties
    // =========================================================================

    private $_integrations;


    // Public Methods
    // =========================================================================

    /**
     * Returns all registered integrations.
     *
     * @return array
     */
    public function getRegisteredIntegrations(): array
    {
        $captchas = [
            Recaptcha::class,
            Duplicate::class,
            Honeypot::class,
            Javascript::class,
        ];

        $event = new RegisterIntegrationsEvent([
            'captchas' => $captchas,
        ]);

        $this->trigger(self::EVENT_REGISTER_INTEGRATIONS, $event);

        return ['captcha' => $event->captchas];
    }

    /**
     * Returns all integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllIntegrations(): array
    {
        $registeredIntegrations = $this->getRegisteredIntegrations();

        $projectConfig = Craft::$app->getProjectConfig();

        if ($this->_integrations !== null) {
            return $this->_integrations;
        }

        foreach ($registeredIntegrations as $type => $registeredIntegration) {
            foreach ($registeredIntegration as $integrationClass) {
                $integration = new $integrationClass();
                $integration->type = $type;

                // Load in any settings from Projcet config
                $data = $projectConfig->get(self::CONFIG_INTEGRATIONS_KEY . '.' . $integration->handle);

                $integration->enabled = $data['enabled'] ?? false;
                $integration->settings = $data['settings'] ?? [];

                $this->_integrations[] = $integration;
            }
        }

        // Fire a 'modifyIntegrations' event
        $event = new ModifyIntegrationsEvent([
            'integrations' => $this->_integrations,
        ]);
        $this->trigger(self::EVENT_MODIFY_INTEGRATIONS, $event);

        return $event->integrations;
    }

    /**
     * Returns an integration by it's handle.
     *
     * @param $handle
     * @return IntegrationInterface|null
     */
    public function getIntegrationByHandle($handle)
    {
        return ArrayHelper::firstWhere($this->getAllIntegrations(), 'handle', $handle, false);
    }

    /**
     * Returns all enabled integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllEnabledIntegrations(): array
    {
        return ArrayHelper::where($this->getAllIntegrations(), 'enabled', true, false);
    }

    /**
     * Returns all CAPTCHA integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllCaptchas(): array
    {
        return ArrayHelper::where($this->getAllIntegrations(), 'type', 'captcha', false);
    }

    /**
     * Returns all enabled CAPTCHA integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllEnabledCaptchas(): array
    {
        return ArrayHelper::where($this->getAllEnabledIntegrations(), 'type', 'captcha', false);
    }

    /**
     * Returns all enabled captchas for the provided form.
     *
     * @param Form $form
     * @return string
     */
    public function getAllEnabledCaptchasForForm(Form $form, $page = null): array
    {
        $enabledCaptchas = [];

        // If captchas are disabled globally, they aren't available at all, so check per-form
        $captchas = $this->getAllEnabledCaptchas();
        $formCaptchas = $form->getCaptchas();

        foreach ($captchas as $captcha) {
            // Check if this is a multi-page form, because by default, we want to only show it
            // on the last page. But also check the form setting if this is enabled to show on each page.
            if ($form->hasMultiplePages()) {
                // Only show the captcha on the last page - unless we specify otherwise in settings
                if (!$captcha->showAllPages && !$form->isLastPage($page)) {
                    continue;
                }
            }

            // Add all global captchas
            if ($captcha->enabled) {
                $enabledCaptchas[$captcha->handle] = $captcha;
            }

            // Then check if there are any form captcha settings, which override
            foreach ($formCaptchas as $formCaptcha) {
                if (!$formCaptcha->enabled) {
                    unset($enabledCaptchas[$formCaptcha->handle]);
                }
            }
        }

        $enabledCaptchas = array_values($enabledCaptchas);

        return $enabledCaptchas;
    }

    /**
     * Returns CAPTCHA HTML for the provided form.
     *
     * @param Form $form
     * @return string
     */
    public function getCaptchasHtmlForForm(Form $form, $page = null)
    {
        $html = '';

        $captchas = $this->getAllEnabledCaptchasForForm($form, $page);

        foreach ($captchas as $captcha) {
            $html .= $captcha->getFrontEndHtml($form, $page);
        }

        return $html;
    }

    /**
     * Saves an integrations settings.
     *
     * @param Integration $integration
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function saveIntegrationSettings(Integration $integration): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Fire an 'afterSaveIntegration' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_INTEGRATION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }

        $configData = [
            'enabled' => $integration->enabled,
            'settings' => $integration->settings,
        ];

        $configPath = self::CONFIG_INTEGRATIONS_KEY . '.' . $integration->handle;
        $projectConfig->set($configPath, $configData);

        // Fire an 'afterSaveIntegration' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_INTEGRATION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }

        return true;
    }

}
