<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\models\ReportExportFile;

use Craft;
use craft\queue\BaseJob as CraftBaseJob;
use Throwable;

class ExportReport extends CraftBaseJob
{
    // Properties
    // =========================================================================

    public ?int $exportFileId = null;


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        $mutex = Craft::$app->getMutex();
        $lock = 'formie-report-export:' . (int)$this->exportFileId;

        if (!$mutex->acquire($lock)) {
            throw new \RuntimeException('This report export is already running.');
        }

        try {
            $files = Formie::$plugin->getReportExportFiles();
            $exportFile = $files->getExportFileById((int)$this->exportFileId);

            // Expired/deleted exports must not be recreated by a delayed retry.
            if (!$exportFile || $exportFile->isExpired()) {
                return;
            }

            if (in_array($exportFile->status, [ReportExportFile::STATUS_READY, ReportExportFile::STATUS_CONSUMED], true)) {
                if (!$exportFile->filePath || !is_file($exportFile->filePath)) {
                    throw new \RuntimeException('The completed export file is unavailable. Request a new export.');
                }

                $this->setProgress($queue, 1);
                return;
            }

            $this->setProgress($queue, 0.05);

            try {
                $files->markRunning($exportFile);
                $result = Formie::$plugin->getReportExport()->runQueuedExport($exportFile, function(float $progress) use ($queue): void {
                    $this->setProgress($queue, 0.05 + 0.9 * max(0, min(1, $progress)));
                });
                $exportFile = $files->markReady($exportFile, $result['path'], $result['filename'], $result['mimeType']);
            } catch (Throwable $e) {
                try {
                    $files->markFailed($exportFile, $e->getMessage());
                } catch (Throwable $saveError) {
                    Formie::error('Unable to persist report export failure: ' . $saveError->getMessage());
                }

                Formie::error('Report export failed: ' . $e->getMessage());
                throw $e;
            }

            // Notification errors do not invalidate a finished download. A retry
            // must neither rebuild its contents nor rotate a published token.
            if ($exportFile->source === ReportExportFile::SOURCE_INTERACTIVE) {
                try {
                    if ($exportFile->notifyEmail && !Formie::$plugin->getReportExport()->sendReadyNotification($exportFile)) {
                        Formie::error('Unable to send the ready notification for report export ' . $exportFile->id . '.');
                    }
                } catch (Throwable $e) {
                    Formie::error('Report export ready notification failed: ' . $e->getMessage());
                }
            }

            $this->setProgress($queue, 1);
        } finally {
            $mutex->release($lock);
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): ?string
    {
        return Craft::t('formie', 'Exporting report');
    }
}
