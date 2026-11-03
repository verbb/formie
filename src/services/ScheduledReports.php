<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\events\ScheduledReportEvent;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\ScheduledReport;
use verbb\formie\models\ScheduledReportDelivery;
use verbb\formie\records\ScheduledReport as ScheduledReportRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;

use DateTime;
use Throwable;

class ScheduledReports extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_SCHEDULED_REPORT = 'beforeSaveScheduledReport';
    public const EVENT_AFTER_SAVE_SCHEDULED_REPORT = 'afterSaveScheduledReport';
    public const EVENT_BEFORE_DELETE_SCHEDULED_REPORT = 'beforeDeleteScheduledReport';
    public const EVENT_BEFORE_APPLY_SCHEDULED_REPORT_DELETE = 'beforeApplyScheduledReportDelete';
    public const EVENT_AFTER_DELETE_SCHEDULED_REPORT = 'afterDeleteScheduledReport';

    /**
     * @deprecated Scheduled reports are stored in the database. This key is only read when upgrading.
     */
    public const CONFIG_SCHEDULED_REPORTS_KEY = 'formie.scheduledReports';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_scheduledReports = null;


    // Public Methods
    // =========================================================================

    public function getAllScheduledReports(): array
    {
        return $this->_scheduledReports()->all();
    }

    public function getScheduledReportById(int $id): ?ScheduledReport
    {
        return $this->_scheduledReports()->firstWhere('id', $id);
    }

    public function getFreshScheduledReportById(int $id): ?ScheduledReport
    {
        $this->_scheduledReports = null;

        return Craft::$app->getDb()->useMaster(fn() => $this->getScheduledReportById($id));
    }

    public function getScheduledReportByUid(string $uid): ?ScheduledReport
    {
        return $this->_scheduledReports()->firstWhere('uid', $uid, true);
    }

    public function getEnabledScheduledReports(): array
    {
        return array_values(array_filter(
            $this->getAllScheduledReports(),
            fn(ScheduledReport $scheduledReport) => $scheduledReport->enabled,
        ));
    }

    public function saveScheduledReport(ScheduledReport $scheduledReport, bool $runValidation = true): bool
    {
        $isNew = !(bool)$scheduledReport->id;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SCHEDULED_REPORT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SCHEDULED_REPORT, new ScheduledReportEvent([
                'scheduledReport' => $scheduledReport,
                'isNew' => $isNew,
            ]));
        }

        if ($runValidation && !$scheduledReport->validate()) {
            Formie::info('Scheduled report not saved due to validation error.');

            return false;
        }

        if ($isNew) {
            $scheduledReport->uid = StringHelper::UUID();
        } elseif (!$scheduledReport->uid) {
            $scheduledReport->uid = Db::uidById(Table::FORMIE_SCHEDULED_REPORTS, $scheduledReport->id);
        }

        $delivery = $scheduledReport->getDeliveryModel();
        $report = Formie::$plugin->getReports()->getReportById((int)$scheduledReport->reportId);

        if (!$report) {
            $scheduledReport->addError('reportId', Craft::t('formie', 'Scheduled report references a missing report.'));

            return false;
        }

        $delivery->reportUid = $report->uid;
        $scheduledReport->setDeliveryModel($delivery);

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $record = $this->_getScheduledReportRecord((string)$scheduledReport->uid, true);
            $record->reportId = $report->id;
            $record->name = $scheduledReport->name;
            $record->enabled = $scheduledReport->enabled;
            $record->delivery = $scheduledReport->delivery ? Json::encode($scheduledReport->delivery) : null;
            $record->uid = $scheduledReport->uid;
            $record->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        $scheduledReport->id = (int)$record->id;
        $this->_scheduledReports = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SCHEDULED_REPORT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SCHEDULED_REPORT, new ScheduledReportEvent([
                'scheduledReport' => $this->getScheduledReportById($scheduledReport->id),
                'isNew' => $isNew,
            ]));
        }

        return true;
    }

    /**
     * @deprecated Scheduled reports are stored in the database, so project config can't change them.
     */
    public function handleChangedScheduledReport(ConfigEvent $event): void
    {
    }

    public function deleteScheduledReport(ScheduledReport $scheduledReport): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_SCHEDULED_REPORT)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_SCHEDULED_REPORT, new ScheduledReportEvent([
                'scheduledReport' => $scheduledReport,
            ]));
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_SCHEDULED_REPORT_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_SCHEDULED_REPORT_DELETE, new ScheduledReportEvent([
                'scheduledReport' => $scheduledReport,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::FORMIE_SCHEDULED_REPORTS, ['id' => $scheduledReport->id])
                ->execute();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        $this->_scheduledReports = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SCHEDULED_REPORT)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SCHEDULED_REPORT, new ScheduledReportEvent([
                'scheduledReport' => $scheduledReport,
            ]));
        }

        return true;
    }

    /**
     * @deprecated Scheduled reports are stored in the database, so project config can't change them.
     */
    public function handleDeletedScheduledReport(ConfigEvent $event): void
    {
    }

    public function markSent(ScheduledReport $scheduledReport, ?DateTime $sentAt = null): void
    {
        $sentAt ??= new DateTime();

        Db::update(Table::FORMIE_SCHEDULED_REPORTS, [
            'lastSentAt' => Db::prepareDateForDb($sentAt),
            'dateUpdated' => Db::prepareDateForDb(new DateTime()),
        ], [
            'id' => $scheduledReport->id,
        ], [], false);

        $scheduledReport->lastSentAt = $sentAt;
        $this->_scheduledReports = null;
    }

    public function getDueScheduledReports(?DateTime $now = null): array
    {
        $now ??= new DateTime();
        $due = [];

        foreach ($this->getEnabledScheduledReports() as $scheduledReport) {
            if ($this->isDue($scheduledReport, $now)) {
                $due[] = $scheduledReport;
            }
        }

        return $due;
    }

    public function getScheduledReportsForReport(int $reportId): array
    {
        return array_values(array_filter(
            $this->getAllScheduledReports(),
            fn(ScheduledReport $scheduledReport) => (int)$scheduledReport->reportId === $reportId,
        ));
    }

    public function deleteScheduledReportById(int $id): bool
    {
        $scheduledReport = $this->getScheduledReportById($id);

        if (!$scheduledReport) {
            return false;
        }

        return $this->deleteScheduledReport($scheduledReport);
    }

    public function applyDeliveryPayload(ScheduledReport $scheduledReport, array $payload): void
    {
        $delivery = ScheduledReportDelivery::fromArray($payload['delivery'] ?? []);
        $scheduledReport->name = trim((string)($payload['name'] ?? $scheduledReport->name));
        $scheduledReport->enabled = (bool)($payload['enabled'] ?? $scheduledReport->enabled);
        $scheduledReport->reportId = (int)($payload['reportId'] ?? $scheduledReport->reportId);
        $scheduledReport->setDeliveryModel($delivery);
    }

    public function isDue(ScheduledReport $scheduledReport, ?DateTime $now = null): bool
    {
        $now ??= new DateTime();
        $delivery = $scheduledReport->getDeliveryModel();

        if ($delivery->startAt) {
            $startAt = DateTimeHelper::toDateTime($delivery->startAt);

            if ($startAt && $now < $startAt) {
                return false;
            }
        }

        if ($delivery->endAt) {
            $endAt = DateTimeHelper::toDateTime($delivery->endAt);

            if ($endAt && preg_match('/^\d{4}-\d{2}-\d{2}$/', $delivery->endAt)) {
                $endAt->setTime(23, 59, 59, 999999);
            }

            if ($endAt && $now > $endAt) {
                return false;
            }
        }

        $lastSentAt = $scheduledReport->lastSentAt;
        $frequency = $delivery->frequency;

        if ($frequency === 'daily') {
            if (!$lastSentAt) {
                return (int)$now->format('G') >= $delivery->hour;
            }

            $nextRun = (clone $lastSentAt)->modify('+1 day')->setTime($delivery->hour, 0);

            return $now >= $nextRun;
        }

        if ($frequency === 'weekly') {
            if ((int)$now->format('w') !== $delivery->weekday) {
                return false;
            }

            if ((int)$now->format('G') < $delivery->hour) {
                return false;
            }

            if (!$lastSentAt) {
                return true;
            }

            return $lastSentAt->format('Y-W') !== $now->format('Y-W');
        }

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _scheduledReports(): MemoizableArray
    {
        if (!isset($this->_scheduledReports)) {
            $scheduledReports = [];

            foreach ($this->_createScheduledReportsQuery()->all() as $result) {
                $delivery = Json::decodeIfJson((string)$result['delivery']);
                $result['delivery'] = is_array($delivery) ? $delivery : null;
                $scheduledReports[] = new ScheduledReport($result);
            }

            $this->_scheduledReports = new MemoizableArray($scheduledReports);
        }

        return $this->_scheduledReports;
    }

    private function _createScheduledReportsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'reportId',
                'name',
                'enabled',
                'lastSentAt',
                'delivery',
                'dateDeleted',
                'uid',
            ])
            ->from([Table::FORMIE_SCHEDULED_REPORTS])
            ->where(['dateDeleted' => null])
            ->orderBy(['name' => SORT_ASC]);
    }

    private function _getScheduledReportRecord(string $uid, bool $withTrashed = false): ScheduledReportRecord
    {
        $query = $withTrashed ? ScheduledReportRecord::findWithTrashed() : ScheduledReportRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new ScheduledReportRecord();
    }
}
