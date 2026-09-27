<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;
use verbb\formie\models\payments\PaymentWebhookCommand;

use craft\web\Controller;

use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentWebhooksController extends Controller
{
    // Properties
    // =========================================================================

    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = ['process-webhook'];


    // Public Methods
    // =========================================================================

    public function actionProcessWebhook(): Response
    {
        $integrationUid = trim((string)$this->request->getParam('integrationUid', ''));
        $legacyHandle = Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled()
            ? trim((string)$this->request->getParam('handle', ''))
            : '';

        if ($integrationUid === '' && $legacyHandle === '') {
            throw new NotFoundHttpException('Integration not found');
        }

        $integration = $integrationUid !== ''
            ? Formie::$plugin->getIntegrations()->getIntegrationByUid($integrationUid)
            : Formie::$plugin->getIntegrations()->getIntegrationByHandle($legacyHandle);

        if (!($integration instanceof Payment)) {
            throw new NotFoundHttpException('Integration not found');
        }

        return $integration->receiveWebhook(PaymentWebhookCommand::fromRequest((int)$integration->id));
    }
}
