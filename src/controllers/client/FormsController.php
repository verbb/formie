<?php
namespace verbb\formie\controllers\client;

use verbb\formie\Formie;
use verbb\formie\helpers\SiteHelper;
use verbb\formie\elements\Form;
use verbb\formie\client\models\LoadContext;
use verbb\formie\client\models\PageTransitionRequest;

use craft\web\Controller;

use yii\web\NotFoundHttpException;
use yii\web\Response;

class FormsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['load', 'page'];

    
    // Traits
    // =========================================================================

    use CrossOriginRequestTrait;


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (in_array($action->id, ['load', 'page'], true)) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionLoad(): Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $this->requirePostRequest();

        $form = $this->_getRequestForm();

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
            'handle' => (string)$this->request->getParam('handle', ''),
            'siteId' => SiteHelper::resolveSiteIdFromRequest(),
            'locale' => $this->request->getParam('locale') ?: null,
        ]));

        $this->response->setNoCacheHeaders();

        return $this->asJson($bootstrap->toArrayRecursive());
    }

    public function actionPage(): Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $this->requirePostRequest();

        $session = Formie::$plugin->getClientSessionService()->persistPageState(new PageTransitionRequest([
            'handle' => (string)$this->request->getBodyParam('handle', $this->request->getParam('handle', '')),
            'siteId' => SiteHelper::resolveSiteIdFromRequest(),
            'currentPageId' => $this->request->getBodyParam('currentPageId'),
            'targetPageId' => $this->request->getBodyParam('targetPageId'),
            'session' => (array)$this->request->getBodyParam('session', []),
            'values' => (array)$this->request->getBodyParam('values', []),
        ]), true);

        $this->response->setNoCacheHeaders();

        return $this->asJson($session->toArrayRecursive());
    }


    // Private Methods
    // =========================================================================

    private function _getRequestForm(): ?Form
    {
        $handle = trim((string)$this->request->getParam('handle', ''));

        if ($handle === '') {
            return null;
        }

        $siteId = SiteHelper::resolveRequestSiteId(
            $this->request->getParam('siteId'),
            $this->request->getParam('siteHandle'),
        );

        return Formie::$plugin->getForms()->getFormByHandle($handle, $siteId);
    }
}
