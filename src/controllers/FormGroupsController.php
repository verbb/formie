<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\FormGroup;

use Craft;
use craft\helpers\Json;

use yii\web\HttpException;
use yii\web\Response;

class FormGroupsController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $formGroups = Formie::$plugin->getFormGroups()->getAllGroups();

        return $this->renderTemplate('formie/settings/form-groups', compact('formGroups'));
    }

    public function actionEdit(int $id = null, FormGroup $formGroup = null): Response
    {
        $variables = compact('id', 'formGroup');

        if (!$variables['formGroup']) {
            if ($variables['id']) {
                $variables['formGroup'] = Formie::$plugin->getFormGroups()->getGroupById($variables['id']);

                if (!$variables['formGroup']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['formGroup'] = new FormGroup();
            }
        }

        if ($variables['formGroup']->id) {
            $variables['title'] = $variables['formGroup']->name;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new form group');
        }

        return $this->renderTemplate('formie/settings/form-groups/_edit', $variables);
    }

    public function actionSave(): void
    {
        $this->requirePostRequest();
        $request = $this->request;

        $formGroup = new FormGroup();
        $formGroup->id = $request->getBodyParam('id');
        $formGroup->name = $request->getBodyParam('name');
        $formGroup->handle = $request->getBodyParam('handle');

        if (Formie::$plugin->getFormGroups()->saveGroup($formGroup)) {
            $this->setSuccessFlash(Craft::t('formie', 'Form group saved.'));
            $this->redirectToPostedUrl($formGroup);
        } else {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save form group.'));
        }

        Craft::$app->getUrlManager()->setRouteParams(compact('formGroup'));
    }

    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $ids = Json::decode($this->request->getRequiredBodyParam('ids'));

        if (Formie::$plugin->getFormGroups()->reorderGroups($ids)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t reorder form groups.')]);
    }

    public function actionDelete(): Response
    {
        $this->requireAcceptsJson();

        $groupId = (int)$this->request->getRequiredParam('id');

        if (Formie::$plugin->getFormGroups()->deleteGroupById($groupId)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive form group.')]);
    }
}
