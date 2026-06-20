<?php
namespace verbb\formie\controllers\client;

use verbb\formie\Formie;
use verbb\formie\helpers\SiteHelper;
use verbb\formie\client\models\SubmitRequest;

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

    
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (in_array($action->id, ['submit'], true)) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionSubmit(): Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $this->requirePostRequest();

        $result = Formie::$plugin->getSubmissionProcessor()->execute(new SubmitRequest([
            'handle' => (string)$this->request->getBodyParam('handle', $this->request->getParam('handle', '')),
            'action' => (string)$this->request->getBodyParam('action', 'submit'),
            'siteId' => SiteHelper::resolveSiteIdFromRequest(),
            'session' => (array)$this->request->getBodyParam('session', []),
            'values' => (array)$this->request->getBodyParam('values', []),
        ]));

        $this->response->setNoCacheHeaders();

        return $this->asJson($result->toArrayRecursive());
    }
}
