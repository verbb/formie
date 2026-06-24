<?php
namespace verbb\formie\console\controllers;

use verbb\formie\migrations\plugins\MigrateFreeform4;
use verbb\formie\migrations\plugins\MigrateFreeform5;
use verbb\formie\migrations\plugins\MigrateSproutForms;

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
    public ?int $submissionBatchSize = null;
    public int $submissionOffset = 0;
    public ?int $submissionLimit = null;
    public bool $skipSubmissions = false;
    public bool $submissionsOnly = false;
    public bool $verboseSubmissionLogs = false;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        $options[] = 'formHandle';

        if (in_array($actionID, ['freeform4', 'freeform5'], true)) {
            $options[] = 'submissionBatchSize';
            $options[] = 'submissionOffset';
            $options[] = 'submissionLimit';
            $options[] = 'skipSubmissions';
            $options[] = 'submissionsOnly';
            $options[] = 'verboseSubmissionLogs';
        }

        return $options;
    }

    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), [
            'batch-size' => 'submissionBatchSize',
            'submission-offset' => 'submissionOffset',
            'submission-limit' => 'submissionLimit',
            'skip-submissions' => 'skipSubmissions',
            'submissions-only' => 'submissionsOnly',
            'verbose-submission-logs' => 'verboseSubmissionLogs',
        ]);
    }

    /**
     * Migrates Sprout Forms forms to Formie forms.
     */
    public function actionSproutForms(): int
    {
        $formIds = SproutFormsForm::find()->ids();

        if ($this->formHandle !== null) {
            $formHandle = explode(',', $this->formHandle);

            $formIds = SproutFormsForm::find()->handle($formHandle)->ids();
        }

        foreach ($formIds as $formId) {
            $this->stderr('Migrating Sprout Forms form #' . $formId . PHP_EOL, Console::FG_GREEN);

            $migration = new MigrateSproutForms(['formId' => $formId]);
            $result = $migration->run();
            $this->renderMigrationLines($result->lines);
        }

        return ExitCode::OK;
    }

    /**
     * Migrates Solspace Freeform 4 forms to Formie forms.
     */
    public function actionFreeform4(): int
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

            $migration = new MigrateFreeform4($this->getFreeformMigrationConfig($formId));
            $result = $migration->run();
            $this->renderMigrationLines($result->lines);
        }

        return ExitCode::OK;
    }

    /**
     * Migrates Solspace Freeform 5 forms to Formie forms.
     */
    public function actionFreeform5(): int
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

            $migration = new MigrateFreeform5($this->getFreeformMigrationConfig($formId));
            $result = $migration->run();
            $this->renderMigrationLines($result->lines);
        }

        return ExitCode::OK;
    }


    // Private Methods
    // =========================================================================

    private function getFreeformMigrationConfig(int $formId): array
    {
        $config = ['formId' => $formId];

        if ($this->submissionBatchSize !== null) {
            $config['submissionBatchSize'] = max(1, $this->submissionBatchSize);
        }

        if ($this->submissionOffset > 0) {
            $config['submissionOffset'] = $this->submissionOffset;
        }

        if ($this->submissionLimit !== null) {
            $config['submissionLimit'] = max(0, $this->submissionLimit);
        }

        if ($this->skipSubmissions) {
            $config['skipSubmissions'] = true;
        }

        if ($this->submissionsOnly) {
            $config['submissionsOnly'] = true;
        }

        if ($this->verboseSubmissionLogs) {
            $config['verboseSubmissionLogs'] = true;
        }

        return $config;
    }

    private function renderMigrationLines(array $lines): void
    {
        foreach ($lines as $line) {
            $color = match ($line->level ?? 'info') {
                'success' => Console::FG_GREEN,
                'warning' => Console::FG_YELLOW,
                'error' => Console::FG_RED,
                default => Console::FG_GREY,
            };

            $prefix = ($line->depth ?? 0) > 0 ? '> ' : '';

            $this->stdout($prefix . (string)($line->message ?? '') . PHP_EOL, $color);
        }
    }
}
