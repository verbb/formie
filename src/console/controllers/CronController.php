<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;

use yii\console\ExitCode;

/**
 * Runs Formie tasks that are intended to be scheduled on cron.
 */
class CronController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether cleanup tasks should be skipped.
     */
    public bool $skipGc = false;

    /**
     * @var bool Whether scheduled report delivery should be skipped.
     */
    public bool $skipReports = false;

    /**
     * @var string|null Comma-separated task groups to run: `gc`, `reports`. Omit to run all groups.
     */
    public ?string $only = null;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'run') {
            $options[] = 'skipGc';
            $options[] = 'skipReports';
            $options[] = 'only';
        }

        return $options;
    }

    /**
     * Runs Formie cron tasks (cleanup and scheduled reports).
     *
     * Schedule this command on cron for production sites — for example, hourly:
     *
     * ```
     * ./craft formie/cron/run
     * ```
     *
     * Use `--skip-reports` or `--skip-gc` when you want separate schedules for cleanup and report delivery.
     * Use `--only=gc` or `--only=reports` to run a single task group.
     */
    public function actionRun(): int
    {
        $groups = $this->_resolveGroups();

        if ($groups === null) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $exitCode = ExitCode::OK;

        if (in_array('gc', $groups, true)) {
            $this->stdout("Running Formie cleanup tasks ...\n", Console::FG_YELLOW);
            Formie::$plugin->getCleanup()->runAll($this);
            $this->stdout("Finished Formie cleanup tasks.\n", Console::FG_GREEN);
        }

        if (in_array('reports', $groups, true)) {
            $reportsExitCode = $this->_runScheduledReports();

            if ($reportsExitCode !== ExitCode::OK) {
                $exitCode = $reportsExitCode;
            }
        }

        return $exitCode;
    }

    private function _runScheduledReports(): int
    {
        $scheduledReports = Formie::$plugin->getScheduledReports()->getDueScheduledReports();

        if (!$scheduledReports) {
            $this->stdout("No scheduled reports are due.\n");

            return ExitCode::OK;
        }

        $sent = 0;
        $failed = 0;

        foreach ($scheduledReports as $scheduledReport) {
            $this->stdout("Sending scheduled report “{$scheduledReport->name}”…\n", Console::FG_YELLOW);

            try {
                Formie::$plugin->getReportScheduledDelivery()->send($scheduledReport);
                $sent++;
                $this->stdout("Sent.\n", Console::FG_GREEN);
            } catch (\Throwable $e) {
                $failed++;
                $this->stderr("Failed: {$e->getMessage()}\n", Console::FG_RED);
                Craft::error('Scheduled report delivery failed: ' . $e->getMessage(), __METHOD__);
            }
        }

        $this->stdout("Completed: {$sent} sent, {$failed} failed.\n");

        return $failed > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }


    // Private Methods
    // =========================================================================

    /**
     * @return string[]|null
     */
    private function _resolveGroups(): ?array
    {
        if ($this->only !== null && $this->only !== '') {
            $groups = array_values(array_filter(array_map('trim', explode(',', $this->only))));

            foreach ($groups as $group) {
                if (!in_array($group, ['gc', 'reports'], true)) {
                    $this->stderr("Unknown cron task group: $group\n", Console::FG_RED);

                    return null;
                }
            }

            return $groups;
        }

        $groups = [];

        if (!$this->skipGc) {
            $groups[] = 'gc';
        }

        if (!$this->skipReports) {
            $groups[] = 'reports';
        }

        if (!$groups) {
            $this->stderr("No cron task groups selected. Remove --skip-gc and --skip-reports, or set --only.\n", Console::FG_RED);

            return null;
        }

        return $groups;
    }
}
