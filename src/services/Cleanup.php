<?php
namespace verbb\formie\services;

use verbb\formie\Formie;

use Craft;
use craft\console\Application as ConsoleApplication;
use craft\console\Controller;
use craft\helpers\App;
use craft\helpers\Console;

/**
 * Orchestrates Formie data retention and cleanup tasks.
 */
class Cleanup extends Service
{
    // Constants
    // =========================================================================

    public const TASK_INCOMPLETE_SUBMISSIONS = 'incomplete-submissions';
    public const TASK_DATA_RETENTION_SUBMISSIONS = 'data-retention-submissions';
    public const TASK_SENT_NOTIFICATIONS = 'sent-notifications';
    public const TASK_FILE_UPLOAD_ASSET_RETENTION = 'file-upload-asset-retention';
    public const TASK_STALE_PENDING_UPLOADS = 'stale-pending-uploads';
    public const TASK_REPORT_EXPORTS = 'report-exports';
    public const TASK_SUBMISSION_STATES = 'submission-states';
    public const TASK_DRAFT_STORAGE = 'draft-storage';


    // Public Methods
    // =========================================================================

    /**
     * @return string[]
     */
    public static function taskHandles(): array
    {
        return [
            self::TASK_INCOMPLETE_SUBMISSIONS,
            self::TASK_DATA_RETENTION_SUBMISSIONS,
            self::TASK_SENT_NOTIFICATIONS,
            self::TASK_FILE_UPLOAD_ASSET_RETENTION,
            self::TASK_STALE_PENDING_UPLOADS,
            self::TASK_REPORT_EXPORTS,
            self::TASK_SUBMISSION_STATES,
            self::TASK_DRAFT_STORAGE,
        ];
    }

    /**
     * Runs all cleanup tasks, or a filtered subset.
     *
     * @param Controller|ConsoleApplication|null $console
     * @param string[]|null $only Task handles from ::taskHandles(). Null runs all tasks.
     */
    public function runAll(Controller|ConsoleApplication|null $console = null, ?array $only = null): void
    {
        App::maxPowerCaptain();

        $tasks = $this->_tasks($console);
        $selected = $only ? array_values(array_intersect($only, array_keys($tasks))) : array_keys($tasks);

        foreach ($selected as $handle) {
            $task = $tasks[$handle];
            $this->_runTask($console, $task['label'], $task['run']);
        }
    }

    public function runTask(string $handle, Controller|ConsoleApplication|null $console = null): void
    {
        App::maxPowerCaptain();

        $tasks = $this->_tasks($console);

        if (!isset($tasks[$handle])) {
            throw new \InvalidArgumentException("Unknown cleanup task: $handle");
        }

        $task = $tasks[$handle];
        $this->_runTask($console, $task['label'], $task['run']);
    }


    // Private Methods
    // =========================================================================

    /**
     * @return array<string, array{label: string, run: callable(): void}>
     */
    private function _tasks(Controller|ConsoleApplication|null $console): array
    {
        return [
            self::TASK_INCOMPLETE_SUBMISSIONS => [
                'label' => 'purging incomplete Formie submissions',
                'run' => fn() => Formie::$plugin->getSubmissions()->pruneIncompleteSubmissions($console),
            ],
            self::TASK_DATA_RETENTION_SUBMISSIONS => [
                'label' => 'purging Formie submissions based on data retention',
                'run' => fn() => Formie::$plugin->getSubmissions()->pruneDataRetentionSubmissions($console),
            ],
            self::TASK_SENT_NOTIFICATIONS => [
                'label' => 'purging Formie sent notifications',
                'run' => fn() => Formie::$plugin->getSentNotifications()->pruneSentNotifications($console),
            ],
            self::TASK_FILE_UPLOAD_ASSET_RETENTION => [
                'label' => 'purging expired File Upload field assets',
                'run' => fn() => Formie::$plugin->getFileUploads()->pruneExpiredFieldAssets($console),
            ],
            self::TASK_STALE_PENDING_UPLOADS => [
                'label' => 'purging stale pending File Upload assets',
                'run' => function() use ($console): void {
                    $count = Formie::$plugin->getFileUploads()->purgeStalePendingUploads();

                    if ($console instanceof Controller && $count > 0) {
                        $console->stdout("Purged stale pending uploads: $count" . PHP_EOL, Console::FG_GREEN);
                    }
                },
            ],
            self::TASK_REPORT_EXPORTS => [
                'label' => 'purging expired Formie report exports',
                'run' => function() use ($console): void {
                    $count = Formie::$plugin->getReportExportFiles()->pruneExpired();

                    if ($console instanceof Controller && $count > 0) {
                        $console->stdout("Purged expired report exports: $count" . PHP_EOL, Console::FG_GREEN);
                    }
                },
            ],
            self::TASK_SUBMISSION_STATES => [
                'label' => 'pruning stale Formie submission states',
                'run' => function() use ($console): void {
                    $count = Formie::$plugin->getSubmissionDrafts()->pruneDraftStates();

                    if ($console instanceof Controller && $count > 0) {
                        $console->stdout("Pruned submission states: $count" . PHP_EOL, Console::FG_GREEN);
                    }
                },
            ],
            self::TASK_DRAFT_STORAGE => [
                'label' => 'pruning expired Formie draft storage',
                'run' => function() use ($console): void {
                    $count = Formie::$plugin->getSubmissionDrafts()->pruneExpiredDraftStorage();

                    if ($console instanceof Controller && $count > 0) {
                        $console->stdout("Pruned draft storage rows: $count" . PHP_EOL, Console::FG_GREEN);
                    }
                },
            ],
        ];
    }

    private function _runTask(Controller|ConsoleApplication|null $console, string $label, callable $run): void
    {
        $this->_stdout($console, "    > $label ... ");

        $run();

        $this->_stdout($console, "done\n", Console::FG_GREEN);
    }

    private function _stdout(Controller|ConsoleApplication|null $console, string $message, ...$format): void
    {
        if ($console === null) {
            return;
        }

        if ($console instanceof Controller) {
            $console->stdout($message, ...$format);

            return;
        }

        Console::stdout($message, ...$format);
    }
}
