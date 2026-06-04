<?php
namespace verbb\formie\controllers;

use verbb\formie\compatibility\client\RefreshTokensCompatibility;
use verbb\formie\Formie;
use verbb\formie\controllers\CrossOriginRequestTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\models\FormTemplate;

use Craft;
use craft\db\Query;
use craft\enums\CmsEdition;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Session;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use craft\web\CpScreenResponseBehavior;
use craft\web\Response as CraftResponse;

use yii\base\Exception;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use Throwable;

class FormsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = [
        'render' => self::ALLOW_ANONYMOUS_LIVE,
        'refresh-tokens' => self::ALLOW_ANONYMOUS_LIVE,
    ];


    // Traits
    // =========================================================================

    use CrossOriginRequestTrait;


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (in_array($action->id, ['render', 'refresh-tokens'], true)) {
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

        $form = $this->_getClientRequestForm();

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

        $form = $this->_getFrontendRequestForm();

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $renderOptions = (array)$this->request->getParam('renderOptions', []);
        $renderOptions['includeCss'] = false;
        $renderOptions['includeJs'] = false;
        $renderOptions['includeScriptsInline'] = true;
        $renderOptions['mode'] = 'html';
        $renderOptions['endpoint'] = $renderOptions['endpoint'] ?? UrlHelper::actionUrl('formie/forms/render');

        $this->response->setNoCacheHeaders();

        return $this->asJson(Formie::$plugin->getServerRenderPayloadBuilder()->buildServerRenderPayload($form, $renderOptions));
    }

    public function actionIndex(): Response
    {
        $this->requirePermission('formie-accessForms');

        $canCreateForms = Craft::$app->getUser()->checkPermission('formie-createForms');
        $editableFormGroups = [];

        if ($canCreateForms) {
            Plugin::registerCpFormsIndexAssets();

            foreach (Formie::$plugin->getFormGroups()->getAllGroups() as $group) {
                $editableFormGroups[] = [
                    'handle' => $group->handle,
                    'id' => (int)$group->id,
                    'name' => $group->name,
                    'uid' => $group->uid,
                ];
            }
        }

        return $this->renderTemplate('formie/forms/index', [
            'canCreateForms' => $canCreateForms,
            'editableFormGroups' => $editableFormGroups,
        ]);
    }

    public function actionNew(Form $form = null): Response
    {
        $this->requirePermission('formie-createForms');

        $form = $form ?? new Form();

        // Craft Team requires specific permissions
        if (Craft::$app->edition === CmsEdition::Team && !Craft::$app->getUser()->checkPermission('formie-manageForms')) {
            return $this->renderTemplate('formie/forms/_team');
        }

        Plugin::registerCpNewFormAssets();

        $stencilOptions = array_merge([
            [
                'value' => '',
                'label' => Craft::t('formie', 'Blank Form'),
            ],
        ], array_map(static function(array $option): array {
            $option['value'] = (string)($option['value'] ?? '');

            return $option;
        }, Formie::$plugin->getStencils()->getStencilArray()));

        $groupId = $this->_resolveGroupIdFromRequest();

        if ($groupId) {
            $form->groupId = $groupId;
        }

        $settings = [
            'formId' => 'fui-new-form-form',
            'name' => $form->title,
            'handle' => $form->handle,
            'groupId' => $groupId,
            'applyStencilId' => (string)$this->request->getParam('applyStencilId', ''),
            'stencilOptions' => $stencilOptions,
            'formHandles' => ArrayHelper::getColumn(Form::find()->all(), 'handle'),
            'reservedHandles' => Formie::$plugin->getFields()->getReservedHandles(),
            'maxFormHandleLength' => HandleHelper::getMaxFormHandle(),
            'nameErrors' => $form->getErrors('title'),
            'handleErrors' => $form->getErrors('handle'),
            'cancelUrl' => UrlHelper::cpUrl('formie/forms'),
        ];

        $this->view->registerJs('new Craft.Formie.NewForm(' . Json::encode($settings) . ');');

        return $this->renderTemplate('formie/forms/_new');
    }

    public function actionEdit(mixed $segments = null, Form $form = null): Response
    {
        $formId = explode('/', $segments)[0] ?? null;

        if (!$formId) {
            throw new NotFoundHttpException('Form not found');
        }

        $form = Formie::$plugin->getForms()->getFormById($formId);

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        Plugin::registerCpFormBuilderAssets();

        $variables = Formie::$plugin->getForms()->getFormBuilderVariables($form);

        $encodedVariables = Json::encode($variables);

        $this->view->registerJs('new Craft.Formie.FormBuilder(' . $encodedVariables . ');');

        return $this->renderTemplate('formie/forms/_edit', [
            'form' => $form,
        ]);
    }

    public function actionTemplateFieldsSlideout(): Response
    {
        $this->requireCpRequest();
        
        $formId = $this->request->getParam('formId');
        $templateId = $this->request->getParam('templateId');

        $form = $formId ? Formie::$plugin->getForms()->getFormById((int)$formId) : null;
        $template = $templateId ? Formie::$plugin->getFormTemplates()->getTemplateById((int)$templateId) : null;

        if (!$template && $form?->templateId) {
            $template = Formie::$plugin->getFormTemplates()->getTemplateById((int)$form->templateId);
        }

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $form->setTemplate($template);

        return $this->asCpScreen()
            ->docTitle(Craft::t('formie', 'Template Fields'))
            ->title(Craft::t('formie', 'Template Fields'))
            ->action('formie/forms/template-fields-slideout-save')
            ->prepareScreen(function(CraftResponse $response) use ($form, $template) {
                $fieldLayout = $form->getFieldLayout();
                $formContent = $fieldLayout->createForm($form);

                $components = [];
                $components[] = Html::hiddenInput('formId', (string)$form->id);
                $components[] = Html::hiddenInput('templateId', (string)$template->id);
                $components[] = Html::hiddenInput('siteId', (string)$form->siteId);
                $components[] = $formContent->render();
                $contentHtml = implode("\n", $components);

                $response
                    ->tabs($formContent->getTabMenu())
                    ->contentHtml($contentHtml);
            });
    }

    public function actionPreparePreview(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('formie-accessForms');

        $bodyParams = $this->request->getBodyParams();

        if (!$bodyParams) {
            return $this->asJson([
                'error' => Craft::t('formie', 'Missing form preview data.'),
            ]);
        }

        try {
            $token = Formie::$plugin->getFormPreview()->createSession($bodyParams);

            return $this->asJson([
                'token' => $token,
            ]);
        } catch (Throwable $e) {
            Formie::error('Could not prepare form preview: {message}', [
                'message' => $e->getMessage(),
            ]);

            return $this->asJson([
                'error' => Craft::t('formie', 'Could not prepare form preview.'),
            ]);
        }
    }

    public function actionPreviewSlideout(): Response
    {
        $this->requireCpRequest();
        $this->requirePermission('formie-accessForms');

        $previewKey = (string)$this->request->getParam('previewKey');

        if ($previewKey === '') {
            throw new NotFoundHttpException('Form preview not found.');
        }

        Formie::$plugin->getFormPreview()->registerSlideoutCss();

        $response = $this->asCpScreen()
            ->docTitle(Craft::t('formie', 'Form Preview'))
            ->title(Craft::t('formie', 'Form Preview'))
            ->toolbarHtml(Formie::$plugin->getFormPreview()->getSlideoutToolbarHtml())
            ->contentHtml(Formie::$plugin->getFormPreview()->getSlideoutContentHtml($previewKey))
            ->prepareScreen(function (CraftResponse $response, string $containerId): void {
                Formie::$plugin->getFormPreview()->registerSlideoutPaneHeader($containerId);
            });

        $screen = $response->getBehavior(CpScreenResponseBehavior::NAME);

        if ($screen instanceof CpScreenResponseBehavior) {
            $screen->slideoutBodyClass = 'so-full-details formie-form-preview-slideout';
        }

        return $response;
    }

    public function actionPreviewFrame(): Response
    {
        $this->requireCpRequest();
        $this->requirePermission('formie-accessForms');

        $previewKey = (string)$this->request->getParam('previewKey');

        if ($previewKey === '') {
            throw new NotFoundHttpException('Form preview not found.');
        }

        try {
            $preview = Formie::$plugin->getFormPreview()->renderPreviewFrame($previewKey);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Formie::error('Could not render form preview: {message}', [
                'message' => $e->getMessage(),
            ]);

            throw new NotFoundHttpException('Form preview could not be rendered.');
        }

        $html = Craft::$app->getView()->renderTemplate('formie/forms/_preview-frame', [
            'formHtml' => $preview['html'],
        ]);

        return $this->asRaw($html);
    }

    public function actionPreviewSubmit(): ?Response
    {
        $this->requirePostRequest();

        $token = (string)$this->request->getParam('previewToken');

        if ($token === '') {
            throw new NotFoundHttpException('Form preview not found.');
        }

        $this->requirePermission('formie-accessForms');

        Formie::$plugin->getFormPreview()->setPreviewSubmittedFlash($token);

        return $this->redirect(Formie::$plugin->getFormPreview()->getPreviewFrameUrl($token));
    }

    public function actionTemplateFieldsSlideoutSave(): Response
    {
        $this->requirePostRequest();
        
        $formId = $this->request->getParam('formId');
        $templateId = $this->request->getParam('templateId');

        $form = $formId ? Formie::$plugin->getForms()->getFormById((int)$formId) : null;
        $template = $templateId ? Formie::$plugin->getFormTemplates()->getTemplateById((int)$templateId) : null;

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        if (!$template) {
            throw new NotFoundHttpException('Template not found');
        }

        $form->setTemplate($template);
        $form->setFieldValuesFromRequest('fields');
        
        if (!Craft::$app->getElements()->saveElement($form)) {
            Formie::error('Couldn\'t save form - {e}.', ['e' => Json::encode($form->getErrors())]);

            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $form->getErrors(),
                ]);
            }

            $this->setFailFlash(Craft::t('formie', 'Couldn\'t save form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
            ]);

            return null;
        }

        return $this->asSuccess(Craft::t('formie', 'Template fields saved.'));
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $form = Formie::$plugin->getForms()->buildFormFromPost();
        $isNewForm = !$form->id;

        $saveAsNew = (bool)$this->request->getParam('saveAsNew');

        // If the user has create permissions, but not edit permissions, we can run into issues...
        if (!$form->uid) {
            $this->requirePermission('formie-createForms');
        } else {
            // User must have at least one of these permissions to edit (all, or the specific form)
            $formsPermission = Craft::$app->getUser()->checkPermission('formie-manageForms');
            $formPermission = Craft::$app->getUser()->checkPermission('formie-manageForms:' . $form->uid);

            if (!$formsPermission && !$formPermission) {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }
        }

        if ($saveAsNew) {
            $this->requirePermission('formie-createForms');

            $duplicatedForm = Craft::$app->getElements()->duplicateElement($form, $form->getDuplicateAttributes());


            if (!$duplicatedForm) {
                Formie::error('Couldn\'t save form as new - {e}.', ['e' => Json::encode($form->getErrors())]);

                if ($this->request->getAcceptsJson()) {
                    return $this->asJson([
                        'errors' => $form->getErrors(),
                    ]);
                }

                $this->setFailFlash(Craft::t('formie', 'Couldn\'t save form as new.'));

                Craft::$app->getUrlManager()->setRouteParams([
                    'form' => $form,
                ]);

                return null;
            }

            $this->_updateFormPermission($duplicatedForm);

            $variables = Formie::$plugin->getForms()->getFormBuilderVariables($duplicatedForm);
            $variables['redirect'] = $duplicatedForm->getCpEditUrl();

            if ($this->request->getAcceptsJson()) {
                return $this->asJson($variables);
            }

            $this->setSuccessFlash(Craft::t('formie', 'Form saved.'));

            return $this->redirectToPostedUrl($duplicatedForm);
        }

        if (!Craft::$app->getElements()->saveElement($form)) {
            Formie::error('Couldn\'t save form - {e}.', ['e' => Json::encode($form->getErrors())]);

            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $form->getErrors(),
                ]);
            }

            $this->setFailFlash(Craft::t('formie', 'Couldn\'t save form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
            ]);

            return null;
        }

        // If this was a new form, redirect to the edit page
        if ($isNewForm) {
            $this->setSuccessFlash(Craft::t('formie', 'Form created.'));

            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => true,
                    'redirect' => $form->getCpEditUrl(),
                ]);
            }

            return $this->redirectToPostedUrl($form);
        }

        $savedForm = $form;

        // Check if we need to update the permissions for this user.
        $this->_updateFormPermission($savedForm);

        $variables = Formie::$plugin->getForms()->getFormBuilderVariables($savedForm);
        $variables['redirect'] = null;

        if ($this->request->getAcceptsJson()) {
            return $this->asJson($variables);
        }

        $this->setSuccessFlash(Craft::t('formie', 'Form saved.'));

        return $this->redirectToPostedUrl($savedForm);
    }

    public function actionSaveAsStencil(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('formie-accessStencils');

        $stencils = Formie::$plugin->getStencils()->getAllStencils();
        $stencilHandles = ArrayHelper::getColumn($stencils, 'handle');
        $handle = $this->request->getParam('handle');

        $stencil = new Stencil([
            'scope' => \verbb\formie\services\Stencils::SCOPE_SITE,
        ]);
        $stencil->name = $this->request->getParam('title');

        // Resolve the handle, in case it already exists
        $stencil->handle = HandleHelper::getUniqueHandle($stencilHandles, $handle);

        if ($templateId = $this->request->getParam('templateId')) {
            $template = Formie::$plugin->getFormTemplates()->getTemplateById($templateId);
            $stencil->setTemplate($template);
        }

        if ($statusId = $this->request->getParam('defaultStatusId')) {
            $status = Formie::$plugin->getStatuses()->getStatusById($statusId);
            $stencil->setDefaultStatus($status);
        }

        // Set form data.
        $stencil->data = new StencilData();
        $stencil->data->userDeletedAction = $this->request->getParam('userDeletedAction', $stencil->data->userDeletedAction);
        $stencil->data->fileUploadsAction = $this->request->getParam('fileUploadsAction', $stencil->data->fileUploadsAction);
        $stencil->data->dataRetention = $this->request->getParam('dataRetention', $stencil->data->dataRetention);
        $stencil->data->dataRetentionValue = $this->request->getParam('dataRetentionValue', $stencil->data->dataRetentionValue);

        // Build temp form for validation.
        $form = Formie::$plugin->getForms()->buildFormFromPost();

        // Populate the stencil data with data prepped for the form
        $stencil->data->populateFormData($form);

        // Don't validate the handle.
        $form->handle .= random_int(0, mt_getrandmax());

        $form->validate();

        $formErrors = $form->getErrors();

        if ($formErrors || !Formie::$plugin->getStencils()->saveStencil($stencil)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'config' => $stencil->getFormBuilderConfig(),
                    'notifications' => $stencil->getNotificationsConfig(),
                ]);
            }

            $this->setFailFlash(Craft::t('formie', 'Couldn\'t save stencil.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $stencil,
                'stencil' => $stencil,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'config' => $stencil->getFormBuilderConfig(),
                'notifications' => $stencil->getNotificationsConfig(),
                'redirect' => $stencil->getCpEditUrl(),
                'redirectMessage' => Craft::t('formie', 'Stencil saved.'),
            ]);
        }

        $this->setSuccessFlash(Craft::t('formie', 'Stencil saved.'));

        return $this->redirectToPostedUrl($stencil);
    }

    public function actionDeleteForm(): ?Response
    {
        $this->requirePostRequest();

        $this->requirePermission('formie-deleteForms');

        $formId = $this->request->getRequiredBodyParam('formId');

        $form = Formie::$plugin->getForms()->getFormById($formId);

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        if (!Craft::$app->getElements()->deleteElement($form)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn\'t delete form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Form deleted.'));

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'redirect' => UrlHelper::cpUrl('formie/forms'),
            ]);
        }

        return $this->redirectToPostedUrl($form);
    }

    public function actionGetExistingFields(): Response
    {
        $formId = $this->request->getRequiredParam('formId');
        $compact = (bool)$this->request->getParam('compact', false);
        $includeFields = (bool)$this->request->getParam('includeFields', true);
        $formKey = $this->request->getParam('formKey');
        $search = trim((string)$this->request->getParam('search', ''));

        $form = Formie::$plugin->getForms()->getFormById($formId);

        if ($compact) {
            if (!$includeFields) {
                return $this->asJson(Formie::$plugin->getFields()->getExistingFieldFormOptions($form));
            }

            return $this->asJson(Formie::$plugin->getFields()->getExistingFieldSummaries($form, $formKey, $search));
        }

        $existingFields = Formie::$plugin->getFields()->getExistingFields($form);

        return $this->asJson($existingFields);
    }

    public function actionGetExistingFieldConfigs(): Response
    {
        $formId = $this->request->getRequiredParam('formId');
        $fieldIds = $this->request->getParam('fieldIds', []);

        $form = Formie::$plugin->getForms()->getFormById($formId);
        $existingFieldConfigs = Formie::$plugin->getFields()->getExistingFieldConfigs($fieldIds, $form);

        return $this->asJson($existingFieldConfigs);
    }

    public function actionGetExistingNotifications(): Response
    {
        $formId = $this->request->getRequiredParam('formId');
        $compact = (bool)$this->request->getParam('compact', false);
        $includeNotifications = (bool)$this->request->getParam('includeNotifications', true);
        $formKey = $this->request->getParam('formKey');
        $search = trim((string)$this->request->getParam('search', ''));

        $form = Formie::$plugin->getForms()->getFormById($formId) ?? Formie::$plugin->getStencils()->getStencilById((int)$formId);

        if ($compact) {
            if (!$includeNotifications) {
                return $this->asJson(Formie::$plugin->getNotifications()->getExistingNotificationFormOptions($form));
            }

            return $this->asJson(Formie::$plugin->getNotifications()->getExistingNotificationSummaries($form, $formKey, $search));
        }

        $existingNotifications = Formie::$plugin->getNotifications()->getExistingNotifications($form);

        return $this->asJson($existingNotifications);
    }

    public function actionGetFormUsage(): Response
    {
        $formId = $this->request->getRequiredParam('formId');

        $form = Formie::$plugin->getForms()->getFormById($formId);
        if (!$form && Formie::$plugin->getStencils()->getStencilById((int)$formId)) {
            return $this->asJson([]);
        }

        $formUsage =  Formie::$plugin->getForms()->getFormUsage($form);

        return $this->asJson($formUsage);
    }


    // Private Methods
    // =========================================================================

    private function _getClientRequestForm(): ?Form
    {
        $formHandle = RefreshTokensCompatibility::resolveRequestedHandle($this->request);

        if ($formHandle === '') {
            return null;
        }

        $query = Form::find()->handle($formHandle);
        $siteId = $this->request->getParam('siteId');

        if ($siteId) {
            $query->siteId((int)$siteId);
        }

        return $query->one();
    }

    private function _updateFormPermission(Form $form): void
    {
        if (Craft::$app->edition === CmsEdition::Solo) {
            return;
        }

        $suffix = ':' . $form->uid;

        $userService = Craft::$app->getUser();
        $currentUser = $userService->getIdentity();
        $permissions = Craft::$app->getUserPermissions()->getPermissionsByUserId($currentUser->id);
        $permissions[] = "formie-manageForms{$suffix}";

        // Add all nested permissions according to top-level permissions set
        if ($userService->checkPermission('formie-showFormAppearance') || $userService->checkPermission('formie-createFormAppearance')) {
            $permissions[] = "formie-showFormAppearance{$suffix}";
        }

        if ($userService->checkPermission('formie-showFormBehavior') || $userService->checkPermission('formie-createFormBehavior')) {
            $permissions[] = "formie-showFormBehavior{$suffix}";
        }

        if ($userService->checkPermission('formie-showNotifications') || $userService->checkPermission('formie-createNotifications')) {
            $permissions[] = "formie-showNotifications{$suffix}";
        }

        if ($userService->checkPermission('formie-showNotificationsAdvanced') || $userService->checkPermission('formie-createNotifications')) {
            $permissions[] = "formie-showNotificationsAdvanced{$suffix}";
        }

        if ($userService->checkPermission('formie-showNotificationsTemplates') || $userService->checkPermission('formie-createNotifications')) {
            $permissions[] = "formie-showNotificationsTemplates{$suffix}";
        }

        if ($userService->checkPermission('formie-showFormIntegrations') || $userService->checkPermission('formie-createFormIntegrations')) {
            $permissions[] = "formie-showFormIntegrations{$suffix}";
        }

        if ($userService->checkPermission('formie-showFormSettings') || $userService->checkPermission('formie-createFormSettings')) {
            $permissions[] = "formie-showFormSettings{$suffix}";
        }

        // Check if they have "View Submissions" - they should have access to manage
        if ($userService->checkPermission('formie-viewSubmissions')) {
            $permissions[] = "formie-viewSubmissions{$suffix}";
            $permissions[] = "formie-createSubmissions{$suffix}";
            $permissions[] = "formie-saveSubmissions{$suffix}";
            $permissions[] = "formie-deleteSubmissions{$suffix}";
        }

        if (Craft::$app->edition === CmsEdition::Pro) {
            Craft::$app->getUserPermissions()->saveUserPermissions($currentUser->id, $permissions);
        }
    }

    private function _getFormHandles(int $formId): array
    {
        return (new Query())
            ->select(['handle'])
            ->from([Table::FORMIE_FORMS])
            ->where(['not', ['id' => $formId]])
            ->column();
    }

    private function _resolveGroupIdFromRequest(): ?int
    {
        $groupId = StringHelper::toId($this->request->getParam('groupId'));

        if ($groupId) {
            return $groupId;
        }

        $source = $this->request->getParam('source');

        if (is_string($source) && str_starts_with($source, 'group:')) {
            $group = Formie::$plugin->getFormGroups()->getGroupByUid(substr($source, 6));

            return $group?->id;
        }

        return null;
    }

}
