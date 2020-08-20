<?php
namespace verbb\formie\controllers;

use Craft;
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

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;

class FormsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->requirePermission('formie-manageForms');

        parent::init();
    }

    /**
     * Shows all the forms in a list.
     *
     * @return Response|null
     */
    public function actionIndex()
    {
        return $this->renderTemplate('formie/forms/index', []);
    }

    /**
     * Creates a new form with a pretty interface.
     *
     * @param Form|null $form
     * @return Response|null
     */
    public function actionNew(Form $form = null)
    {
        $formHandles = ArrayHelper::getColumn(Form::find()->all(), 'handle');
        $stencilArray = Formie::$plugin->getStencils()->getStencilArray();

        $variables = compact('formHandles', 'form', 'stencilArray');
        if (!$variables['form']) {
            $variables['form'] = new Form();
        }

        $variables['reservedHandles'] = Formie::$plugin->getFields()->getReservedHandles();

        return $this->renderTemplate('formie/forms/_new', $variables);
    }

    /**
     * Renders the main form builder interface.
     *
     * @param int|null $formId
     * @param string|null $siteHandle
     * @param Form|null $form
     * @return Response|null
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

        $this->_prepareVariableArray($variables);

        if (!empty($variables['form']->id)) {
            $variables['title'] = $variables['form']->title;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new form');
        }

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'formie/forms/edit/{id}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] .
            (Craft::$app->getIsMultiSite() && Craft::$app->getSites()->currentSite->id !== $variables['site']->id ? '/' . $variables['site']->handle : '');

        return $this->renderTemplate('formie/forms/_edit', $variables);
    }

    /**
     * Saves a form.
     *
     * @return Response|null
     * @throws Throwable
     */
    public function actionSave()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $this->requirePermission('formie-editForms');

        $form = Formie::$plugin->getForms()->buildFormFromPost();
        $duplicate = $request->getParam('duplicate');

        if (!Formie::$plugin->getForms()->saveForm($form)) {
            if ($request->getAcceptsJson()) {
                $notifications = $form->getNotifications();
                $notificationsConfig = Formie::$plugin->getNotifications()->getNotificationsConfig($notifications);

                return $this->asJson([
                    'success' => false,
                    'id' => $form->id,
                    'config' => $form->getFormConfig(),
                    'notifications' => $notificationsConfig,
                    'errors' => $form->getErrors(),
                    'fieldLayoutId' => $form->fieldLayoutId,
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save form.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $form,
                'errors' => $form->getErrors(),
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $form->id,
                'config' => $form->getFormConfig(),
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
     * @return Response|null
     * @throws Throwable
     */
    public function actionSaveAsStencil()
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
            $stencil->data->requireUser = $request->getParam('requireUser', $stencil->data->requireUser);
            $stencil->data->availability = $request->getParam('availability', $stencil->data->availability);
            $stencil->data->userDeletedAction = $request->getParam('userDeletedAction', $stencil->data->userDeletedAction);
            $stencil->data->dataRetention = $request->getParam('dataRetention', $stencil->data->dataRetention);
            $stencil->data->dataRetentionValue = $request->getParam('dataRetentionValue', $stencil->data->dataRetentionValue);
            $stencil->data->availabilitySubmissions = $request->getParam('availabilitySubmissions', $stencil->data->availabilitySubmissions);
            $stencil->data->availabilityFrom = (($date = $request->getParam('availabilityFrom')) !== false ? (DateTimeHelper::toDateTime($date) ?: null) : $stencil->data->availabilityFrom);
            $stencil->data->availabilityTo = (($date = $request->getParam('availabilityTo')) !== false ? (DateTimeHelper::toDateTime($date) ?: null) : $stencil->data->availabilityTo);

            // Build temp form for validation.
            $form = Formie::$plugin->getForms()->buildFormFromPost();

            // Don't validate the handle.
            $form->handle .= rand();

            $form->validate();

            $formHasErrors = $form->hasErrors();
            $formErrors = $form->getErrors();
        } else {
            $formHasErrors = false;
            $formErrors = [];
        }

        // Save it
        if ($formHasErrors || !Formie::$plugin->getStencils()->saveStencil($stencil)) {
            $config = $stencil->getConfig();
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

        $config = $stencil->getConfig();
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
     * Returns tabs and fields HTML when the form template is switched.
     *
     * @return Response
     * @throws Throwable
     */
    public function actionSwitchTemplate()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $form = Formie::$plugin->getForms()->buildFormFromPost();

        $variables = [];
        $variables['form'] = $form;
        $variables['templateId'] = $form->templateId;

        $this->_prepareVariableArray($variables);

        $view = Craft::$app->getView();

        $fieldsHtml = [];

        if ($fieldLayout = $form->getFieldLayout()) {
            foreach ($fieldLayout->getTabs() as $tab) {
                $tabSlug = StringHelper::toKebabCase($tab->name);

                $fieldsHtml[] = [
                    'id' => "tab-form-fields-$tabSlug",
                    'html' => $view->renderTemplate('_includes/fields', [
                        'element' => $form,
                        'fields' => $tab->getFields(),
                    ]),
                ];
            }
        }

        $tabsHtml = $view->renderTemplate('_includes/tabs', $variables);
        $positionsHtml = $view->renderTemplate('formie/forms/_panes/_positions', $variables);

        $headHtml = $view->getHeadHtml();
        $bodyHtml = $view->getBodyHtml();

        return $this->asJson(compact(
            'tabsHtml',
            'fieldsHtml',
            'positionsHtml',
            'headHtml',
            'bodyHtml'
        ));
    }

    /**
     * @return Response|null
     * @throws Throwable
     */
    public function actionDeleteForm()
    {
        $this->requirePostRequest();

        $this->requirePermission('formie-editForms');

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

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
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


    // Private Methods
    // =========================================================================

    /**
     * Prepares the variable array for rendering the form builder.
     *
     * @param array $variables
     * @throws Throwable
     */
    private function _prepareVariableArray(&$variables)
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
                    /** @var Site $site */
                    $site = $variables['site'];

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
        $variables['existingFields'] = Formie::$plugin->getFields()->getExistingFields($form);
        $variables['existingNotifications'] = Formie::$plugin->getNotifications()->getExistingNotifications($form);
        $variables['emailTemplates'] = Formie::$plugin->getEmailTemplates()->getAllTemplates();
        $variables['reservedHandles'] = Formie::$plugin->getFields()->getReservedHandles();
        $variables['integrations'] = Formie::$plugin->getintegrations()->getAllFormIntegrations();
        $variables['groupedIntegrations'] = Formie::$plugin->getintegrations()->getAllGroupedIntegrations(true, true);
        $variables['formHandles'] = ArrayHelper::getColumn(Form::find()->id('not ' . $form->id)->all(), 'handle');
    }
}
