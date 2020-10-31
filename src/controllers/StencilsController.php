<?php
namespace verbb\formie\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\web\Controller;

use verbb\formie\Formie;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;

use Throwable;
use yii\web\HttpException;
use yii\web\Response;

class StencilsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @return Response
     */
    public function actionIndex(): Response
    {
        $stencils = Formie::$plugin->getStencils()->getAllStencils();

        return $this->renderTemplate('formie/settings/stencils', compact('stencils'));
    }

    /**
     * Creates a new stencil with a pretty interface.
     *
     * @param Stencil|null $stencil
     * @return Response|null
     */
    public function actionNew(Stencil $stencil = null)
    {
        $stencils = Formie::$plugin->getStencils()->getAllStencils();
        $stencilHandles = ArrayHelper::getColumn($stencils, 'handle');

        $variables = compact('stencilHandles', 'stencil');

        if (!$variables['stencil']) {
            $variables['stencil'] = new Stencil();
        }

        return $this->renderTemplate('formie/settings/stencils/_new', $variables);
    }

    /**
     * @param int|null $id
     * @param Stencil|null $stencil
     * @return Response|null
     * @throws HttpException
     */
    public function actionEdit(int $id = null, Stencil $stencil = null): Response
    {
        $variables = compact('id', 'stencil');

        if (!$variables['stencil']) {
            if ($variables['id']) {
                $variables['stencil'] = Formie::$plugin->getStencils()->getStencilById($variables['id']);

                if (!$variables['stencil']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['stencil'] = new Stencil();
            }
        }

        if ($variables['stencil']->id) {
            $variables['title'] = $variables['stencil']->name;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new stencil');
        }

        /* @var Stencil $stencil */
        $stencil = $variables['stencil'];

        // For form builder compatibility.
        $variables['form'] = $stencil;

        $allStencils = Formie::$plugin->getStencils()->getAllStencils();

        $variables['tabs'] = $variables['formTabs'] = Formie::$plugin->getForms()->buildTabs();
        $variables['notificationsTabs'] = Formie::$plugin->getForms()->buildNotificationTabs();

        $config = $stencil->getConfig();
        $notifications = ArrayHelper::remove($config, 'notifications', []);

        $variables['formConfig'] = $config;
        $variables['notifications'] = $notifications;
        $variables['variables'] = Variables::getVariablesArray();
        $variables['fields'] = Formie::$plugin->getFields()->getRegisteredFieldGroups();
        $variables['emailTemplates'] = Formie::$plugin->getEmailTemplates()->getAllTemplates();
        $variables['reservedHandles'] = Formie::$plugin->getFields()->getReservedHandles();
        $variables['groupedIntegrations'] = Formie::$plugin->getIntegrations()->getAllIntegrationsForForm();
        $variables['formHandles'] = ArrayHelper::getColumn($allStencils, 'handle');

        $variables['notificationsSchema'] = Formie::$plugin->getNotifications()->getNotificationsSchema();

        return $this->renderTemplate('formie/settings/stencils/_edit', $variables);
    }

    /**
     * @return Response|null
     * @throws Throwable
     */
    public function actionSave()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $notificationsConfig = null;
        $form = null;

        $stencilId = $request->getParam('stencilId');
        $duplicate = $request->getParam('duplicateStencil');

        $stencil = Formie::$plugin->getStencils()->getStencilById($stencilId);

        if (!$stencil) {
            $stencil = new Stencil();
        }

        $stencil->name = $request->getParam('title', $stencil->name);
        $stencil->handle = $request->getParam('handle', $stencil->handle);

        $originalName = $stencil->name;

        if ($duplicate) {
            $stencil = clone $stencil;
            $stencil->id = null;
            $stencil->uid = null;
            $stencil->name .= ' ' . Craft::t('formie', 'Copy');

            $stencils = Formie::$plugin->getStencils()->getAllStencils();
            $stencilHandles = ArrayHelper::getColumn($stencils, 'handle');
            $stencil->handle = HandleHelper::getUniqueHandle($stencilHandles, $stencil->handle);
        }

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
            $stencil->name = $originalName;

            if ($request->getAcceptsJson()) {
                $notifications = $form->getNotifications();
                $notificationsConfig = Formie::$plugin->getNotifications()->getNotificationsConfig($notifications);

                return $this->asJson([
                    'success' => false,
                    'id' => $stencil->id,
                    'config' => $form->getFormConfig(),
                    'notifications' => $notificationsConfig,
                    'errors' => ArrayHelper::merge($formErrors, $stencil->getErrors()),
                    'fieldLayoutId' => $form->fieldLayoutId,
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

        if ($form) {
            $notifications = $form->getNotifications();
            $notificationsConfig = Formie::$plugin->getNotifications()->getNotificationsConfig($notifications);

            // Setup fake IDs for the notifications. They're saved on the stencil, not models, but show like they are
            foreach ($notificationsConfig as $key => $notification) {
                // Generate a fake ID just for stencils. Helps to not show it as saved
                $notificationsConfig[$key]['id'] = uniqId('stencil');
            }
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $stencil->id,
                'config' => $stencil->getConfig(),
                'notifications' => $notificationsConfig,
                'errors' => ArrayHelper::merge($formErrors, $stencil->getErrors()),
                'fieldLayoutId' => $form->fieldLayoutId,
                'redirect' => ($duplicate) ? $stencil->cpEditUrl : null,
                'redirectMessage' => Craft::t('formie', 'Stencil saved.'),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Stencil saved.'));

        return $this->redirectToPostedUrl($stencil);
    }

    /**
     * @return Response
     * @throws Throwable
     */
    public function actionDelete()
    {
        $this->requireAcceptsJson();

        $stencilId = Craft::$app->getRequest()->getRequiredParam('id');

        if (Formie::$plugin->getStencils()->deleteStencilById((int)$stencilId)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive stencil.')]);
    }
}
