<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\Settings;

use Craft;
use craft\web\Controller;

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
        $elements = [];

        try {
            $fieldData = Craft::$app->getRequest()->getParam('field');
            $type = $fieldData['type'];
            $fieldSettings = $fieldData['settings'];

            // Create a new fieldtype, and populate the settings
            $field = new $type();
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

}
