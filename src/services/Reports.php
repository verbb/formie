<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\events\ReportEvent;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Report;
use verbb\formie\records\Report as ReportRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\Json;

use Throwable;

class Reports extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_REPORT = 'beforeSaveReport';
    public const EVENT_AFTER_SAVE_REPORT = 'afterSaveReport';
    public const EVENT_BEFORE_DELETE_REPORT = 'beforeDeleteReport';
    public const EVENT_BEFORE_APPLY_REPORT_DELETE = 'beforeApplyReportDelete';
    public const EVENT_AFTER_DELETE_REPORT = 'afterDeleteReport';

    /**
     * @deprecated Reports are stored in the database. This key is only read when upgrading.
     */
    public const CONFIG_REPORTS_KEY = 'formie.reports';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_reports = null;


    // Public Methods
    // =========================================================================

    public function getAllReports(): array
    {
        return $this->_reports()->all();
    }

    public function getReportById(int $id): ?Report
    {
        return $this->_reports()->firstWhere('id', $id);
    }

    public function getReportByHandle(string $handle): ?Report
    {
        return $this->_reports()->firstWhere('handle', $handle, true);
    }

    public function getReportByUid(string $uid): ?Report
    {
        return $this->_reports()->firstWhere('uid', $uid, true);
    }

    public function getAllReportHandles(): array
    {
        return (new Query())
            ->select(['handle'])
            ->from([Table::FORMIE_REPORTS])
            ->where(['dateDeleted' => null])
            ->column();
    }

    public function reorderReports(array $reportIds): bool
    {
        foreach ($reportIds as $reportOrder => $reportId) {
            Db::update(Table::FORMIE_REPORTS, ['sortOrder' => $reportOrder + 1], ['id' => $reportId]);
        }

        $this->_reports = null;

        return true;
    }

    public function saveReport(Report $report, bool $runValidation = true): bool
    {
        $isNewReport = !(bool)$report->id;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_REPORT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_REPORT, new ReportEvent([
                'report' => $report,
                'isNew' => $isNewReport,
            ]));
        }

        if ($runValidation && !$report->validate()) {
            Formie::info('Report not saved due to validation error.');

            return false;
        }

        if ($isNewReport) {
            $report->uid = StringHelper::UUID();

            $report->sortOrder = (new Query())
                ->from([Table::FORMIE_REPORTS])
                ->max('[[sortOrder]]') + 1;
        } elseif (!$report->uid) {
            $report->uid = Db::uidById(Table::FORMIE_REPORTS, $report->id);
        }

        $handleOwner = (new Query())
            ->select(['id', 'dateDeleted'])
            ->from([Table::FORMIE_REPORTS])
            ->where(['handle' => $report->handle])
            ->one();
        $isHandleTaken = $handleOwner && (!$report->id || (int)$report->id !== (int)$handleOwner['id']);

        if ($isHandleTaken && !$handleOwner['dateDeleted']) {
            $report->addError('handle', Craft::t('formie', 'That handle is already in use.'));

            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Handles stay unique across trashed reports and nothing restores those, so
            // someone recreating a deleted report takes over its handle.
            if ($isHandleTaken) {
                Db::delete(Table::FORMIE_REPORTS, ['id' => $handleOwner['id']]);
            }

            $reportRecord = $this->_getReportRecord((string)$report->uid, true);
            $reportRecord->name = $report->name;
            $reportRecord->handle = $report->handle;
            $reportRecord->sortOrder = $report->sortOrder ?? $reportRecord->sortOrder;
            $reportRecord->settings = $report->settings ? Json::encode($report->settings) : null;
            $reportRecord->uid = $report->uid;
            $reportRecord->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        $report->id = (int)$reportRecord->id;
        $this->_reports = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_REPORT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_REPORT, new ReportEvent([
                'report' => $this->getReportById($report->id),
                'isNew' => $isNewReport,
            ]));
        }

        return true;
    }

    /**
     * @deprecated Reports are stored in the database, so project config can't change them.
     */
    public function handleChangedReport(ConfigEvent $event): void
    {
    }

    public function deleteReportById(int $id): bool
    {
        $report = $this->getReportById($id);

        if (!$report) {
            return false;
        }

        return $this->deleteReport($report);
    }

    public function deleteReport(Report $report): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_REPORT)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_REPORT, new ReportEvent([
                'report' => $report,
            ]));
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_REPORT_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_REPORT_DELETE, new ReportEvent([
                'report' => $report,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::FORMIE_REPORTS, ['id' => $report->id])
                ->execute();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        $this->_reports = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_REPORT)) {
            $this->trigger(self::EVENT_AFTER_DELETE_REPORT, new ReportEvent([
                'report' => $report,
            ]));
        }

        return true;
    }

    /**
     * @deprecated Reports are stored in the database, so project config can't change them.
     */
    public function handleDeletedReport(ConfigEvent $event): void
    {
    }


    // Private Methods
    // =========================================================================

    private function _reports(): MemoizableArray
    {
        if (!isset($this->_reports)) {
            $reports = [];

            foreach ($this->_createReportsQuery()->all() as $result) {
                $settings = Json::decodeIfJson((string)$result['settings']);
                $result['settings'] = is_array($settings) ? $settings : null;
                $reports[] = new Report($result);
            }

            $this->_reports = new MemoizableArray($reports);
        }

        return $this->_reports;
    }

    private function _createReportsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'sortOrder',
                'settings',
                'dateDeleted',
                'uid',
            ])
            ->from([Table::FORMIE_REPORTS])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    private function _getReportRecord(string $uid, bool $withTrashed = false): ReportRecord
    {
        $query = $withTrashed ? ReportRecord::findWithTrashed() : ReportRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new ReportRecord();
    }
}
