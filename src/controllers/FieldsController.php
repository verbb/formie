<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;

use Craft;
use craft\web\Controller;

use yii\web\Response;
use Throwable;

class FieldsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['get-summary-html'];


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if ($action->id === 'get-summary-html') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/fields', []);
    }

    public function actionGetElementSelectOptions(): Response
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

            // Fetch the element query for the field, so we can fetch the content (limited)
            $elements = $field->getPreviewElements();
        } catch (Throwable $e) {
            Formie::error(Craft::t('formie', 'Unable to fetch element select options: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $this->asJson($elements);
    }

    public function actionGetPredefinedOptions(): Response
    {
        $type = Craft::$app->getRequest()->getParam('option');

        $options = Formie::$plugin->getPredefinedOptions()->getPredefinedOptionsForType($type);

        return $this->asJson($options);
    }

    public function actionGetSummaryHtml(): string
    {
        $fieldId = Craft::$app->getRequest()->getParam('fieldId');
        $submissionId = Craft::$app->getRequest()->getParam('submissionId');
        
        if ($submissionId && $fieldId) {
            $submission = Submission::find()->id($submissionId)->isIncomplete(null)->one();

            if ($submission && $form = $submission->getForm()) {
                if ($field = $form->getFieldById($fieldId)) {
                    $value = $field->getFieldValue($submission);

                    return $field->getFrontEndInputHtml($form, $value);
                }
            }
        }

        return '';
    }

}
