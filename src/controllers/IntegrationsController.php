<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\models\Settings;

use Craft;
use craft\web\Controller;

use yii\web\HttpException;
use yii\web\Response;

class IntegrationsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $integrations = Formie::$plugin->getintegrations()->getAllIntegrations();
        $groupedIntegrations = Formie::$plugin->getintegrations()->getAllGroupedIntegrations();
        
        return $this->renderTemplate('formie/settings/integrations', compact('integrations', 'groupedIntegrations'));
    }

    public function actionSaveSettings()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $errors = [];

        foreach ($request->getParam('integrations') as $handle => $integrationConfig) {
            $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);
            $integration->enabled = $integrationConfig['enabled'] ?? false;
            $integration->settings = $integrationConfig['settings'] ?? [];

            if (!Formie::$plugin->getIntegrations()->saveIntegrationSettings($integration)) {
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

}
