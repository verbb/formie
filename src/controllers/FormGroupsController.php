<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\Plugin;
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
        $routeParams = Craft::$app->getUrlManager()->getRouteParams();
        $postedSettings = $routeParams['postedSettings'] ?? null;

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
            $editorConfig = Formie::$plugin->getFormGroupDefaults()->getEditorConfig($variables['formGroup']);

            if (is_array($postedSettings)) {
                $editorConfig['values'] = $postedSettings;
            }

            if ($variables['formGroup']->hasErrors()) {
                $editorConfig['errors'] = $variables['formGroup']->getErrors();
            }

            Plugin::registerCpFormGroupSettingsAssets();
            $this->view->registerJs('new Craft.Formie.FormGroupSettings(' . Json::encode($editorConfig) . ');');

            $variables['groupSettingsPayload'] = Json::encode($editorConfig['values']);
            $variables['continueEditingUrl'] = 'formie/settings/form-groups/edit/' . $variables['formGroup']->id;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new form group');
            $variables['continueEditingUrl'] = 'formie/settings/form-groups/edit/{id}';
        }

        $variables['canEdit'] = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        return $this->renderTemplate('formie/settings/form-groups/_edit', $variables);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $request = $this->request;

        $formGroup = new FormGroup();
        $formGroup->id = $request->getBodyParam('id');
        $formGroup->name = $request->getBodyParam('name');
        $formGroup->handle = $request->getBodyParam('handle');

        $postedSettings = null;

        if ($formGroup->id) {
            $existingGroup = Formie::$plugin->getFormGroups()->getGroupById((int)$formGroup->id);
            $formGroup->sortOrder = $existingGroup?->sortOrder;

            $postedSettings = $this->_decodeSettingsPayload();

            if ($postedSettings === null) {
                return $this->_failSave(
                    $formGroup,
                    null,
                    Craft::t('formie', 'Invalid form group settings payload.'),
                );
            }

            $formGroup->name = trim((string)($postedSettings['name'] ?? $formGroup->name));
            $formGroup->handle = trim((string)($postedSettings['handle'] ?? $formGroup->handle));

            if (!Formie::$plugin->getFormGroupDefaults()->applyPayload($formGroup, $postedSettings)) {
                return $this->_failSave($formGroup, $postedSettings);
            }
        }

        if (Formie::$plugin->getFormGroups()->saveGroup($formGroup)) {
            $this->setSuccessFlash(Craft::t('formie', 'Form group saved.'));

            return $this->redirectToPostedUrl($formGroup);
        }

        return $this->_failSave($formGroup, $postedSettings);
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
        $this->requirePostRequest();

        $groupId = (int)$this->request->getRequiredParam('id');

        if (Formie::$plugin->getFormGroups()->deleteGroupById($groupId)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => true]);
            }

            $this->setSuccessFlash(Craft::t('formie', 'Form group deleted.'));

            return $this->redirectToPostedUrl();
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive form group.')]);
        }

        $this->setFailFlash(Craft::t('formie', 'Couldn’t archive form group.'));

        return $this->redirectToPostedUrl();
    }


    // Private Methods
    // =========================================================================

    private function _decodeSettingsPayload(): ?array
    {
        $settingsJson = $this->request->getBodyParam('settings');

        if (!is_string($settingsJson) || $settingsJson === '') {
            return null;
        }

        try {
            $payload = Json::decode($settingsJson);
        } catch (\Throwable) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    private function _failSave(FormGroup $formGroup, ?array $postedSettings = null, ?string $message = null): ?Response
    {
        $this->setFailFlash($message ?? Craft::t('formie', 'Couldn’t save form group.'));

        Craft::$app->getUrlManager()->setRouteParams(array_filter([
            'formGroup' => $formGroup,
            'postedSettings' => $postedSettings,
        ]));

        return null;
    }
}
