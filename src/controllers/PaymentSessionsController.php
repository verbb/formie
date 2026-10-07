<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\BrowserRequestProfile;
use verbb\formie\helpers\CrossOriginRequestHelper;
use verbb\formie\integrations\payments\Opayo;
use verbb\formie\models\payments\PaymentSessionCommand;

use craft\web\Controller;

use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentSessionsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['initialize'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $profile = BrowserRequestProfile::enter();
        CrossOriginRequestHelper::applyHeaders($this->request, $this->response);

        if ($profile === BrowserRequestProfile::CROSS_ORIGIN) {
            $this->enableCsrfValidation = false;
        }

        if ($this->request->getIsOptions()) {
            $this->response->setStatusCode(204);
            return false;
        }

        return parent::beforeAction($action);
    }

    public function actionInitialize(): Response
    {
        $this->requirePostRequest();
        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle((string)$this->request->getRequiredBodyParam('handle'));

        if (!$integration instanceof Opayo) {
            throw new NotFoundHttpException('Payment provider not found.');
        }
        return $integration->initializeSession(new PaymentSessionCommand((string)$this->request->getRequiredBodyParam('sessionToken'), $integration->id));
    }
}
