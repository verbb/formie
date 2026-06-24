<?php
namespace verbb\formie\migrations\plugins;

use verbb\formie\models\RichText;

use Craft;

use Throwable;

use yii\base\Component;

abstract class BasePluginMigrator extends Component
{
    // Properties
    // =========================================================================

    public int $submissionBatchSize = 100;
    public int $submissionOffset = 0;
    public ?int $submissionLimit = null;
    public bool $skipSubmissions = false;
    public bool $submissionsOnly = false;
    public bool $verboseSubmissionLogs = false;

    protected MigrationResult $result;


    // Public Methods
    // =========================================================================

    public function run(): MigrationResult
    {
        $this->result = new MigrationResult();

        try {
            if (!$this->safeUp()) {
                $this->result->ok = false;
            }
        } catch (Throwable $e) {
            $this->result->ok = false;
            $this->error($e->getMessage());
            $this->error($this->getExceptionTraceAsString($e), 1);
            Craft::error($e->getMessage(), __METHOD__);
        }

        return $this->result;
    }

    abstract public function safeUp(): bool;


    // Protected Methods
    // =========================================================================

    protected function addLine(MigrationLine $line): void
    {
        $this->ensureResultInitialized();
        $this->result->addLine($line);
    }

    protected function setStat(string $key, mixed $value): void
    {
        $this->ensureResultInitialized();
        $this->result->setStat($key, $value);
    }

    protected function incrementStat(string $key, int $value = 1): void
    {
        $this->ensureResultInitialized();
        $this->result->incrementStat($key, $value);
    }

    protected function info(string $message, int $depth = 0, array $context = []): void
    {
        $line = Line::info($this->normalizeMessage($message), $this->normalizeDepth($message, $depth), $context);
        $this->addLine($line);
        $this->collectStats($line->message, $line->level);
    }

    protected function success(string $message, int $depth = 0, array $context = []): void
    {
        $line = Line::success($this->normalizeMessage($message), $this->normalizeDepth($message, $depth), $context);
        $this->addLine($line);
        $this->collectStats($line->message, $line->level);
    }

    protected function warning(string $message, int $depth = 0, array $context = []): void
    {
        $line = Line::warning($this->normalizeMessage($message), $this->normalizeDepth($message, $depth), $context);
        $this->addLine($line);
        $this->collectStats($line->message, $line->level);
    }

    protected function error(string $message, int $depth = 0, array $context = []): void
    {
        $line = Line::error($this->normalizeMessage($message), $this->normalizeDepth($message, $depth), $context);
        $this->addLine($line);
        $this->collectStats($line->message, $line->level);
    }

    protected function collectStats(string $message, string $level): void
    {
        if (str_starts_with($message, 'Form: Preparing to migrate form')) {
            $this->incrementStat('formsAttempted');
        }
        if (str_contains($message, ' migrated.') && str_contains($message, 'Form')) {
            $this->incrementStat('formsMigrated');
        }
        if (str_starts_with($message, 'Entries: Preparing to migrate ')) {
            if (preg_match('/Entries: Preparing to migrate (\d+) entries/', $message, $matches)) {
                $this->incrementStat('submissionsAttempted', (int)$matches[1]);
            }
        }
        if (preg_match('/Migrated .* submission/u', $message)) {
            $this->incrementStat('submissionsMigrated');
        }
        if (str_starts_with($message, 'Notifications: Preparing to migrate notification')) {
            $this->incrementStat('notificationsAttempted');
        }
        if (str_contains($message, 'Migrated notification')) {
            $this->incrementStat('notificationsMigrated');
        }
        if ($level === 'error') {
            $this->incrementStat('errors');
            $this->result->ok = false;
        }
    }

    protected function getExceptionTraceAsString(Throwable $e): string
    {
        return $e->getTraceAsString();
    }

    /**
     * @param callable(): object $createQuery Query factory supporting count(), offset(), limit(), and all().
     * @param callable(mixed): void $migrateEntry
     */
    protected function migrateSubmissionBatches(callable $createQuery, callable $migrateEntry): void
    {
        $totalAvailable = (int)$createQuery()->count();
        $offset = max(0, $this->submissionOffset);
        $batchSize = max(1, $this->submissionBatchSize);

        if ($this->submissionLimit !== null) {
            $totalToMigrate = min($this->submissionLimit, max(0, $totalAvailable - $offset));
        } else {
            $totalToMigrate = max(0, $totalAvailable - $offset);
        }

        $this->info("Entries: Preparing to migrate $totalToMigrate entries to submissions.");

        if ($totalAvailable > $totalToMigrate + $offset) {
            $this->info("    > $totalAvailable total entries available; processing from offset $offset.");
        }

        if (!$totalToMigrate) {
            $this->warning('    > No entries to migrate.');

            return;
        }

        $processed = 0;
        $currentOffset = $offset;
        $batchNumber = 1;

        while ($processed < $totalToMigrate) {
            $limit = min($batchSize, $totalToMigrate - $processed);
            $entries = $createQuery()->offset($currentOffset)->limit($limit)->all();

            if (!$entries) {
                break;
            }

            $batchCount = count($entries);
            $this->info("    > Processing batch $batchNumber ($batchCount entries, offset $currentOffset).");

            foreach ($entries as $entry) {
                $migrateEntry($entry);
            }

            $processed += $batchCount;
            $currentOffset += $batchCount;
            $batchNumber++;

            unset($entries);
            gc_collect_cycles();
        }

        $this->success("    > All entries completed.");
    }

    protected function logSubmissionMigrated(int|string $sourceId, int|string $targetId): void
    {
        if ($this->verboseSubmissionLogs) {
            $this->success("    > Migrated Freeform submission “{$sourceId}” to Formie submission “{$targetId}”.");
        } else {
            $this->incrementStat('submissionsMigrated');
        }
    }

    protected function toRichText(mixed $value): RichText
    {
        return new RichText($value);
    }


    // Private Methods
    // =========================================================================

    private function ensureResultInitialized(): void
    {
        if (!isset($this->result)) {
            $this->result = new MigrationResult();
        }
    }

    private function normalizeMessage(string $message): string
    {
        $message = trim(strip_tags($message));
        $message = preg_replace('/^\s*>\s*/', '', $message) ?? $message;
        $message = preg_replace('/^\s+/', '', $message) ?? $message;

        return $message;
    }

    private function normalizeDepth(string $message, int $depth): int
    {
        if ($depth > 0 || preg_match('/^\s*>\s*/', $message)) {
            return 1;
        }

        return 0;
    }
}

