<?php
namespace verbb\formie\controllers\client;

use verbb\formie\Formie;
use verbb\formie\client\models\SubmitRequest;
use verbb\formie\controllers\AnonymousSiteRequestGuardTrait;
use verbb\formie\controllers\CrossOriginRequestTrait;
use verbb\formie\helpers\SiteHelper;

use craft\web\Controller;

use yii\web\Response;

class SubmissionsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['submit' => self::ALLOW_ANONYMOUS_LIVE];
    

    // Traits
    // =========================================================================

    use CrossOriginRequestTrait;
    use AnonymousSiteRequestGuardTrait;

    
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $this->forbidGuestControlPanelAnonymousActions($action->id);
        $profile = \verbb\formie\helpers\BrowserRequestProfile::enter($action->id === 'load');
        if ($profile === \verbb\formie\helpers\BrowserRequestProfile::CROSS_ORIGIN) {
            $this->enableCsrfValidation = false;
        }
        $this->enableCsrfValidation = $profile === \verbb\formie\helpers\BrowserRequestProfile::SAME_ORIGIN;

        return parent::beforeAction($action);
    }

    public function actionSubmit(): Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $this->requirePostRequest();

        $result = Formie::$plugin->getSubmissionRequests()->execute(new SubmitRequest([
            'handle' => (string)$this->request->getBodyParam('handle', $this->request->getParam('handle', '')),
            'operationId' => $this->request->getBodyParam('operationId'),
            'action' => (string)$this->request->getBodyParam('action', 'submit'),
            'siteId' => SiteHelper::resolveSiteIdFromRequest(),
            'session' => (array)$this->request->getBodyParam('session', []),
            'values' => (array)$this->request->getBodyParam('values', []),
            'browserData' => (array)$this->request->getBodyParam('browserData', []),
        ]), \verbb\formie\enums\SubmissionAuthorityType::VISITOR);

        $this->response->setNoCacheHeaders();
        $this->response->setStatusCode($result->httpStatus);

        return $this->asJson($result->toArrayRecursive());
    }
}
