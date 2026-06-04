<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\FormTemplate;
use Craft;
use craft\base\Component;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class FormPreview extends Component
{
    // Constants
    // =========================================================================

    private const CACHE_KEY_PREFIX = 'formie-form-preview';
    private const CACHE_DURATION = 300;


    // Public Methods
    // =========================================================================

    public function createSession(array $bodyParams): string
    {
        $userId = Craft::$app->getUser()->getId();

        if (!$userId) {
            throw new ForbiddenHttpException('User not permitted to preview forms.');
        }

        $token = Craft::$app->getSecurity()->generateRandomString(32);
        $cacheKey = $this->_getCacheKey($userId, $token);

        Craft::$app->getCache()->set($cacheKey, $bodyParams, self::CACHE_DURATION);

        return $token;
    }

    public function getSessionBodyParams(string $token): array
    {
        $userId = Craft::$app->getUser()->getId();

        if (!$userId) {
            throw new ForbiddenHttpException('User not permitted to preview forms.');
        }

        $cacheKey = $this->_getCacheKey($userId, $token);
        $bodyParams = Craft::$app->getCache()->get($cacheKey);

        if (!is_array($bodyParams)) {
            throw new NotFoundHttpException('Form preview session not found or expired.');
        }

        return $bodyParams;
    }

    public function buildFormFromBodyParams(array $bodyParams): Form
    {
        $request = Craft::$app->getRequest();
        $originalBodyParams = $request->getBodyParams();

        $request->setBodyParams($bodyParams);

        try {
            $isStencil = ($bodyParams['entityType'] ?? '') === 'stencil'
                || ($bodyParams['isStencil'] ?? false);

            $form = $isStencil
                ? Formie::$plugin->getForms()->buildStencilFormFromPost()
                : Formie::$plugin->getForms()->buildFormFromPost();
        } finally {
            $request->setBodyParams($originalBodyParams);
        }

        return $this->prepareFormForPreview($form);
    }

    public function prepareFormForPreview(Form $form): Form
    {
        $form->templateId = null;
        $form->setTemplate(null);

        $settings = $form->getSettings();
        $settings->requireUser = false;
        $settings->scheduleForm = false;
        $settings->limitSubmissions = false;

        return $form;
    }

    public function renderPreviewFrame(string $token): array
    {
        $bodyParams = $this->getSessionBodyParams($token);
        $form = $this->buildFormFromBodyParams($bodyParams);

        $submission = new Submission();
        $submission->setForm($form);
        $form->setCurrentSubmission($submission);
        // Route path for actionInput() — not a full URL.
        $form->setActionUrl('formie/forms/preview-submit');

        // Inline assets in the iframe document — registered CP assets are not output with asRaw().
        $html = (string)Formie::$plugin->getRendering()->renderForm($form, [
            'useStockTemplates' => true,
            'previewMode' => true,
            'theme' => 'formie',
            'includeCss' => true,
            'includeJs' => true,
            'outputCss' => true,
            'outputJs' => true,
            'outputCssLocation' => FormTemplate::PAGE_FOOTER,
            'outputJsLocation' => FormTemplate::PAGE_HEADER,
            'customInputs' => [
                'previewToken' => $token,
            ],
        ]);

        return [
            'html' => $html,
            'previewKey' => $token,
        ];
    }

    public function setPreviewSubmittedFlash(string $token): void
    {
        $bodyParams = $this->getSessionBodyParams($token);
        $form = $this->buildFormFromBodyParams($bodyParams);

        Formie::$plugin->getService()->setFlash(
            $form->getFlashNamespace(),
            'notice',
            Craft::t('formie', 'Preview only — your submission was not saved.'),
        );

        Formie::$plugin->getService()->setFlash(
            $form->getFlashNamespace(),
            'submitted',
            true,
        );
    }

    public function getPreviewFrameUrl(string $token): string
    {
        return UrlHelper::cpUrl('formie/forms/preview-frame', [
            'previewKey' => $token,
        ]);
    }

    public function getSlideoutPaneNoticeHtml(): string
    {
        return Html::tag(
            'div',
            Html::tag('p', Craft::t('formie', 'Preview uses Formie’s default styling and behaviour. Custom form templates, site layouts, and project overrides are not shown. Submissions are not saved.')),
            [
                'id' => 'content-notice',
                'role' => 'status',
            ],
        );
    }

    public function getSlideoutContentHtml(string $previewKey): string
    {
        $frameUrl = $this->getPreviewFrameUrl($previewKey);
        $iframe = Html::tag('iframe', '', [
            'src' => $frameUrl,
            'title' => Craft::t('formie', 'Form Preview'),
            'class' => 'formie-form-preview-iframe',
        ]);

        return Html::tag('div', $iframe, [
            'class' => 'formie-form-preview-layout',
        ]);
    }

    public function getSlideoutToolbarHtml(): string
    {
        return $this->getSlideoutPaneNoticeHtml();
    }

    public function registerSlideoutPaneHeader(string $containerId): void
    {
        // CpScreenSlideout hides .pane-header unless showHeader/tabs/cp link are set.
        Craft::$app->getView()->registerJsWithVars(
            fn(string $containerId) => <<<JS
(() => {
    const container = document.getElementById($containerId);

    if (!container) {
        return;
    }

    const cpScreen = $(container).data('cpScreen');

    if (cpScreen) {
        cpScreen.settings.showHeader = true;
        cpScreen.updateHeaderVisibility();
    }

    const slideout = container.closest('[data-slideout]');
    const header = slideout?.querySelector(':scope > .pane-header');

    if (header) {
        header.classList.remove('hidden');
        header.classList.add('so-visible');
    }
})();
JS,
            [$containerId],
        );
    }

    public function registerSlideoutCss(): void
    {
        $path = Craft::getAlias('@verbb/formie/web/assets/cp/src/form-preview/form-preview-slideout.css');

        if (!is_file($path)) {
            return;
        }

        Craft::$app->getView()->registerCss(file_get_contents($path));
    }

    // Private Methods
    // =========================================================================

    private function _getCacheKey(int $userId, string $token): string
    {
        return self::CACHE_KEY_PREFIX . ':' . $userId . ':' . $token;
    }
}
