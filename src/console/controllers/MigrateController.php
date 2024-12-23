<?php
namespace verbb\formie\console\controllers;

use verbb\formie\migrations\MigrateFreeform4;
use verbb\formie\migrations\MigrateFreeform5;
use verbb\formie\migrations\MigrateSproutForms;

use craft\console\Controller;
use craft\helpers\Console;

use Throwable;

use yii\console\ExitCode;

use barrelstrength\sproutforms\elements\Form as SproutFormsForm;
use solspace\freeform\Freeform;

/**
 * Manages Formie migrations from other plugins.
 */
class MigrateController extends Controller
{
    // Properties
    // =========================================================================

    public ?string $formHandle = null;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        $options[] = 'formHandle';

        return $options;
    }

    /**
     * Migrates Sprout Forms forms to Formie forms.
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
     * Migrates Solspace Freeform 4 forms to Formie forms.
     */
    public function actionMigrateFreeform4(): int
    {
        $formIds = Freeform::getInstance()->forms->getAllFormIds();

        if ($this->formHandle !== null) {
            $formHandles = explode(',', $this->formHandle);

            $formIds = [];

            foreach ($formHandles as $formHandle) {
                $formIds[] = Freeform::getInstance()->forms->getFormByHandle($formHandle)->getId();
            }
        }

        foreach ($formIds as $formId) {
            $this->stderr('Migrating Freeform form #' . $formId . PHP_EOL, Console::FG_GREEN);

            $migration = new MigrateFreeform4(['formId' => $formId]);
            $migration->setConsoleRequest($this);
            $migration->up();
        }

        return ExitCode::OK;
    }

    /**
     * Migrates Solspace Freeform 5 forms to Formie forms.
     */
    public function actionMigrateFreeform5(): int
    {
        $formIds = Freeform::getInstance()->forms->getAllFormIds();

        if ($this->formHandle !== null) {
            $formHandles = explode(',', $this->formHandle);

            $formIds = [];

            foreach ($formHandles as $formHandle) {
                $formIds[] = Freeform::getInstance()->forms->getFormByHandle($formHandle)->getId();
            }
        }

        foreach ($formIds as $formId) {
            $this->stderr('Migrating Freeform form #' . $formId . PHP_EOL, Console::FG_GREEN);

            $migration = new MigrateFreeform5(['formId' => $formId]);
            $migration->setConsoleRequest($this);
            $migration->up();
        }

        return ExitCode::OK;
    }
}
