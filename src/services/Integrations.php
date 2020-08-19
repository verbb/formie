<?php
namespace verbb\formie\services;

use verbb\formie\base\Captcha;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\events\IntegrationEvent;
use verbb\formie\events\ModifyIntegrationsEvent;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\integrations\addressproviders\AddressFinder;
use verbb\formie\integrations\addressproviders\Algolia;
use verbb\formie\integrations\addressproviders\Google;
use verbb\formie\integrations\captchas\Duplicate;
use verbb\formie\integrations\captchas\Honeypot;
use verbb\formie\integrations\captchas\Javascript;
use verbb\formie\integrations\captchas\Recaptcha;
use verbb\formie\integrations\crm\ActiveCampaign as ActiveCampaignCrm;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\integrations\emailmarketing\ActiveCampaign;
use verbb\formie\integrations\emailmarketing\Autopilot;
use verbb\formie\integrations\emailmarketing\AWeber;
use verbb\formie\integrations\emailmarketing\Benchmark;
use verbb\formie\integrations\emailmarketing\CampaignMonitor;
use verbb\formie\integrations\emailmarketing\ConstantContact;
use verbb\formie\integrations\emailmarketing\ConvertKit;
use verbb\formie\integrations\emailmarketing\Drip;
use verbb\formie\integrations\emailmarketing\GetResponse;
use verbb\formie\integrations\emailmarketing\iContact;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\integrations\emailmarketing\MailerLite;
use verbb\formie\integrations\emailmarketing\Moosend;
use verbb\formie\integrations\emailmarketing\Omnisend;
use verbb\formie\integrations\emailmarketing\Ontraport;
use verbb\formie\integrations\emailmarketing\Sender;
use verbb\formie\integrations\emailmarketing\Sendinblue;

use Craft;
use craft\helpers\ArrayHelper;

