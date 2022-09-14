<?php
namespace verbb\formie\console\controllers;

use verbb\formie\elements\Form;

use Craft;
use craft\helpers\Db;

use Throwable;

use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class FormsController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The form ID(s) to delete. Can be set to multiple comma-separated IDs.
     */
    public $formId;

    /**
     * @var string|null The form handle(s) to delete. Can be set to multiple comma-separated handles.
     */
    public $formHandle;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        if ($actionID === 'delete') {
            $options[] = 'formId';
            $options[] = 'formHandle';
        }

        return $options;
    }

    /**
     * Deletes all forms.
     *
     * @return int
     * @throws Throwable
     */
    public function actionDelete(): int
    {
        $formIds = null;

        if ($this->formId !== null) {
            $formIds = explode(',', $this->formId);
        }

        if ($this->formHandle !== null) {
            $formHandle = explode(',', $this->formHandle);

            $formIds = Form::find()->handle($formHandle)->ids();
        }

        if (!$this->formId && !$this->formHandle) {
            $this->stderr('You must provide either a --form-id or --form-handle option.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$formIds) {
            $this->stderr('Unable to find any matching forms.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach ($formIds as $formId) {
            $query = Form::find()->formId($formId);

            $count = (int)$query->count();

            if ($count === 0) {
                $this->stdout('No forms exist for that criteria.' . PHP_EOL, Console::FG_YELLOW);

                continue;
            }

            $elementsText = $count === 1 ? 'form' : 'forms';
            $this->stdout("Deleting {$count} {$elementsText} for form #{$formId} ..." . PHP_EOL, Console::FG_YELLOW);

            $elementsService = Craft::$app->getElements();

            foreach (Db::each($query) as $element) {
                $elementsService->deleteElement($element);

                $this->stdout("Deleted form #{$element->id} ..." . PHP_EOL, Console::FG_GREEN);
            }
        }

        return ExitCode::OK;
    }
}
