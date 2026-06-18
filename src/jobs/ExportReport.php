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
        $exportFile = Formie::$plugin->getReportExportFiles()->getExportFileById((int)$this->exportFileId);

        if (!$exportFile) {
            throw new \RuntimeException('Unable to find report export file: ' . $this->exportFileId . '.');
        }

        $this->setProgress($queue, 0.1);
        Formie::$plugin->getReportExportFiles()->markRunning($exportFile);

        try {
            $result = Formie::$plugin->getReportExport()->runQueuedExport($exportFile, function(float $progress) use ($queue): void {
                $this->setProgress($queue, max(0.1, min(0.95, $progress)));
            });

            $exportFile = Formie::$plugin->getReportExportFiles()->markReady(
                $exportFile,
                $result['path'],
                $result['filename'],
                $result['mimeType'],
            );

            if ($exportFile->source === ReportExportFile::SOURCE_INTERACTIVE) {
                Formie::$plugin->getReportExport()->sendReadyNotification($exportFile);
            }
        } catch (Throwable $e) {
            Formie::$plugin->getReportExportFiles()->markFailed($exportFile, $e->getMessage());
            Formie::error('Report export failed: {message}', [
                'message' => $e->getMessage(),
                'exportFileId' => $this->exportFileId,
            ]);

            throw $e;
        }

        $this->setProgress($queue, 1);
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): ?string
    {
        return Craft::t('formie', 'Exporting report');
    }
}
