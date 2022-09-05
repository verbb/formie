<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;

use yii\base\InvalidConfigException;
use yii\web\HttpException;
use yii\web\Response;

use Throwable;

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
    public function actionNew(Stencil $stencil = null): ?Response
    {
        $stencils = Formie::$plugin->getStencils()->getAllStencils();
        $stencilHandles = ArrayHelper::getColumn($stencils, 'handle');

        $variables = compact('stencilHandles', 'stencil');

        if (!$variables['stencil']) {
            $variables['stencil'] = new Stencil();
        }

        $variables['maxFormHandleLength'] = HandleHelper::getMaxFormHandle();

        Plugin::registerAsset('src/js/formie-form-new.js');

        return $this->renderTemplate('formie/settings/stencils/_new', $variables);
    }

    /**
     * @param int|null $id
     * @param Stencil|null $stencil
     * @return Response
     * @throws HttpException
     * @throws InvalidConfigException
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

        $config = $stencil->getFormConfig();
        $notifications = ArrayHelper::remove($config, 'notifications', []);

        $variables['formConfig'] = $config;
        $variables['notifications'] = $notifications;
        $variables['variables'] = Variables::getVariablesArray();
        $variables['fields'] = Formie::$plugin->getFields()->getRegisteredFieldGroups();
        $variables['emailTemplates'] = Formie::$plugin->getEmailTemplates()->getAllTemplates();
        $variables['reservedHandles'] = Formie::$plugin->getFields()->getReservedHandles();
        $variables['groupedIntegrations'] = Formie::$plugin->getIntegrations()->getAllIntegrationsForForm();
        $variables['formHandles'] = ArrayHelper::getColumn($allStencils, 'handle');
        $variables['formUsage'] = [];

        $variables['notificationsSchema'] = Formie::$plugin->getNotifications()->getNotificationsSchema();

        $variables['maxFormHandleLength'] = HandleHelper::getMaxFormHandle();
        $variables['maxFieldHandleLength'] = HandleHelper::getMaxFieldHandle();

        Plugin::registerAsset('src/js/formie-form.js');

        return $this->renderTemplate('formie/settings/stencils/_edit', $variables);
    }

    /**
     * @return Response|null
     * @throws Throwable
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $notificationsConfig = null;
        $form = null;

        $duplicate = (bool)$request->getParam('duplicateStencil');

        $stencil = new Stencil();
        $stencil->id = $request->getParam('stencilId');
        $stencil->name = $request->getParam('title', $stencil->name);
        $stencil->handle = $request->getParam('handle', $stencil->handle);
        $stencil->submitActionEntryId = $request->getParam('submitActionEntryId.id');
        $stencil->submitActionEntrySiteId = $request->getParam('submitActionEntryId.siteId');

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
            $stencil->data->userDeletedAction = $request->getParam('userDeletedAction', $stencil->data->userDeletedAction);
            $stencil->data->fileUploadsAction = $request->getParam('fileUploadsAction', $stencil->data->fileUploadsAction);
            $stencil->data->dataRetention = $request->getParam('dataRetention', $stencil->data->dataRetention);
            $stencil->data->dataRetentionValue = $request->getParam('dataRetentionValue', $stencil->data->dataRetentionValue);

            // Build temp form for validation.
            $form = Formie::$plugin->getForms()->buildFormFromPost();

            // Don't validate the handle.
            $form->handle .= mt_rand();

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
                $notificationsConfig[$key]['id'] = StringHelper::appendRandomString('stencil', 16);
            }
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $stencil->id,
                'config' => $stencil->getFormConfig(),
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
    public function actionDelete(): Response
    {
        $this->requireAcceptsJson();

        $stencilId = Craft::$app->getRequest()->getRequiredParam('id');

        if (Formie::$plugin->getStencils()->deleteStencilById((int)$stencilId)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive stencil.')]);
    }
}
