<?php
namespace verbb\formie\console\controllers;

use verbb\formie\migrations\MigrateFreeform;
use verbb\formie\migrations\MigrateSproutForms;

use Throwable;

use yii\helpers\Console;
use yii\console\Controller;
use yii\console\ExitCode;

use barrelstrength\sproutforms\elements\Form as SproutFormsForm;
use solspace\freeform\Freeform;

class MigrateController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The form handle(s) to migrate. Can be set to multiple comma-separated handles.
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

        $options[] = 'formHandle';

        return $options;
    }

    /**
     * Migrates Sprout Forms forms, notifications and submissions.
     *
     * @return int
     * @throws Throwable
     */
    public function actionMigrateSproutForms(): int
    {
        $formIds = SproutFormsForm::find()->ids();

        if ($this->formHandle !== null) {
            $formHandle = explode(',', $this->formHandle);

            $formIds = SproutFormsForm::find()->handle($formHandle)->ids();
        }

        foreach ($formIds as $formId) {
            $this->stderr('Migrating Sprout Forms form #' . $formId . PHP_EOL, Console::FG_GREEN);

            $migration = new MigrateSproutForms(['formId' => $formId]);
            $migration->setConsoleRequest($this);
            $migration->up();
        }

        return ExitCode::OK;
    }

    /**
     * Migrates Freeform forms, notifications and submissions.
     *
     * @return int
     * @throws Throwable
     */
    public function actionMigrateFreeform(): int
    {
        $formIds = Freeform::getInstance()->forms->getAllFormIds();

        if ($this->formHandle !== null) {
            $formHandles = explode(',', $this->formHandle);

            $formIds = [];

            foreach ($formHandles as $formHandle) {
                $formIds[] = Freeform::getInstance()->forms->getFormByHandle($formHandle)->id;
            }
        }

        foreach ($formIds as $formId) {
            $this->stderr('Migrating Freeform form #' . $formId . PHP_EOL, Console::FG_GREEN);

            $migration = new MigrateFreeform(['formId' => $formId]);
            $migration->setConsoleRequest($this);
            $migration->up();
        }

        return ExitCode::OK;
    }
}
