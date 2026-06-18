<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\models\Report;
use verbb\formie\models\ReportExportFile;
use verbb\formie\records\ReportExportFile as ReportExportFileRecord;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\Db;
use craft\helpers\StringHelper;

use DateInterval;
use DateTime;
use Throwable;

class ReportExportFiles extends Component
{
    // Public Methods
    // =========================================================================

    public function createPending(
        Report $report,
        string $format,
        array $context,
        string $source = ReportExportFile::SOURCE_INTERACTIVE,
        ?User $user = null,
        ?int $scheduledReportId = null,
        ?string $notifyEmail = null,
        ?DateTime $dateExpires = null,
    ): ReportExportFile {
        $dateExpires ??= $this->_defaultExpiry($source);

        $exportFile = new ReportExportFile([
            'reportId' => (int)$report->id,
            'userId' => $user?->id ? (int)$user->id : null,
            'scheduledReportId' => $scheduledReportId,
            'source' => $source,
            'status' => ReportExportFile::STATUS_PENDING,
            'format' => strtolower(trim($format)) ?: 'csv',
            'context' => $context,
            'notifyEmail' => $notifyEmail,
            'dateExpires' => $dateExpires,
        ]);

        if (!$this->saveExportFile($exportFile)) {
            throw new \RuntimeException(Craft::t('formie', 'Unable to create report export.'));
        }

        return $exportFile;
    }

    public function saveExportFile(ReportExportFile $exportFile): bool
    {
        if (!$exportFile->validate()) {
            return false;
        }

        $record = $exportFile->id
            ? ReportExportFileRecord::findOne($exportFile->id)
            : new ReportExportFileRecord();

        if (!$record) {
            return false;
        }

        $record->reportId = (int)$exportFile->reportId;
        $record->userId = $exportFile->userId;
        $record->scheduledReportId = $exportFile->scheduledReportId;
        $record->source = $exportFile->source;
        $record->status = $exportFile->status;
        $record->format = $exportFile->format;
        $record->context = $exportFile->context ? json_encode($exportFile->context, JSON_UNESCAPED_UNICODE) : null;
        $record->filename = $exportFile->filename;
        $record->filePath = $exportFile->filePath;
        $record->fileSize = $exportFile->fileSize;
        $record->downloadUrl = $exportFile->downloadUrl;
        $record->notifyEmail = $exportFile->notifyEmail;
        $record->error = $exportFile->error;
        $record->dateExpires = Db::prepareDateForDb($exportFile->dateExpires);
        $record->dateDownloaded = Db::prepareDateForDb($exportFile->dateDownloaded);

        if ($exportFile->downloadToken !== null && $exportFile->downloadToken !== '') {
            $record->downloadTokenHash = $this->hashDownloadToken($exportFile->downloadToken);
        } elseif ($exportFile->downloadTokenHash !== null) {
            $record->downloadTokenHash = $exportFile->downloadTokenHash;
        }

        if (!$record->save()) {
            $exportFile->addErrors($record->getErrors());

            return false;
        }

        if (!$exportFile->id) {
            $exportFile->id = (int)$record->id;
            $exportFile->uid = $record->uid;
        }

        return true;
    }

    public function hashDownloadToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function verifyDownloadToken(string $token, ?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            return false;
        }

