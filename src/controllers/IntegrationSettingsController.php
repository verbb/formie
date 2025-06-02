<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\MissingIntegration;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\NotFoundHttpException;
use yii\web\Response;

class IntegrationSettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionCaptchaIndex(): Response
    {
        $groupedIntegrations = Formie::$plugin->getIntegrations()->getAllGroupedCaptchas();

        return $this->renderTemplate('formie/settings/captchas', compact('groupedIntegrations'));
    }

    public function actionSaveCaptchas(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;
        $integrationsService = Formie::$plugin->getIntegrations();

        $errors = [];

        foreach ($request->getParam('integrations') as $handle => $integrationConfig) {
            // Force the `saveSpam` setting to be boolean, to allow us to check for backward compatibility
            if (isset($integrationConfig['saveSpam'])) {
                $integrationConfig['saveSpam'] = (bool)$integrationConfig['saveSpam'];
            }

            $integration = $integrationsService->createIntegration($integrationConfig);

            if (!Formie::$plugin->getIntegrations()->saveCaptcha($integration)) {
                $errors[] = $integration->getErrors();
            }
        }

        if ($errors) {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save integration settings.'));

            Formie::error('Couldn’t save integration settings - {e}.', ['e' => Json::encode($errors)]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('formie', 'Integration settings saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionAddressProviderIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_ADDRESS_PROVIDER);
        $typeName = 'Address Providers';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    public function actionEditAddressProvider(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Address Providers', Integration::TYPE_ADDRESS_PROVIDER);
    }

    public function actionElementIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_ELEMENT);
        $typeName = 'Elements';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    public function actionEditElement(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Elements', Integration::TYPE_ELEMENT);
    }

    public function actionEmailMarketingIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_EMAIL_MARKETING);
        $typeName = 'Email Marketing';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    public function actionEditEmailMarketing(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Email Marketing', Integration::TYPE_EMAIL_MARKETING);
    }

    public function actionCrmIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_CRM);
        $typeName = 'CRM';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    public function actionEditCrm(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'CRM', Integration::TYPE_CRM);
    }

    public function actionPaymentIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_PAYMENT);
        $typeName = 'Payments';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    public function actionEditPayment(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Payments', Integration::TYPE_PAYMENT);
    }

    public function actionAutomationIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_AUTOMATION);
        $typeName = 'Automations';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    public function actionEditAutomation(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Automations', Integration::TYPE_AUTOMATION);
    }

    public function actionMessagingIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_MESSAGING);
        $typeName = 'Messaging';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    public function actionEditMessaging(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Messaging', Integration::TYPE_MESSAGING);
    }

    public function actionHelpDeskIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_HELP_DESK);
        $typeName = 'Help Desk';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    public function actionEditHelpDesk(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Help Desk', Integration::TYPE_HELP_DESK);
    }

    public function actionMiscellaneousIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_MISC);
        $typeName = 'Miscellaneous';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    public function actionEditMiscellaneous(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Miscellaneous', Integration::TYPE_MISC);
    }


    // Private Methods
    // =========================================================================

    public function _editIntegration(?int $integrationId, ?IntegrationInterface $integration, string $typeName, string $typeHandle): Response
    {
        $integrations = Formie::$plugin->getIntegrations();

        $allIntegrationTypes = $integrations->getIntegrationTypes($typeHandle);

        $missingIntegrationPlaceholder = null;

        if ($integration === null) {
            $firstIntegrationType = ArrayHelper::firstValue($allIntegrationTypes);

            if ($integrationId !== null) {
                $integration = $integrations->getIntegrationById($integrationId);

                if ($integration === null) {
                    throw new NotFoundHttpException('Integration not found');
                }

                if ($integration instanceof MissingIntegration) {
                    $missingIntegrationPlaceholder = $integration->getPlaceholderHtml();
                    // $integration = $integration->createFallback($firstIntegrationType);
                }
            } else {
                $integration = $integrations->createIntegration($firstIntegrationType);
            }
        }

        // Make sure the selected integration class is in there
        if (!in_array(get_class($integration), $allIntegrationTypes, true)) {
            $allIntegrationTypes[] = get_class($integration);
        }

        $integrationInstances = [];
        $integrationTypeOptions = [];

        foreach ($allIntegrationTypes as $class) {
            $integrationInstances[$class] = $integrations->createIntegration($class);

            $integrationTypeOptions[] = [
                'value' => $class,
                'label' => $class::displayName(),
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($integrationTypeOptions, 'label', SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);

        $isNewIntegration = !$integration->id;

        if ($isNewIntegration) {
            $title = Craft::t('formie', 'Create a new integration');
        } else {
            $title = trim($integration->name) ?: Craft::t('app', 'Edit Integration');
        }

        $typeKebab = StringHelper::toKebabCase($typeName);

        $baseUrl = "formie/settings/$typeKebab";
        $continueEditingUrl = "formie/settings/$typeKebab/edit/{id}";

        Plugin::registerAsset('src/js/formie-integration-settings.js');

        return $this->renderTemplate('formie/settings/integrations/_edit', [
            'integration' => $integration,
            'isNewIntegration' => $isNewIntegration,
            'integrationTypes' => $allIntegrationTypes,
            'integrationTypeOptions' => $integrationTypeOptions,
            'missingIntegrationPlaceholder' => $missingIntegrationPlaceholder,
            'integrationInstances' => $integrationInstances,
            'baseUrl' => $baseUrl,
            'continueEditingUrl' => $continueEditingUrl,
            'title' => $title,
            'typeName' => $typeName,
        ]);
    }

}
