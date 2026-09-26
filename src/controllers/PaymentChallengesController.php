<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\integrations\payments\Opayo;

use craft\web\Controller;

use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentChallengesController extends Controller
{
    // Properties
    // =========================================================================

    // Cross-origin provider POST, authenticated by a challenge-only capability and provider API.
    public $enableCsrfValidation = false;
    protected array|bool|int $allowAnonymous = ['complete'];


    // Public Methods
    // =========================================================================

    public function actionComplete(): Response
    {
        $this->requirePostRequest();
        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle((string)$this->request->getRequiredParam('handle'));
        if (!$integration instanceof Opayo) {
            throw new NotFoundHttpException('Payment provider not found.');
        }
        return $integration->completeChallenge();
    }
}
