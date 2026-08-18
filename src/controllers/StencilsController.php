<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form as FormElement;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

use Craft;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\ForbiddenHttpException;

use yii\web\HttpException;
use yii\web\Response;

class StencilsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $this->requirePermission('formie-accessStencils');

        return parent::beforeAction($action);
    }

    public function actionIndex(): Response
    {
        $stencils = Formie::$plugin->getStencils()->getAllStencils();
        $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        return $this->renderTemplate('formie/stencils/index', compact('stencils', 'allowAdminChanges'));
    }

    public function actionNew(Stencil $stencil = null): ?Response
    {
        $requestedScope = $this->request->getParam('scope');
        $scope = StencilsService::resolveScopeForNew($requestedScope);

        if ($requestedScope === StencilsService::SCOPE_PROJECT && $scope !== StencilsService::SCOPE_PROJECT) {
            throw new ForbiddenHttpException(Craft::t('formie', 'Project stencils cannot be created when admin changes are disabled.'));
        }

        $stencil = $stencil ?? new Stencil(['scope' => $scope]);

        Plugin::registerCpStencilNewAssets();

        $isProjectScope = $scope === StencilsService::SCOPE_PROJECT;

        $settings = [
            'formId' => 'fui-new-stencil-form',
            'name' => $stencil->name,
            'handle' => $stencil->handle,
            'scope' => $scope,
            'stencilOptions' => [],
            'showStencilSelector' => false,
            'formHandles' => $this->_getStencilHandles((int)$stencil->id),
            'reservedHandles' => Formie::$plugin->getFields()->getReservedHandles(),
            'maxFormHandleLength' => HandleHelper::getMaxFormHandle(),
            'nameErrors' => $stencil->getErrors('name'),
            'handleErrors' => $stencil->getErrors('handle'),
            'cancelUrl' => UrlHelper::cpUrl('formie/stencils'),
            'submitAction' => 'formie/stencils/save',
            'titleText' => $isProjectScope
                ? Craft::t('formie', 'Create a project stencil')
                : Craft::t('formie', 'Create a stencil'),
            'introText' => $isProjectScope
                ? Craft::t('formie', 'Project stencils are saved in project config and can be shared with your team.')
                : Craft::t('formie', 'Create a reusable starting point for new forms.'),
            'nameInstructions' => Craft::t('formie', 'What this stencil will be called in the control panel.'),
            'handleInstructions' => Craft::t('formie', 'How you’ll refer to this stencil in the templates.'),
            'saveErrorText' => Craft::t('formie', 'Couldn’t save stencil.'),
            'submitLabel' => Craft::t('formie', 'Next'),
        ];

        $this->view->registerJs('new Craft.Formie.NewForm(' . Json::encode($settings) . ');');

        return $this->renderTemplate('formie/stencils/_new');
    }

    public function actionEdit(mixed $segments = null, Stencil $stencil = null): Response
    {
        $stencilId = explode('/', $segments)[0] ?? null;

        if (!$stencilId) {
            throw new HttpException(404);
        }

        if (!$stencil) {
            $stencil = Formie::$plugin->getStencils()->getStencilById((int)$stencilId);
        }

        if (!$stencil) {
            throw new HttpException(404);
        }

        Plugin::registerCpStencilEditAssets();

        $variables = $this->_getStencilBuilderVariables($stencil);
        $this->view->registerJs('new Craft.Formie.FormBuilder(' . Json::encode($variables) . ');');

        return $this->renderTemplate('formie/stencils/_edit', [
            'stencil' => $stencil,
            'canEdit' => $stencil->canEdit(),
            'builderSiteCrumb' => Formie::$plugin->getFormSiteOverrides()->getBuilderSiteCrumbConfig(),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;
        $duplicate = (bool)$request->getParam('duplicateStencil');
        $duplicateToSite = (bool)$request->getParam('duplicateToSite');

        $stencilId = $request->getParam('stencilId');
        $stencil = $stencilId ? Formie::$plugin->getStencils()->getStencilById((int)$stencilId) : null;
        $stencil = $stencil ?? new Stencil();
        $stencil->id = $stencilId;
        $stencil->name = $request->getParam('title', $stencil->name);
        $stencil->handle = $request->getParam('handle', $stencil->handle);
        [$stencil->submitActionEntryId, $stencil->submitActionEntrySiteId] = SchemaHelper::firstElementSelectIds(
            $request->getParam('submitActionEntry'),
        );

        $originalName = $stencil->name;

        if (!$stencilId) {
            $stencil->scope = StencilsService::resolveScopeForNew($request->getParam('scope', $stencil->scope));
        }

        if ($duplicate || $duplicateToSite) {
            $stencil = clone $stencil;
            $stencil->id = null;
            $stencil->uid = null;
            $stencil->name = Craft::t('formie', '{name} Copy', ['name' => $originalName]);

            $stencilHandles = ArrayHelper::getColumn(Formie::$plugin->getStencils()->getAllStencils(), 'handle');
            $stencil->handle = HandleHelper::getUniqueHandle($stencilHandles, $stencil->handle);

            if ($duplicateToSite) {
                $stencil->scope = StencilsService::SCOPE_SITE;
            }
        }

        if ($templateId = $request->getParam('templateId')) {
            $template = Formie::$plugin->getFormTemplates()->getTemplateById($templateId);
            $stencil->setTemplate($template);
        }

        if ($statusId = $request->getParam('defaultStatusId')) {
            $status = Formie::$plugin->getSubmissionStatuses()->getStatusById($statusId);
            $stencil->setDefaultStatus($status);
        }

        $stencil->data = new StencilData();
        $stencil->data->userDeletedAction = $request->getParam('userDeletedAction', $stencil->data->userDeletedAction);
        $stencil->data->fileUploadsAction = $request->getParam('fileUploadsAction', $stencil->data->fileUploadsAction);
        $stencil->data->dataRetention = $request->getParam('dataRetention', $stencil->data->dataRetention);
        $stencil->data->dataRetentionValue = $request->getParam('dataRetentionValue', $stencil->data->dataRetentionValue);

        $form = Formie::$plugin->getForms()->buildStencilFormFromPost();
        $stencil->data->populateFormData($form);

        $form->handle .= mt_rand();
        $form->validate();

        $formErrors = $form->getErrors();

        if ($formErrors || !Formie::$plugin->getStencils()->saveStencil($stencil)) {
            $errors = $this->_normalizeStencilErrors($stencil, $formErrors);
            $stencil->name = $originalName;

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $errors,
                ]);
            }

            $this->setFailFlash(Craft::t('formie', 'Couldn’t save stencil.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'stencil' => $stencil,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $builderVariables = $this->_getStencilBuilderVariables($stencil);

            return $this->asJson([
                'success' => true,
                'data' => $builderVariables['data'],
                'redirect' => ($duplicate || $duplicateToSite || !$request->getParam('stencilId')) ? $stencil->getCpEditUrl() : null,
            ]);
        }

        $this->setSuccessFlash(Craft::t('formie', 'Stencil saved.'));

        return $this->redirectToPostedUrl($stencil);
    }

    public function actionDelete(): Response
    {
        $this->requireAcceptsJson();

        $stencilId = $this->request->getRequiredParam('id');

        if (Formie::$plugin->getStencils()->deleteStencilById((int)$stencilId)) {
            return $this->asJson([
                'success' => true,
                'redirect' => UrlHelper::cpUrl('formie/stencils'),
            ]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive stencil.')]);
    }


    // Private Methods
    // =========================================================================

    private function _getStencilBuilderVariables(Stencil $stencil): array
    {
        $canEdit = $stencil->canEdit();
        $now = DateTimeHelper::currentUTCDateTime();
        $form = new FormElement();
        $form->id = $stencil->id;
        $form->uid = $stencil->uid;
        $form->title = $stencil->name;
        $form->handle = $stencil->handle;
        $form->templateId = $stencil->templateId;
        $form->defaultStatusId = $stencil->defaultStatusId;
        $form->submitActionEntryId = $stencil->submitActionEntryId;
        $form->submitActionEntrySiteId = $stencil->submitActionEntrySiteId;
        $form->builderEntityType = FormElement::BUILDER_ENTITY_TYPE_STENCIL;
        $form->dateCreated = $now;
        $form->dateUpdated = $now;

        $stencil->applyStencilToForm($form);

        $variables = Formie::$plugin->getForms()->getFormBuilderVariables($form);
        $variables['baseUrl'] = $stencil->getCpEditUrl();
        $variables['viewSubmissionsUrl'] = null;
        $variables['entityType'] = 'stencil';
        $variables['entityId'] = $stencil->id;
        $variables['canEdit'] = $canEdit;
        $variables['stencilScope'] = $stencil->scope;
        $variables['stencilScopeLabel'] = $stencil->getScopeLabel();
        $variables['readOnlyMessage'] = !$canEdit
            ? Craft::t('formie', 'This stencil is managed by your development team and can’t be edited here. Save a copy to customize it.')
            : null;
        $variables['newItemTitle'] = Craft::t('formie', 'New Stencil');
        $variables['saveActionUrl'] = 'formie/stencils/save';
        $variables['saveRequestData'] = [
            'stencilId' => $stencil->id,
        ];
        $variables['saveDuplicateAction'] = 'saveAsDuplicate';
        $variables['saveDuplicateLabel'] = $canEdit
            ? Craft::t('formie', 'Save as a new stencil')
            : Craft::t('formie', 'Save a copy');
        $variables['saveDuplicateRequestData'] = $canEdit
            ? ['duplicateStencil' => true]
            : ['duplicateToSite' => true];
        $variables['saveSuccessMessage'] = Craft::t('formie', 'Stencil saved.');
        $variables['deleteAction'] = 'formie/stencils/delete';
        $variables['deleteRequestData'] = [
            'id' => $stencil->id,
        ];
        $variables['deleteRedirectUrl'] = UrlHelper::cpUrl('formie/stencils');
        $variables['deleteConfirmMessage'] = Craft::t('formie', 'Are you sure you want to delete this stencil?');
        $variables['deleteErrorMessage'] = Craft::t('formie', 'Couldn’t archive stencil.');
        $variables['data'] = [
            ...$variables['data'],
            'id' => $stencil->id,
            'uid' => $stencil->uid,
            'title' => $stencil->name,
            'handle' => $stencil->handle,
            'isStencil' => true,
            'stencilScope' => $stencil->scope,
            'templateId' => $stencil->templateId,
            'submitActionEntry' => array_filter([
                array_filter([
                    'id' => $stencil->submitActionEntryId,
                    'siteId' => $stencil->submitActionEntrySiteId,
                ]),
            ]),
            'defaultStatusId' => $stencil->defaultStatusId,
            'dataRetention' => $stencil->data->dataRetention,
            'dataRetentionValue' => $stencil->data->dataRetentionValue,
            'userDeletedAction' => $stencil->data->userDeletedAction,
            'fileUploadsAction' => $stencil->data->fileUploadsAction,
            'settings' => $stencil->getSettings(),
            'notifications' => $form->getNotifications(),
            'pages' => $form->getFormLayout()->getFormBuilderConfig(),
        ];

        return $variables;
    }

    private function _normalizeStencilErrors(Stencil $stencil, array $formErrors): array
    {
        $errors = $formErrors;

        if ($nameErrors = $stencil->getErrors('name')) {
            $errors['title'] = array_values(array_unique(array_merge($errors['title'] ?? [], $nameErrors)));
        }

        if ($handleErrors = $stencil->getErrors('handle')) {
            $errors['handle'] = array_values(array_unique(array_merge($errors['handle'] ?? [], $handleErrors)));
        }

        return $errors;
    }

    private function _getStencilHandles(int $stencilId): array
    {
        return (new Query())
            ->select(['handle'])
            ->from([Table::FORMIE_STENCILS])
            ->where(['not', ['id' => $stencilId]])
            ->column();
    }
}
