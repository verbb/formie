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

    protected array|bool|int $allowAnonymous = ['process-webhook', 'process-callback'];


    // Public Methods
    // =========================================================================

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

    public function actionProcessCallback(): Response
    {
        // Query string overrides body param, which we sometimes don't want
        $handle = $this->request->getBodyParam('handle') ?: $this->request->getParam('handle');

        if (!$handle) {
            throw new NotFoundHttpException('Integration ' . $handle . ' not found');
        }

        if (!$integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle)) {
            throw new NotFoundHttpException('Integration not found');
        }

        if (!($integration instanceof Payment)) {
            throw new BadRequestHttpException('Invalid integration: ' . $handle);
        }

        return $integration->processCallbacks();
    }
}
