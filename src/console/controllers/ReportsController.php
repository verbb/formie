<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;

use yii\console\ExitCode;

/**
 * Manages Formie reports.
 */
class ReportsController extends Controller
{
    /**
     * Runs due scheduled reports.
     */
    public function actionRunScheduled(): int
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
}