        return hash_equals($hash, $this->hashDownloadToken($token));
    }

    public function getExportFileById(int $id): ?ReportExportFile
    {
        $record = ReportExportFileRecord::findOne($id);

        return $record ? $this->_hydrate($record) : null;
    }

    public function getExportFileByUid(string $uid): ?ReportExportFile
    {
        $record = ReportExportFileRecord::find()
            ->where(['uid' => $uid])
            ->one();

        return $record ? $this->_hydrate($record) : null;
    }

    public function getExportFileByToken(string $uid, string $token): ?ReportExportFile
    {
        $record = ReportExportFileRecord::find()
            ->where(['uid' => $uid])
            ->one();

        if (!$record || !$this->verifyDownloadToken($token, $record->downloadTokenHash)) {
            return null;
        }

        return $this->_hydrate($record);
    }

    public function markRunning(ReportExportFile $exportFile): void
    {
        $exportFile->status = ReportExportFile::STATUS_RUNNING;
        $this->saveExportFile($exportFile);
    }

    public function markReady(ReportExportFile $exportFile, string $path, string $filename, string $mimeType): ReportExportFile
    {
        $exportFile->status = ReportExportFile::STATUS_READY;
        $exportFile->filePath = $path;
        $exportFile->filename = $filename;
        $exportFile->fileSize = is_file($path) ? (int)filesize($path) : null;
        $exportFile->error = null;
        $exportFile->downloadToken = StringHelper::UUID();
        $exportFile->downloadUrl = $exportFile->getDownloadUrl();
        $this->saveExportFile($exportFile);

        return $exportFile;
    }

    public function markFailed(ReportExportFile $exportFile, string $error): void
    {
        $exportFile->status = ReportExportFile::STATUS_FAILED;
        $exportFile->error = $error;
        $this->saveExportFile($exportFile);
    }

    public function markConsumed(ReportExportFile $exportFile): void
    {
        // Single-use applies to signed links only — keep the export ready for CP downloads.
        $exportFile->dateDownloaded = new DateTime();
        $exportFile->downloadTokenHash = null;
        $exportFile->downloadToken = null;
        $exportFile->downloadUrl = null;
        $this->saveExportFile($exportFile);
    }

    public function shouldSingleUseDownload(): bool
    {
        return (bool)Formie::$plugin->getSettings()->reportExportSingleUseDownload;
    }

    public function deleteExportFile(ReportExportFile $exportFile): bool
    {
        if ($exportFile->filePath && is_file($exportFile->filePath)) {
            @unlink($exportFile->filePath);
        }

        if (!$exportFile->id) {
            return true;
        }

        return (bool)ReportExportFileRecord::deleteAll(['id' => $exportFile->id]);
    }

    public function pruneExpired(): int
    {
        $records = ReportExportFileRecord::find()
            ->where(['<=', 'dateExpires', Db::prepareDateForDb(new DateTime())])
            ->all();

        $count = 0;

        foreach ($records as $record) {
            $exportFile = $this->_hydrate($record);

            if ($this->deleteExportFile($exportFile)) {
                $count++;
            }
        }

        return $count;
    }

    public function getStatusPayload(ReportExportFile $exportFile): array
    {
        $payload = [
            'uid' => $exportFile->uid,
            'status' => $exportFile->status,
            'format' => $exportFile->format,
            'filename' => $exportFile->filename,
            'fileSize' => $exportFile->fileSize,
            'error' => $exportFile->error,
            'dateExpires' => $exportFile->dateExpires?->format('Y-m-d H:i:s'),
        ];

        if ($exportFile->isDownloadable()) {
            $payload['downloadUrl'] = $exportFile->source === ReportExportFile::SOURCE_INTERACTIVE
                ? $exportFile->getInteractiveDownloadUrl()
                : $exportFile->getDownloadUrl();
        }

        return $payload;
    }


    // Private Methods
    // =========================================================================

    private function _hydrate(ReportExportFileRecord $record): ReportExportFile
    {
        $context = null;

        if ($record->context) {
            try {
                $decoded = json_decode($record->context, true);
                $context = is_array($decoded) ? $decoded : null;
            } catch (Throwable) {
                $context = null;
            }
        }

        return new ReportExportFile([
            'id' => (int)$record->id,
            'reportId' => (int)$record->reportId,
            'userId' => $record->userId ? (int)$record->userId : null,
            'scheduledReportId' => $record->scheduledReportId ? (int)$record->scheduledReportId : null,
            'source' => (string)$record->source,
            'status' => (string)$record->status,
            'format' => (string)$record->format,
            'context' => $context,
            'filename' => $record->filename,
            'filePath' => $record->filePath,
            'fileSize' => $record->fileSize ? (int)$record->fileSize : null,
            'downloadTokenHash' => $record->downloadTokenHash,
            'downloadUrl' => $record->downloadUrl,
            'notifyEmail' => $record->notifyEmail,
            'error' => $record->error,
            'dateExpires' => $record->dateExpires ? new DateTime($record->dateExpires) : null,
            'dateDownloaded' => $record->dateDownloaded ? new DateTime($record->dateDownloaded) : null,
            'uid' => $record->uid,
        ]);
    }

    private function _defaultExpiry(string $source): DateTime
    {
        $settings = Formie::$plugin->getSettings();
        $expires = new DateTime();

        if ($source === ReportExportFile::SOURCE_SCHEDULED) {
            $hours = max(1, (int)$settings->reportScheduledExportExpiryHours);
        } else {
            $hours = max(1, (int)$settings->reportInteractiveExportExpiryHours);
        }

        $expires->add(new DateInterval('PT' . $hours . 'H'));

        return $expires;
    }
}
