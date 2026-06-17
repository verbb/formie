<?php
namespace verbb\formie\services;

use verbb\formie\events\ReportEvent;
use verbb\formie\Formie;
use verbb\formie\helpers\DbSchema;
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

use DateTime;
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

    public function reorderReports(array $reportIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $uidsByIds = Db::uidsByIds(Table::FORMIE_REPORTS, $reportIds);

        foreach ($reportIds as $reportOrder => $reportId) {
            if (!empty($uidsByIds[$reportId])) {
                $reportUid = $uidsByIds[$reportId];
                $projectConfig->set(self::CONFIG_REPORTS_KEY . '.' . $reportUid . '.sortOrder', $reportOrder + 1);
            }
        }

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

        $existingReport = $this->getReportByHandle($report->handle);

        if ($existingReport && (!$report->id || $report->id != $existingReport->id)) {
            $report->addError('handle', Craft::t('formie', 'That handle is already in use'));

            return false;
        }

        $configPath = self::CONFIG_REPORTS_KEY . '.' . $report->uid;
        Craft::$app->getProjectConfig()->set($configPath, $report->getConfig(), "Save the “{$report->handle}” report");

        if ($isNewReport) {
            $report->id = Db::idByUid(Table::FORMIE_REPORTS, $report->uid);
        }

        return true;
    }

    public function handleChangedReport(ConfigEvent $event): void
    {
        $reportUid = $event->tokenMatches[0];
        $data = $event->newValue;

        if (!$data) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $reportRecord = $this->_getReportRecord($reportUid, true);
            $isNewReport = $reportRecord->getIsNewRecord();

            $reportRecord->name = $data['name'];
            $reportRecord->handle = $data['handle'];
            $reportRecord->sortOrder = $data['sortOrder'];
            $reportRecord->uid = $reportUid;

            if ($reportRecord->dateDeleted) {
                $reportRecord->restore();
            } else {
                $reportRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_reports = null;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_REPORT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_REPORT, new ReportEvent([
                'report' => $this->getReportById($reportRecord->id),
                'isNew' => $isNewReport,
            ]));
        }
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

        Craft::$app->getProjectConfig()->remove(self::CONFIG_REPORTS_KEY . '.' . $report->uid, "Delete report “{$report->handle}”");

        return true;
    }

    public function handleDeletedReport(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $reportRecord = $this->_getReportRecord($uid);

        if ($reportRecord->getIsNewRecord()) {
            return;
        }

        $report = $this->getReportById($reportRecord->id);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_REPORT_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_REPORT_DELETE, new ReportEvent([
                'report' => $report,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::FORMIE_REPORTS, ['id' => $reportRecord->id])
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
    }


    // Private Methods
    // =========================================================================

    private function _reports(): MemoizableArray
    {
        if (!isset($this->_reports)) {
            if (!DbSchema::tableExists(Table::FORMIE_REPORTS)) {
                return $this->_reports = new MemoizableArray([]);
            }

            $reports = [];

            foreach ($this->_createReportsQuery()->all() as $result) {
                $report = new Report($result);
                $reportConfig = Craft::$app->getProjectConfig()->get(self::CONFIG_REPORTS_KEY . '.' . $result['uid']);

                if (is_array($reportConfig['settings'] ?? null)) {
                    $report->settings = $reportConfig['settings'];
                }

                $reports[] = $report;
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
