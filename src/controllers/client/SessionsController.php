<?php
namespace verbb\formie\controllers\client;

use verbb\formie\Formie;
use verbb\formie\client\models\SessionRefreshRequest;
use verbb\formie\controllers\CrossOriginRequestTrait;

use craft\web\Controller;

use yii\web\Response;

class SessionsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['refresh'];

    
    // Traits
    // =========================================================================

    use CrossOriginRequestTrait;
    use ClientGuestCsrfTrait;
    

    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $this->configureGuestCsrfValidation(['refresh']);

        return parent::beforeAction($action);
    }

    public function actionRefresh(): Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $this->requirePostRequest();

        $session = Formie::$plugin->getClientSessionService()->refreshSession(new SessionRefreshRequest([
            'handle' => (string)$this->request->getBodyParam('handle', $this->request->getParam('handle', '')),
            'siteId' => $this->request->getBodyParam('siteId') ? (int)$this->request->getBodyParam('siteId') : null,
            'session' => (array)$this->request->getBodyParam('session', []),
        ]), true);

        $this->response->setNoCacheHeaders();

        return $this->asJson($session->toArrayRecursive());
    }
}
