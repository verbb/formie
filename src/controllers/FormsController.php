<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;

use Craft;
use craft\db\Query;
use craft\enums\CmsEdition;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Session;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use Throwable;

class FormsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['refresh-tokens'];


    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $this->requirePermission('formie-accessForms');

        return $this->renderTemplate('formie/forms/index', []);
    }

    public function actionNew(Form $form = null): Response
    {
        $this->requirePermission('formie-createForms');

        $formHandles = ArrayHelper::getColumn(Form::find()->all(), 'handle');
        $stencilArray = Formie::$plugin->getStencils()->getStencilArray();
        $applyStencilId = $this->request->getParam('applyStencilId');

        $variables = compact('formHandles', 'form', 'stencilArray', 'applyStencilId');

        if (!$variables['form']) {
            $variables['form'] = new Form();
        }

        $variables['reservedHandles'] = Formie::$plugin->getFields()->getReservedHandles();
        $variables['maxFormHandleLength'] = HandleHelper::getMaxFormHandle();

        Plugin::registerAsset('src/js/formie-form-new.js');

        // Craft Team requires specific permissions
        if (Craft::$app->edition === CmsEdition::Team && !Craft::$app->getUser()->checkPermission('formie-manageForms')) {
            return $this->renderTemplate('formie/forms/_team');
        }

        return $this->renderTemplate('formie/forms/_new', $variables);
    }

    public function actionEdit(int $formId = null, Form $form = null): Response
    {
        $variables = compact('formId', 'form');

        $this->_prepareVariableArray($variables);

        if (!empty($variables['form']->id)) {
            $variables['title'] = $variables['form']->title;

            // User must have at least one of these permissions to edit (all, or the specific form)
            $formsPermission = Craft::$app->getUser()->checkPermission('formie-manageForms');
            $formPermission = Craft::$app->getUser()->checkPermission('formie-manageForms:' . $variables['form']->uid);

            if (!$formsPermission && !$formPermission) {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new form');
        }

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'formie/forms/edit/{id}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'];

        Plugin::registerAsset('src/js/formie-form.js');

        return $this->renderTemplate('formie/forms/_edit', $variables);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $settings = Formie::$plugin->getSettings();

        $form = Formie::$plugin->getForms()->buildFormFromPost();

        $duplicate = (bool)$this->request->getParam('duplicate');

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

        if ($duplicate) {
            $duplicatedForm = Craft::$app->getElements()->duplicateElement($form, $form->getDuplicateAttributes());

            if (!$duplicatedForm) {
                Formie::error('Couldn’t duplicate form - {e}.', ['e' => Json::encode($form->getConsolidatedErrors())]);

                // Important not to return back the duplicated form (which failed to be created). Use the original form.
                if ($this->request->getAcceptsJson()) {
                    return $this->asJson([
                        'success' => false,
                        'config' => $form->getFormBuilderConfig(),
                        'notifications' => $form->getNotificationsConfig(),
                    ]);
                }

                $this->setFailFlash(Craft::t('formie', 'Couldn’t duplicate form.'));

                Craft::$app->getUrlManager()->setRouteParams([
                    'form' => $form,
                ]);

                return null;
            }
        } else {
            if (!Craft::$app->getElements()->saveElement($form)) {
                Formie::error('Couldn’t save form - {e}.', ['e' => Json::encode($form->getConsolidatedErrors())]);

                if ($this->request->getAcceptsJson()) {
                    return $this->asJson([
                        'success' => false,
                        'config' => $form->getFormBuilderConfig(),
                        'notifications' => $form->getNotificationsConfig(),
                    ]);
                }

                $this->setFailFlash(Craft::t('formie', 'Couldn’t save form.'));

                Craft::$app->getUrlManager()->setRouteParams([
                    'form' => $form,
                ]);

                return null;
            }
        }

        // For some things, we'll want to use a potentially duplicated form (if we've duplicated)
        $savedForm = ($duplicate) ? $duplicatedForm : $form;

        // Check if we need to update the permissions for this user.
        $this->_updateFormPermission($savedForm);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'config' => $form->getFormBuilderConfig(),
                'notifications' => $form->getNotificationsConfig(),
                'redirect' => ($duplicate) ? $duplicatedForm->getCpEditUrl() : null,
            ]);
        }

        $this->setSuccessFlash(Craft::t('formie', 'Form saved.'));

        return $this->redirectToPostedUrl($savedForm);
    }

    public function actionSaveAsStencil(): ?Response
    {
        $this->requirePostRequest();

        $stencils = Formie::$plugin->getStencils()->getAllStencils();
        $stencilHandles = ArrayHelper::getColumn($stencils, 'handle');
        $handle = $this->request->getParam('handle');

        $stencil = new Stencil();
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

            $this->setFailFlash(Craft::t('formie', 'Couldn’t save stencil.'));

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

            $this->setFailFlash(Craft::t('app', 'Couldn’t delete form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Form deleted.'));

        if ($this->request->getAcceptsJson()) {
            $url = $this->request->getValidatedBodyParam('redirect');
            $url = Formie::$plugin->getTemplates()->renderObjectTemplate($url, $form);

            return $this->asJson([
                'success' => false,
                'redirect' => UrlHelper::url($url),
            ]);
        }

        return $this->redirectToPostedUrl($form);
    }

    public function actionRefreshTokens(): Response
    {
        // Ensure that the session has started, just in case
        Craft::$app->getSession()->open();

        $params = [
            'csrf' => [
                'param' => $this->request->csrfParam,
                'token' => $this->request->getCsrfToken(),
                'input' => '<input type="hidden" name="' . $this->request->csrfParam . '" value="' . $this->request->getCsrfToken() . '">',
            ],
        ];

        // Add captchas into the payload
        $formHandle = $this->request->getRequiredParam('form');
        $form = Formie::$plugin->getForms()->getFormByHandle($formHandle);

        // Force fetch captchas because we're dealing with potential ajax forms
        // Normally, this function returns only if the `showAllPages` property is set.
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form, null, true);

        foreach ($captchas as $captcha) {
            if ($jsVariables = $captcha->getRefreshJsVariables($form)) {
                $params['captchas'][$captcha->handle] = $jsVariables;
            }
        }

        // Prevent the browser from caching the response
        $this->response->setNoCacheHeaders();

        return $this->asJson($params);
    }

    public function actionGetExistingFields(): Response
    {
        $formId = $this->request->getRequiredParam('formId');

        $form = Formie::$plugin->getForms()->getFormById($formId);
        $existingFields = Formie::$plugin->getFields()->getExistingFields($form);

        return $this->asJson($existingFields);
    }

    public function actionGetExistingNotifications(): Response
    {
        $formId = $this->request->getRequiredParam('formId');

        $form = Formie::$plugin->getForms()->getFormById($formId);
        $existingNotifications = Formie::$plugin->getNotifications()->getExistingNotifications($form);

        return $this->asJson($existingNotifications);
    }


    // Private Methods
    // =========================================================================

    private function _prepareVariableArray(array &$variables): void
    {
        if (!$variables['form']) {
            $variables['form'] = Formie::$plugin->getForms()->getFormById($variables['formId']);

            if (!$variables['form']) {
                throw new Exception('Missing form data.');
            }
        }

        $settings = Formie::$plugin->getSettings();

        /** @var Form $form */
        $form = $variables['form'];

        $variables['notificationsSchema'] = Formie::$plugin->getNotifications()->getNotificationsSchema();
        $variables['groupedIntegrations'] = Formie::$plugin->getIntegrations()->getAllIntegrationsForForm();
        $variables['formUsage'] = Formie::$plugin->getForms()->getFormUsage($form);

        $variables['jsBuilderConfig'] = [
            'config' => $form->getFormBuilderConfig(),
            'fields' => Formie::$plugin->getFields()->getFormBuilderFieldTypes(),
            'notifications' => $form->getNotificationsConfig(),
            'variables' => Variables::getVariablesArray(),
            'emailTemplates' => Formie::$plugin->getEmailTemplates()->getAllTemplates(),
            'reservedHandles' => Formie::$plugin->getFields()->getReservedHandles(),
            'formHandles' => $this->_getFormHandles($form->id),
            'statuses' => Formie::$plugin->getStatuses()->getAllStatuses(),
            'maxFormHandleLength' => HandleHelper::getMaxFormHandle(),
            'maxFieldHandleLength' => HandleHelper::getMaxFieldHandle(),
            'filterIntegrationMapping' => $settings->filterIntegrationMapping,
        ];

        $variables['tabs'] = Formie::$plugin->getForms()->getFormBuilderTabs($form, $variables);

        // When only one tab is available (user permissions) Craft will change `tabs` to `null` for some reason.
        // So we need to use both `tabs` and `formTabs`
        $variables['formTabs'] = $variables['tabs'];
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
}
