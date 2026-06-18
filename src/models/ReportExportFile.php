<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;

use DateTime;

class ReportExportFile extends Model
{
    // Constants
    // =========================================================================

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CONSUMED = 'consumed';

    public const SOURCE_INTERACTIVE = 'interactive';
    public const SOURCE_SCHEDULED = 'scheduled';


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $reportId = null;
    public ?int $userId = null;
    public ?int $scheduledReportId = null;
    public string $source = self::SOURCE_INTERACTIVE;
    public string $status = self::STATUS_PENDING;
    public string $format = 'csv';
    public ?array $context = null;
    public ?string $filename = null;
    public ?string $filePath = null;
    public ?int $fileSize = null;
    /** Plaintext token — memory only until persisted as a hash at markReady. */
    public ?string $downloadToken = null;
    public ?string $downloadTokenHash = null;
    public ?string $downloadUrl = null;
    public ?string $notifyEmail = null;
    public ?string $error = null;
    public ?DateTime $dateExpires = null;
    public ?DateTime $dateDownloaded = null;
    public ?string $uid = null;


    // Public Methods
    // =========================================================================

    public function getDownloadUrl(?int $siteId = null): ?string
    {
        if ($this->downloadUrl) {
            return self::normalizeDownloadUrl($this->downloadUrl);
        }

        if (!$this->uid || !$this->downloadToken || $this->status !== self::STATUS_READY) {
            return null;
        }

        return self::buildDownloadUrl($this->uid, $this->downloadToken, $siteId);
    }

    public function getInteractiveDownloadUrl(): ?string
    {
        if ($this->source !== self::SOURCE_INTERACTIVE || !$this->uid || !$this->isDownloadable()) {
            return null;
        }

        return UrlHelper::cpUrl('formie/reports/download-queued-export/' . $this->uid);
    }

    public static function buildDownloadUrl(string $uid, string $downloadToken, ?int $siteId = null): string
    {
        return UrlHelper::actionUrl('formie/reports/download-export', [
            'uid' => $uid,
            'downloadToken' => $downloadToken,
        ], null, $siteId);
    }

    /**
     * Craft 5.9+ validates the `token` query param before routing; rewrite legacy export links.
     */
    public static function normalizeDownloadUrl(string $url): string
    {
        return (string)preg_replace('/([?&])token=/', '$1downloadToken=', $url, 1);
    }

    public function isExpired(): bool
    {
        if (!$this->dateExpires) {
            return false;
        }

        return $this->dateExpires->getTimestamp() <= time();
    }

    public function isDownloadable(): bool
    {
        return in_array($this->status, [self::STATUS_READY, self::STATUS_CONSUMED], true) && !$this->isExpired();
    }

    public function isTokenDownloadable(): bool
    {
        return $this->status === self::STATUS_READY
            && !$this->isExpired()
            && $this->downloadTokenHash !== null
            && $this->downloadTokenHash !== '';
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        return [
            [['reportId', 'format'], 'required'],
            [['reportId', 'userId', 'scheduledReportId', 'fileSize'], 'integer'],
            [['source', 'status', 'format'], 'string'],
            [['filename', 'filePath', 'notifyEmail', 'downloadToken', 'downloadTokenHash', 'downloadUrl'], 'string'],
            [['context'], 'safe'],
            [['dateExpires', 'dateDownloaded'], 'safe'],
            [['error'], 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'reportId' => Craft::t('formie', 'Report'),
            'format' => Craft::t('formie', 'Format'),
            'status' => Craft::t('formie', 'Status'),
        ];
    }
}
