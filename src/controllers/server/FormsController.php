<?php
namespace verbb\formie\controllers\server;

use verbb\formie\compatibility\client\RefreshTokensCompatibility;
use verbb\formie\helpers\SiteHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\CrossOriginRequestTrait;
use verbb\formie\elements\Form;

use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FormsController extends Controller
{
    // Traits
    // =========================================================================

    use CrossOriginRequestTrait;


    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['refresh-tokens', 'render'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (in_array($action->id, ['refresh-tokens', 'render'], true)) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionRefreshTokens(): Response
    {
        if ($response = $this->handleCrossOriginRequest(['GET', 'OPTIONS'])) {
            return $response;
        }

        if (!$this->request->getIsGet()) {
            throw new MethodNotAllowedHttpException('GET request required');
        }

        $form = $this->_getRequestForm();

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $renderId = trim((string)$this->request->getParam('renderId', ''));

        if ($renderId !== '') {
            $form->setRenderId($renderId);
        }

        $this->response->setNoCacheHeaders();

        return $this->asJson(RefreshTokensCompatibility::applyLegacyPayload(
            Formie::$plugin->getServerRenderPayloadBuilder()->buildRefreshTokensPayload($form)
        ));
    }

    public function actionRender(): Response
    {
        if ($response = $this->handleCrossOriginRequest()) {
            return $response;
        }

        $form = $this->_getRequestForm();

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $renderOptions = (array)$this->request->getParam('renderOptions', []);
        $renderOptions['includeCss'] = false;
        $renderOptions['includeJs'] = false;
        $renderOptions['includeScriptsInline'] = true;
        $renderOptions['mode'] = 'html';
        $renderOptions['endpoint'] = $renderOptions['endpoint'] ?? UrlHelper::actionUrl('formie/server/forms/render');

        $this->response->setNoCacheHeaders();

        return $this->asJson(Formie::$plugin->getServerRenderPayloadBuilder()->buildServerRenderPayload($form, $renderOptions));
    }


    // Private Methods
    // =========================================================================

    private function _getRequestForm(): ?Form
    {
        $formHandle = RefreshTokensCompatibility::resolveRequestedHandle($this->request);

        if ($formHandle === '') {
            return null;
        }

        $siteId = SiteHelper::resolveRequestSiteId(
            $this->request->getParam('siteId'),
            $this->request->getParam('siteHandle'),
        );

        return Formie::$plugin->getForms()->getFormByHandle($formHandle, $siteId);
    }
}
