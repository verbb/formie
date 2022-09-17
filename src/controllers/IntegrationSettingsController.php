<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\helpers\Plugin;
use verbb\formie\models\MissingIntegration;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
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

        $request = Craft::$app->getRequest();
        $integrationsService = Formie::$plugin->getIntegrations();

        $errors = [];

        foreach ($request->getParam('integrations') as $handle => $integrationConfig) {
            // Force the `saveSpam` setting to be boolean, to allow us to check for backward compatibility
            if (isset($integrationConfig['saveSpam'])) {
                $integrationConfig['saveSpam'] = (bool)$integrationConfig['saveSpam'];
            }

            $integration = $integrationsService->createIntegration($integrationConfig);

            if (!Formie::$plugin->getIntegrations()->saveCaptcha($integration)) {
                $errors[] = true;
            }
        }

        if ($errors) {
            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save integration settings.'));

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Integration settings saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return Response
     */
    public function actionAddressProviderIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_ADDRESS_PROVIDER);
        $typeName = 'Address Providers';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    /**
     * Edit an integration.
     *
     * @param int|null $integrationId The integrations’ ID, if editing an existing integration.
     * @param IntegrationInterface|null $integration The integration being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested integration cannot be found
     */
    public function actionEditAddressProvider(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Address Providers', Integration::TYPE_ADDRESS_PROVIDER);
    }

    /**
     * @return Response
     */
    public function actionElementIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_ELEMENT);
        $typeName = 'Elements';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    /**
     * Edit an integration.
     *
     * @param int|null $integrationId The integrations’ ID, if editing an existing integration.
     * @param IntegrationInterface|null $integration The integration being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested integration cannot be found
     */
    public function actionEditElement(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Elements', Integration::TYPE_ELEMENT);
    }

    /**
     * @return Response
     */
    public function actionEmailMarketingIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_EMAIL_MARKETING);
        $typeName = 'Email Marketing';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    /**
     * Edit an integration.
     *
     * @param int|null $integrationId The integrations’ ID, if editing an existing integration.
     * @param IntegrationInterface|null $integration The integration being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested integration cannot be found
     */
    public function actionEditEmailMarketing(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Email Marketing', Integration::TYPE_EMAIL_MARKETING);
    }

    /**
     * @return Response
     */
    public function actionCrmIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_CRM);
        $typeName = 'CRM';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    /**
     * Edit an integration.
     *
     * @param int|null $integrationId The integrations’ ID, if editing an existing integration.
     * @param IntegrationInterface|null $integration The integration being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested integration cannot be found
     */
    public function actionEditCrm(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'CRM', Integration::TYPE_CRM);
    }

    /**
     * @return Response
     */
    public function actionPaymentIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_PAYMENT);
        $typeName = 'Payments';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    /**
     * Edit an integration.
     *
     * @param int|null $integrationId The integrations’ ID, if editing an existing integration.
     * @param IntegrationInterface|null $integration The integration being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested integration cannot be found
     */
    public function actionEditPayment(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Payments', Integration::TYPE_PAYMENT);
    }

    /**
     * @return Response
     */
    public function actionWebhookIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_WEBHOOK);
        $typeName = 'Webhooks';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    /**
     * Edit an integration.
     *
     * @param int|null $integrationId The integrations’ ID, if editing an existing integration.
     * @param IntegrationInterface|null $integration The integration being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested integration cannot be found
     */
    public function actionEditWebhook(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Webhooks', Integration::TYPE_WEBHOOK);
    }

    /**
     * @return Response
     */
    public function actionMiscellaneousIndex(): Response
    {
        $integrations = Formie::$plugin->getIntegrations()->getAllIntegrationsForType(Integration::TYPE_MISC);
        $typeName = 'Miscellaneous';

        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'typeName'));
    }

    /**
     * Edit an integration.
     *
     * @param int|null $integrationId The integrations’ ID, if editing an existing integration.
     * @param IntegrationInterface|null $integration The integration being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested integration cannot be found
     */
    public function actionEditMiscellaneous(int $integrationId = null, IntegrationInterface $integration = null): Response
    {
        return $this->_editIntegration($integrationId, $integration, 'Miscellaneous', Integration::TYPE_MISC);
    }


    // Private Methods
    // =========================================================================

    public function _editIntegration($integrationId, $integration, $typeName, $typeHandle): Response
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
