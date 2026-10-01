<?php
namespace verbb\formie\controllers\client;

use verbb\formie\Formie;
use verbb\formie\client\models\LoadContext;
use verbb\formie\client\models\PageTransitionRequest;
use verbb\formie\controllers\AnonymousSiteRequestGuardTrait;
use verbb\formie\controllers\CrossOriginRequestTrait;
use verbb\formie\helpers\SiteHelper;
use verbb\formie\elements\Form;

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

        // Initial bootstrap supplies the token required by subsequent mutations.
        // It reads a public form and retains the endpoint's CORS policy.
        if ($action->id === 'load') {
            $this->enableCsrfValidation = false;
        } else {
            $this->enableCsrfValidation = $profile === \verbb\formie\helpers\BrowserRequestProfile::SAME_ORIGIN;
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
            'query' => (array)$this->request->getBodyParam('query', []),
            'grantToken' => $this->request->getParam('grantToken'),
            'grantPurpose' => (string)$this->request->getParam('grantPurpose', 'continue-incomplete'),
            'draftContext' => $this->request->getParam('draftContext'),
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

        $result = Formie::$plugin->getClientSessionService()->persistPageState(new PageTransitionRequest([
            'handle' => (string)$this->request->getBodyParam('handle', $this->request->getParam('handle', '')),
            'siteId' => SiteHelper::resolveSiteIdFromRequest(),
            'currentPageId' => $this->request->getBodyParam('currentPageId'),
            'operationId' => $this->request->getBodyParam('operationId'),
            'targetPageId' => $this->request->getBodyParam('targetPageId'),
            'session' => (array)$this->request->getBodyParam('session', []),
            'values' => (array)$this->request->getBodyParam('values', []),
        ]), true);

        $this->response->setNoCacheHeaders();
        $this->response->setStatusCode($result->httpStatus);

        return $this->asJson($result->toArrayRecursive());
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
