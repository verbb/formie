<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;

use Craft;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\web\Controller;

use yii\base\InvalidConfigException;
use yii\web\HttpException;
use yii\web\Response;

use Throwable;

class StencilsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $stencils = Formie::$plugin->getStencils()->getAllStencils();

        // Sort stencils by name
        usort($stencils, function($a, $b) {
            return strcmp($a->name, $b->name);
        });

        return $this->renderTemplate('formie/settings/stencils', compact('stencils'));
    }

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

    public function actionEdit(int $id = null, Stencil $stencil = null): Response
    {
        $variables = compact('id', 'stencil');

        $this->_prepareVariableArray($variables);

        // For form builder compatibility.
        $variables['form'] = $variables['stencil'];

        Plugin::registerAsset('src/js/formie-form.js');

        return $this->renderTemplate('formie/settings/stencils/_edit', $variables);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = $this->request;

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
            $stencil->name = Craft::t('formie', '{name} Copy', ['name' => $stencil->name]);

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

        // Set form data.
        $stencil->data = new StencilData();
        $stencil->data->userDeletedAction = $request->getParam('userDeletedAction', $stencil->data->userDeletedAction);
        $stencil->data->fileUploadsAction = $request->getParam('fileUploadsAction', $stencil->data->fileUploadsAction);
        $stencil->data->dataRetention = $request->getParam('dataRetention', $stencil->data->dataRetention);
        $stencil->data->dataRetentionValue = $request->getParam('dataRetentionValue', $stencil->data->dataRetentionValue);

        // Build temp form for validation.
        $form = Formie::$plugin->getForms()->buildFormFromPost();

        // Populate the stencil data with data prepped for the form
        $stencil->data->populateFormData($form);

        // Don't validate the handle.
        $form->handle .= mt_rand();

        $form->validate();

        $formErrors = $form->getErrors();

        // Save it
        if ($formErrors || !Formie::$plugin->getStencils()->saveStencil($stencil)) {
            $stencil->name = $originalName;

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'config' => $form->getFormBuilderConfig(),
                    'notifications' => $form->getNotificationsConfig(),
                ]);
            }

            $this->setFailFlash(Craft::t('formie', 'Couldn’t save stencil.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'form' => $stencil,
                'stencil' => $stencil,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'config' => $stencil->getFormBuilderConfig(),
                'notifications' => $stencil->getNotificationsConfig(),
                'redirect' => ($duplicate) ? $stencil->getCpEditUrl() : null,
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
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive stencil.')]);
    }


    // Private Methods
    // =========================================================================

    private function _prepareVariableArray(array &$variables): void
    {
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

        $settings = Formie::$plugin->getSettings();

        /** @var Stencil $stencil */
        $stencil = $variables['stencil'];

        $variables['notificationsSchema'] = Formie::$plugin->getNotifications()->getNotificationsSchema();
        $variables['groupedIntegrations'] = Formie::$plugin->getIntegrations()->getAllIntegrationsForForm();
        $variables['formUsage'] = [];

        $variables['jsBuilderConfig'] = [
            'config' => $stencil->getFormBuilderConfig(),
            'fields' => Formie::$plugin->getFields()->getFormBuilderFieldTypes(),
            'notifications' => $stencil->getNotificationsConfig(),
            'variables' => Variables::getVariablesArray(),
            'emailTemplates' => Formie::$plugin->getEmailTemplates()->getAllTemplates(),
            'reservedHandles' => Formie::$plugin->getFields()->getReservedHandles(),
            'formHandles' => $this->_getStencilHandles($stencil->id),
            'statuses' => Formie::$plugin->getStatuses()->getAllStatuses(),
            'maxFormHandleLength' => HandleHelper::getMaxFormHandle(),
            'maxFieldHandleLength' => HandleHelper::getMaxFieldHandle(),
            'filterIntegrationMapping' => $settings->filterIntegrationMapping,
        ];

        $variables['tabs'] = Formie::$plugin->getForms()->getFormBuilderTabs();
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
