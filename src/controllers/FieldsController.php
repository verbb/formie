<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\Settings;

use Craft;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class FieldsController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = ['get-summary-html'];


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($action->id === 'get-summary-html') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * @inheritdoc
     */
    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/fields', []);
    }

    /**
     * @inheritdoc
     */
    public function actionGetElementSelectOptions()
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->_requireFormAuthoringPermission();

        $elements = [];

        $fieldData = Craft::$app->getRequest()->getParam('field');

        if (!is_array($fieldData) || !is_string($fieldData['type'] ?? null)) {
            throw new BadRequestHttpException('Invalid element field data.');
        }

        $fieldSettings = $fieldData['settings'] ?? [];

        if (!is_array($fieldSettings)) {
            throw new BadRequestHttpException('Invalid element field settings.');
        }

        $field = $this->_getRegisteredElementField($fieldData['type']);

        try {
            $field->sources = $fieldSettings['sources'] ?? [];
            $field->source = $fieldSettings['source'] ?? null;

            // Fetch the element query for the field so we can fetch the content (limited)
            $elements = $field->getPreviewElements();
        } catch (\Throwable $e) {
            Formie::error(Craft::t('formie', 'Unable to fetch element select options: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $this->asJson($elements);
    }

    /**
     * @inheritdoc
     */
    public function actionGetPredefinedOptions()
    {
        $type = Craft::$app->getRequest()->getParam('option');

        $options = Formie::$plugin->getPredefinedOptions()->getPredefinedOptionsForType($type);

        return $this->asJson($options);
    }

    /**
     * @inheritdoc
     */
    public function actionGetSummaryHtml()
    {
        $fieldId = Craft::$app->getRequest()->getParam('fieldId');
        $submissionId = Craft::$app->getRequest()->getParam('submissionId');

        if ($submissionId && $fieldId) {
            $submission = Submission::find()->id($submissionId)->isIncomplete(null)->one();

            if ($submission) {
                if ($form = $submission->getForm()) {
                    if ($field = $form->getFieldById($fieldId)) {
                        $value = $field->getFieldValue($submission);

                        return $field->getFrontEndInputHtml($form, $value);
                    }
                }
            }
        }

        return '';
    }


    // Private Methods
    // =========================================================================

    private function _getRegisteredElementField($type)
    {
        foreach (Formie::$plugin->getFields()->getRegisteredFields(false) as $field) {
            if (get_class($field) === $type && is_callable([$field, 'getPreviewElements'])) {
                return clone $field;
            }
        }

        throw new BadRequestHttpException('Invalid element field type.');
    }

    private function _requireFormAuthoringPermission()
    {
        $user = Craft::$app->getUser();

        if ($user->checkPermission('formie-createForms') || $user->checkPermission('formie-editForms')) {
            return;
        }

        $identity = $user->getIdentity();

        if ($identity && $identity->id) {
            $permissions = Craft::$app->getUserPermissions()->getPermissionsByUserId($identity->id);

            foreach ($permissions as $permission) {
                if (strpos(strtolower($permission), 'formie-manageform:') === 0) {
                    return;
                }
            }
        }

        throw new ForbiddenHttpException('User is not permitted to perform this action.');
    }

}