use verbb\formie\models\FieldLayoutPage;
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
        $addressProviders = [
            Google::class,
            Algolia::class,
            AddressFinder::class,
        ];

        $captchas = [
            Recaptcha::class,
            Duplicate::class,
            Honeypot::class,
            Javascript::class,
        ];

        $elements = [
            Entry::class,
        ];

        $emailMarketing = [
            ActiveCampaign::class,
            Autopilot::class,
            AWeber::class,
            Benchmark::class,
            CampaignMonitor::class,
            ConstantContact::class,
            ConvertKit::class,
            Drip::class,
            GetResponse::class,
            iContact::class,
            Mailchimp::class,
            MailerLite::class,
            Moosend::class,
            Omnisend::class,
            Ontraport::class,
            Sender::class,
            Sendinblue::class,
        ];

        $crm = [
            ActiveCampaignCrm::class,
        ];

        $event = new RegisterIntegrationsEvent([
            'addressProviders' => $addressProviders,
            'captchas' => $captchas,
            'elements' => $elements,
            'emailMarketing' => $emailMarketing,
            'crm' => $crm,
        ]);

        $this->trigger(self::EVENT_REGISTER_INTEGRATIONS, $event);

        return [
            'addressProvider' => $event->addressProviders,
            'captcha' => $event->captchas,
            'element' => $event->elements,
            'emailMarketing' => $event->emailMarketing,
            'crm' => $event->crm,
        ];
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

        $integrations = [];

        foreach ($registeredIntegrations as $type => $registeredIntegration) {
            foreach ($registeredIntegration as $integrationClass) {
                $integration = new $integrationClass();
                $integration->type = $type;

                // Load in any settings from Projcet config
                $data = $projectConfig->get(self::CONFIG_INTEGRATIONS_KEY . '.' . $integration->handle);

                $integration->enabled = $data['enabled'] ?? false;
                $integration->settings = $data['settings'] ?? [];

                $integrations[] = $integration;
            }
        }

        // Fire a 'modifyIntegrations' event
        $event = new ModifyIntegrationsEvent([
            'integrations' => $integrations,
        ]);
        $this->trigger(self::EVENT_MODIFY_INTEGRATIONS, $event);

        return $event->integrations;
    }

    /**
     * Returns all integrations, grouped by their type.
     *
     * @param bool $formOnly
     * @return array
     */
    public function getAllGroupedIntegrations($formOnly = false)
    {
        $grouped = [];

        if ($formOnly) {
            $integrations = $this->getAllFormIntegrations();
        } else {
            $integrations = $this->getAllIntegrations();
        }

        foreach ($integrations as $key => $integration) {
            $grouped[$integration->type][] = $integration;
        }

        return $grouped;
    }

    /**
     * Returns all integrations allowed to be used on a form element.
     *
     * @return array
     */
    public function getAllFormIntegrations()
    {
        $integrations = [];

        foreach ($this->getAllIntegrations() as $key => $integration) {
            if (!$integration->hasFormSettings()) {
                continue;
            }

            $integrations[] = $integration;
        }

        return $integrations;
    }

    /**
     * Returns an integration by it's handle.
     *
     * @param string $handle
     * @return IntegrationInterface|null
     */
    public function getIntegrationByHandle($handle)
    {
        return ArrayHelper::firstWhere($this->getAllIntegrations(), 'handle', $handle, false);
    }

    /**
     * Returns all CAPTCHA integrations.
     *
     * @param string|int|null $type
     * @return IntegrationInterface[]
     */
    public function getAllIntegrationsByType($type): array
    {
        if ($type) {
            return ArrayHelper::where($this->getAllIntegrations(), 'type', $type, false);
        }

        return $this->getAllIntegrations();
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
     * Returns all address integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllAddressProviders(): array
    {
        return ArrayHelper::where($this->getAllIntegrations(), 'type', 'addressProvider', false);
    }

    /**
     * Returns all enabled address integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllEnabledAddressProviders(): array
    {
        return ArrayHelper::where($this->getAllEnabledIntegrations(), 'type', 'addressProvider', false);
    }

    /**
     * Returns all element integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllElements(): array
    {
        return ArrayHelper::where($this->getAllIntegrations(), 'type', 'element', false);
    }

    /**
     * Returns all enabled element integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllEnabledElements(): array
    {
        return ArrayHelper::where($this->getAllEnabledIntegrations(), 'type', 'element', false);
    }

    /**
     * Returns all email marketing integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllEmailMarketing(): array
    {
        return ArrayHelper::where($this->getAllIntegrations(), 'type', 'emailMarketing', false);
    }

    /**
     * Returns all enabled email marketing integrations.
     *
     * @return IntegrationInterface[]
     */
    public function getAllEnabledEmailMarketing(): array
    {
        return ArrayHelper::where($this->getAllEnabledIntegrations(), 'type', 'emailMarketing', false);
    }

    /**
     * Returns all enabled captchas for the provided form.
     *
     * @param Form $form
     * @param FieldLayoutPage|null $page
     * @return array
     */
    public function getAllEnabledCaptchasForForm(Form $form, $page = null): array
    {
        $enabledCaptchas = [];

        // If captchas are disabled globally, they aren't available at all, so check per-form
        /* @var Captcha[] $captchas */
        $captchas = $this->getAllEnabledCaptchas();
        $formCaptchas = $form->getIntegrations('captcha');

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
     * @param FieldLayoutPage|null $page
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
     * Returns all enabled integrations for the provided form and type.
     *
     * @param Form $form
     * @param string|null $type
     * @return array
     */
    public function getAllEnabledIntegrationsForForm(Form $form, $type = null): array
    {
        $enabledIntegrations = [];

        // If integrations are disabled globally, they aren't available at all, so check per-form
        $integrations = $this->getAllIntegrationsByType($type);
        $formIntegrations = $form->getIntegrations($type);

        foreach ($integrations as $integration) {
            // Add all global integrations
            if ($integration->enabled) {
                // Find the form settings, and use that firstly, then merge in settings at the plugin-level
                foreach ($formIntegrations as $formIntegration) {
                    if ($formIntegration->handle == $integration->handle) {
                        $settings = array_merge($integration->settings, $formIntegration->settings);

                        // Combine all attributes and settings at form-level and plugin-level
                        $enabledIntegrations[$integration->handle] = $formIntegration;
                        $enabledIntegrations[$integration->handle]->settings = $settings;
                    }
                }
            }

            // Then check if there are any form integration settings, which override
            foreach ($formIntegrations as $formIntegration) {
                if (!$formIntegration->enabled) {
                    unset($enabledIntegrations[$formIntegration->handle]);
                }
            }
        }

        $enabledIntegrations = array_values($enabledIntegrations);

        return $enabledIntegrations;
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

        // Allow integrations to perform actions before their settings are saved
        if (!$integration->beforeSave()) {
            return false;
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

        $integration->afterSave();

        return true;
    }

}
