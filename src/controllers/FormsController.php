<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;

use Craft;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
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

    /**
     * Shows all the forms in a list.
     *
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionIndex(): Response
    {
        $this->requirePermission('formie-viewForms');

        return $this->renderTemplate('formie/forms/index', []);
    }

    /**
     * Creates a new form with a pretty interface.
     *
     * @param Form|null $form
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionNew(Form $form = null): Response
    {
        $this->requirePermission('formie-createForms');

        $formHandles = ArrayHelper::getColumn(Form::find()->all(), 'handle');
        $stencilArray = Formie::$plugin->getStencils()->getStencilArray();

        $variables = compact('formHandles', 'form', 'stencilArray');

        if (!$variables['form']) {
            $variables['form'] = new Form();
        }

        $variables['reservedHandles'] = Formie::$plugin->getFields()->getReservedHandles();
        $variables['maxFormHandleLength'] = HandleHelper::getMaxFormHandle();

        Plugin::registerAsset('src/js/formie-form-new.js');

        return $this->renderTemplate('formie/forms/_new', $variables);
    }

    /**
     * Renders the main form builder interface.
     *
     * @param int|null $formId
     * @param string|null $siteHandle
     * @param Form|null $form
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionEdit(int $formId = null, string $siteHandle = null, Form $form = null): Response
    {
        $variables = compact('formId', 'form');

        if ($siteHandle !== null) {
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        $this->requirePermission('formie-viewForms');

        $this->_prepareVariableArray($variables);

        if (!empty($variables['form']->id)) {
            $variables['title'] = $variables['form']->title;

            // User must have at least one of these permissions to edit (all, or the specific form)
            $formsPermission = Craft::$app->getUser()->checkPermission('formie-editForms');
            $formPermission = Craft::$app->getUser()->checkPermission('formie-manageForm:' . $variables['form']->uid);

            if (!$formsPermission && !$formPermission) {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new form');
        }

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'formie/forms/edit/{id}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] .
            (Craft::$app->getIsMultiSite() && Craft::$app->getSites()->currentSite->id !== $variables['site']->id ? '/' . $variables['site']->handle : '');

        Plugin::registerAsset('src/js/formie-form.js');

        return $this->renderTemplate('formie/forms/_edit', $variables);
    }

    /**
     * Saves a form.
     *
     * @throws Throwable
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $settings = Formie::$plugin->getSettings();

        $form = Formie::$plugin->getForms()->buildFormFromPost();
        $duplicate = (bool)$request->getParam('duplicate');

        // If the user has create permissions, but not edit permissions, we can run into issues...
        if (!$form->uid) {
            $this->requirePermission('formie-createForms');
        } else {
            // User must have at least one of these permissions to edit (all, or the specific form)
            $formsPermission = Craft::$app->getUser()->checkPermission('formie-editForms');
            $formPermission = Craft::$app->getUser()->checkPermission('formie-manageForm:' . $form->uid);

            if (!$formsPermission && !$formPermission) {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }
        }

        // Set the default template from settings, if not already set - for new forms
        if (!$form->id && !$form->templateId) {
            $form->templateId = $settings->getDefaultFormTemplateId();
        }

        if (!Formie::$plugin->getForms()->saveForm($form)) {
            if ($request->getAcceptsJson()) {
                $notifications = $form->getNotifications();
                $notificationsConfig = Formie::$plugin->getNotifications()->getNotificationsConfig($notifications);

                $json = [
                    'success' => false,
                    'id' => $form->id,
                    'notifications' => $notificationsConfig,
                    'errors' => $form->getErrors(),
                    'fieldLayoutId' => $form->fieldLayoutId,
                ];

                // Don't return the config if there's an error on duplicate - settings will have changed
                if (!$duplicate) {
                    $json['config'] = $form->getFormConfig();
                }

                return $this->asJson($json);
            }

            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
                'errors' => $form->getErrors(),
            ]);

            return null;
        }

        $notifications = $form->getNotifications();
        $notificationsConfig = Formie::$plugin->getNotifications()->getNotificationsConfig($notifications);

        // Check if we need to update the permissions for this user.
        $this->_updateFormPermission($form);

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $form->id,
                'config' => $form->getFormConfig(),
                'notifications' => $notificationsConfig,
                'errors' => $form->getErrors(),
                'fieldLayoutId' => $form->fieldLayoutId,
                'redirect' => ($duplicate) ? $form->cpEditUrl : null,
                'redirectMessage' => Craft::t('formie', 'Form saved.'),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Form saved.'));

        return $this->redirectToPostedUrl($form);
    }

    /**
     * Creates a new Stencil from a form.
     *
     * @throws Throwable
     */
    public function actionSaveAsStencil(): ?Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $stencils = Formie::$plugin->getStencils()->getAllStencils();
        $stencilHandles = ArrayHelper::getColumn($stencils, 'handle');
        $handle = $request->getParam('handle');

        $stencil = new Stencil();
        $stencil->name = $request->getParam('title');

        // Resolve the handle, in case it already exists
        $stencil->handle = HandleHelper::getUniqueHandle($stencilHandles, $handle);

        if ($templateId = $request->getParam('templateId')) {
            $template = Formie::$plugin->getFormTemplates()->getTemplateById($templateId);
            $stencil->setTemplate($template);
        }

        if ($statusId = $request->getParam('defaultStatusId')) {
            $status = Formie::$plugin->getStatuses()->getStatusById($statusId);
            $stencil->setDefaultStatus($status);
        }

        if ($settings = $request->getParam('settings')) {
            $pages = Json::decode($request->getParam('pages'));
            $notifications = Json::decode($request->getParam('notifications'));

            // Set form data.
            $stencil->data = new StencilData(compact('settings', 'pages', 'notifications'));
            $stencil->data->userDeletedAction = $request->getParam('userDeletedAction', $stencil->data->userDeletedAction);
            $stencil->data->fileUploadsAction = $request->getParam('fileUploadsAction', $stencil->data->fileUploadsAction);
            $stencil->data->dataRetention = $request->getParam('dataRetention', $stencil->data->dataRetention);
            $stencil->data->dataRetentionValue = $request->getParam('dataRetentionValue', $stencil->data->dataRetentionValue);

            // Build temp form for validation.
            $form = Formie::$plugin->getForms()->buildFormFromPost();

            // Don't validate the handle.
            $form->handle .= random_int(0, mt_getrandmax());

            $form->validate();

            $formHasErrors = $form->hasErrors();
            $formErrors = $form->getErrors();
        } else {
            $formHasErrors = false;
            $formErrors = [];
        }

        // Save it
        if ($formHasErrors || !Formie::$plugin->getStencils()->saveStencil($stencil)) {
            $config = $stencil->getFormConfig();
            $notifications = ArrayHelper::remove($config, 'notifications', []);

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'id' => $stencil->id,
                    'config' => $config,
                    'notifications' => $notifications,
                    'errors' => ArrayHelper::merge($formErrors, $stencil->getErrors()),
                    'success' => !$formHasErrors && !$stencil->hasErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save stencil.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $stencil,
                'stencil' => $stencil,
                'errors' => ArrayHelper::merge($formErrors, $stencil->getErrors()),
            ]);

            return null;
        }

        $config = $stencil->getFormConfig();
        $notifications = ArrayHelper::remove($config, 'notifications', []);

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'id' => $stencil->id,
                'config' => $config,
                'notifications' => $notifications,
                'errors' => ArrayHelper::merge($formErrors, $stencil->getErrors()),
                'success' => !$formHasErrors && !$stencil->hasErrors(),
                'redirect' => $stencil->cpEditUrl,
                'redirectMessage' => Craft::t('formie', 'Stencil saved.'),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Stencil saved.'));

        return $this->redirectToPostedUrl($stencil);
    }

    /**
     * @throws Throwable
     */
    public function actionDeleteForm(): ?Response
    {
        $this->requirePostRequest();

        $this->requirePermission('formie-deleteForms');

        $request = Craft::$app->getRequest();
        $formId = $request->getRequiredBodyParam('formId');

        $form = Formie::$plugin->getForms()->getFormById($formId);

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        if (!Craft::$app->getElements()->deleteElement($form)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Form deleted.'));

        if ($request->getAcceptsJson()) {
            $url = Craft::$app->getRequest()->getValidatedBodyParam('redirect');
            $url = Craft::$app->getView()->renderObjectTemplate($url, $form);

            return $this->asJson([
                'success' => false,
                'redirect' => UrlHelper::url($url),
            ]);
        }

        return $this->redirectToPostedUrl($form);
    }

    public function actionRefreshTokens(): Response
    {
        $request = Craft::$app->getRequest();

        $params = [
            'csrf' => [
                'param' => $request->csrfParam,
                'token' => $request->getCsrfToken(),
                'input' => '<input type="hidden" name="' . $request->csrfParam . '" value="' . $request->getCsrfToken() . '">',
            ],
        ];

        // Add captchas into the payload
        $formHandle = $request->getParam('form');
        $form = Formie::$plugin->getForms()->getFormByHandle($formHandle);
        // Force fetch captchas because we're dealing with potential ajax forms
        // Normally, this function returns only if the `showAllPages` property is set.
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form, null, true);

        foreach ($captchas as $captcha) {
            if ($jsVariables = $captcha->getRefreshJsVariables($form)) {
                $params['captchas'][$captcha->handle] = $jsVariables;
            }
        }

        return $this->asJson($params);
    }

    public function actionGetExistingFields(): Response
    {
        $formId = Craft::$app->getRequest()->getParam('formId');

        $form = Formie::$plugin->getForms()->getFormById($formId);
        $existingFields = Formie::$plugin->getFields()->getExistingFields($form);

        return $this->asJson($existingFields);
    }

    public function actionGetExistingNotifications(): Response
    {
        $formId = Craft::$app->getRequest()->getParam('formId');

        $form = Formie::$plugin->getForms()->getFormById($formId);
        $existingNotifications = Formie::$plugin->getNotifications()->getExistingNotifications($form);

        return $this->asJson($existingNotifications);
    }


    // Private Methods
    // =========================================================================

    /**
     * Prepares the variable array for rendering the form builder.
     *
     * @param array $variables
     * @throws Throwable
     */
    private function _prepareVariableArray(array &$variables): void
    {
        // Locale related checks
        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites supported by this form');
        }

        if (empty($variables['site'])) {
            $site = $variables['site'] = Craft::$app->getSites()->currentSite;

            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $site = $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];

            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        if (empty($variables['form'])) {
            if (!empty($variables['formId'])) {
                $variables['form'] = Formie::$plugin->getForms()->getFormById($variables['formId'], $site->id);

                if (!$variables['form']) {
                    throw new Exception('Missing form data.');
                }
            } else {
                $variables['form'] = new Form();

                if (!empty($variables['site'])) {
                    $variables['form']->siteId = $site->id;
                }
            }
        }

        /** @var Form $form */
        $form = $variables['form'];

        // Enable locales
        if ($form->id) {
            $variables['enabledSiteIds'] = Craft::$app->getElements()->getEnabledSiteIdsForElement($form->id);
        } else {
            $variables['enabledSiteIds'] = [];

            foreach (Craft::$app->getSites()->getEditableSiteIds() as $site) {
                $variables['enabledSiteIds'][] = $site;
            }
        }

        // When there's only a single tab, it looks like Craft switches it to a null value.
        // Pretty bizarre default behaviour!
        $variables['tabs'] = $variables['formTabs'] = Formie::$plugin->getForms()->buildTabs($form);
        $variables['notificationsSchema'] = Formie::$plugin->getNotifications()->getNotificationsSchema();

        $notifications = $form->getNotifications();
        $notificationsConfig = Formie::$plugin->getNotifications()->getNotificationsConfig($notifications);

        $variables['formConfig'] = $form->getFormConfig();
        $variables['notifications'] = $notificationsConfig;
        $variables['variables'] = Variables::getVariablesArray();
        $variables['fields'] = Formie::$plugin->getFields()->getRegisteredFieldGroups();
        $variables['emailTemplates'] = Formie::$plugin->getEmailTemplates()->getAllTemplates();
        $variables['reservedHandles'] = Formie::$plugin->getFields()->getReservedHandles();
        $variables['groupedIntegrations'] = Formie::$plugin->getIntegrations()->getAllIntegrationsForForm();
        $variables['formHandles'] = $this->_getFormHandles($form->id);
        $variables['formUsage'] = Formie::$plugin->getForms()->getFormUsage($form);

        $variables['maxFormHandleLength'] = HandleHelper::getMaxFormHandle();
        $variables['maxFieldHandleLength'] = HandleHelper::getMaxFieldHandle();
    }

    private function _updateFormPermission($form): void
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return;
        }

        $suffix = ':' . $form->uid;

        $userService = Craft::$app->getUser();
        $currentUser = $userService->getIdentity();
        $permissions = Craft::$app->getUserPermissions()->getPermissionsByUserId($currentUser->id);
        $permissions[] = "formie-manageform{$suffix}";

        // Add all nested permissions according to top-level permissions set
        if ($userService->checkPermission('formie-manageFormAppearance')) {
            $permissions[] = "formie-manageFormAppearance{$suffix}";
        }

        if ($userService->checkPermission('formie-manageFormBehavior')) {
            $permissions[] = "formie-manageFormBehavior{$suffix}";
        }

        if ($userService->checkPermission('formie-manageNotifications')) {
            $permissions[] = "formie-manageNotifications{$suffix}";
        }

        if ($userService->checkPermission('formie-manageNotificationsAdvanced')) {
            $permissions[] = "formie-manageNotificationsAdvanced{$suffix}";
        }

        if ($userService->checkPermission('formie-manageNotificationsTemplates')) {
            $permissions[] = "formie-manageNotificationsTemplates{$suffix}";
        }

        if ($userService->checkPermission('formie-manageFormIntegrations')) {
            $permissions[] = "formie-manageFormIntegrations{$suffix}";
        }

        if ($userService->checkPermission('formie-manageFormSettings')) {
            $permissions[] = "formie-manageFormSettings{$suffix}";
        }

        // Check if they have "View Submissions" - they should have access to manage
        if ($userService->checkPermission('formie-viewSubmissions')) {
            $permissions[] = "formie-manageSubmission{$suffix}";
        }

        Craft::$app->getUserPermissions()->saveUserPermissions($currentUser->id, $permissions);
    }

    private function _getFormHandles($formId)
    {
        return (new Query())
            ->select(['handle'])
            ->from(['{{%formie_forms}}'])
            ->where(['not', ['id' => $formId]])
            ->column();
    }
}
