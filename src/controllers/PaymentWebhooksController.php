<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
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

    /**
     * @param null $handle
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function actionProcessWebhook(): Response
    {
        $handle = $this->request->getRequiredParam('handle');

        if (!$integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle)) {
            throw new NotFoundHttpException('Integration not found');
        }

        if (!($integration instanceof Payment)) {
            throw new BadRequestHttpException('Invalid integration: ' . $handle);
        }

        return $integration->processWebhooks();
    }
}
