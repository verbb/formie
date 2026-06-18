<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\ReportDateBoundHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;

use DateTime;

class ReportQuery extends Component
{
    // Public Methods
    // =========================================================================

    public function buildSubmissionQuery(Report $report, ?User $user = null, array $viewer = []): ElementQueryInterface
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $filters = $this->resolveFilters($report, $viewer);

        $formIds = $this->resolveFormIds($filters['formIds'] ?? '*', $user);
        $query = Submission::find()
            ->formId($formIds ?: false)
            ->status(null);

        $this->applyStateFilters($query, $filters);
        $this->applyStatusFilters($query, $filters);
        $this->applyDateFilters($query, $filters);

        return $query;
    }

    public function getSummaryCounts(Report $report, ?User $user = null, ?DateTime $since = null, array $viewer = []): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $filters = $this->resolveFilters($report, $viewer);

        if ($since) {
            $existingStart = DateTimeHelper::toDateTime($filters['startDate'] ?? null);

            if (!$existingStart || $since > $existingStart) {
                $filters['startDate'] = $since->format('Y-m-d H:i:s');
            }
        }

        $formIds = $this->resolveFormIds($filters['formIds'] ?? '*', $user);

        $summary = [
            'total' => 0,
            'complete' => 0,
            'incomplete' => 0,
            'spam' => 0,
            'forms' => [],
        ];

        if (!$formIds) {
            return $summary;
        }

        $forms = Form::find()
            ->id($formIds)
            ->status(null)
            ->indexBy('id')
            ->all();

        $countsByFormId = $this->_getSummaryCountsByForm($formIds, $filters);

        foreach ($formIds as $formId) {
            $form = $forms[$formId] ?? null;

            if (!$form || !$this->canViewFormSubmissions($user, $form)) {
                continue;
            }

            $counts = $countsByFormId[$formId] ?? [
                'complete' => 0,
                'incomplete' => 0,
                'spam' => 0,
            ];

            $formSummary = [
                'formId' => (int)$formId,
                'formTitle' => $form->title,
                'formHandle' => $form->handle,
                'complete' => $counts['complete'],
                'incomplete' => $counts['incomplete'],
                'spam' => $counts['spam'],
            ];

            $formSummary['total'] = $formSummary['complete'] + $formSummary['incomplete'] + $formSummary['spam'];
            $summary['forms'][] = $formSummary;
            $summary['complete'] += $formSummary['complete'];
            $summary['incomplete'] += $formSummary['incomplete'];
            $summary['spam'] += $formSummary['spam'];
            $summary['total'] += $formSummary['total'];
        }

        return $summary;
    }

    public function getChartData(Report $report, ?User $user = null, array $viewer = []): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $filters = $this->resolveFilters($report, $viewer);
        $formIds = $this->resolveFormIds($filters['formIds'] ?? '*', $user);

        if (!$formIds) {
            return [
                'range' => null,
                'rows' => [],
            ];
        }

        $query = (new Query())
            ->select([
                'bucket' => new \yii\db\Expression('DATE([[elements.dateCreated]])'),
                'complete' => new \yii\db\Expression(
                    'SUM(CASE WHEN [[submissions.isIncomplete]] = 0 AND [[submissions.isSpam]] = 0 THEN 1 ELSE 0 END)',
                ),
                'incomplete' => new \yii\db\Expression(
                    'SUM(CASE WHEN [[submissions.isIncomplete]] = 1 AND [[submissions.isSpam]] = 0 THEN 1 ELSE 0 END)',
                ),
                'spam' => new \yii\db\Expression(
                    'SUM(CASE WHEN [[submissions.isSpam]] = 1 THEN 1 ELSE 0 END)',
                ),
            ])
            ->from(['submissions' => Table::FORMIE_SUBMISSIONS])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[submissions.id]]')
            ->where([
                'submissions.formId' => $formIds,
                'elements.dateDeleted' => null,
            ]);

        $this->applyDbStateFilters($query, $filters);
        $this->applyDbStatusFilters($query, $filters);
        $this->applyDbDateFilters($query, $filters);

        $results = $query
            ->groupBy(['bucket'])
            ->orderBy(['bucket' => SORT_ASC])
            ->all();

        $rows = [];

        foreach ($results as $result) {
            $complete = (int)$result['complete'];
            $incomplete = (int)$result['incomplete'];
            $spam = (int)$result['spam'];

            $rows[] = [
                'date' => $result['bucket'],
                'complete' => $complete,
                'incomplete' => $incomplete,
                'spam' => $spam,
                'total' => $complete + $incomplete + $spam,
            ];
        }

        [$rangeStart, $rangeEnd] = $this->_resolveChartDateRange($filters, $rows);

        return [
            'range' => [
                'start' => $rangeStart->format('Y-m-d'),
                'end' => $rangeEnd->format('Y-m-d'),
            ],
            'rows' => $this->_fillChartBuckets($rows, $rangeStart, $rangeEnd),
        ];
    }

    public function getTableData(
        Report $report,
        int $page = 1,
        int $limit = 100,
        ?User $user = null,
        ?array $columnOverride = null,
        array $viewer = [],
    ): array {
        $user ??= Craft::$app->getUser()->getIdentity();
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $search = trim((string)($viewer['search'] ?? ''));
        $sort = (string)($viewer['sort'] ?? 'dateCreated');
        $sortDir = strtolower((string)($viewer['sortDir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = $this->buildSubmissionQuery($report, $user, $viewer);

        if ($search !== '') {
            $query->search($search);
        }

        $this->applyViewerSort($query, $sort, $sortDir);

        $total = (int)(clone $query)->count();
        $submissions = $query
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->all();

        $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, $columnOverride);
        $display = $report->getSettingsModel()->display;
        $rows = [];

        foreach ($submissions as $submission) {
            $rows[] = [
                'id' => (int)$submission->id,
                'cells' => Formie::$plugin->getReportColumns()->formatViewerRow($submission, $columns, $display),
            ];
        }

        return [
            'columns' => array_map(fn(array $column) => [
                'id' => $column['id'],
                'type' => $column['type'],
                'handle' => $column['handle'],
                'header' => $column['header'],
            ], $columns),
            'rows' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $limit > 0 ? (int)ceil($total / $limit) : 0,
            ],
            'sort' => [
                'handle' => $sort,
                'dir' => $sortDir,
            ],
        ];
    }

    public function getSortableColumns(): array
    {
        $definitions = Formie::$plugin->getReportColumns()->getAttributeDefinitions();
        $allowed = ['dateCreated', 'dateUpdated', 'id', 'title', 'status', 'formName'];
        $columns = [];

        foreach ($allowed as $handle) {
            if (!isset($definitions[$handle])) {
                continue;
            }

            $columns[] = [
                'handle' => $handle,
                'label' => $definitions[$handle],
            ];
        }

        return $columns;
    }

    public function buildViewerQuery(Report $report, ?User $user = null, array $viewer = []): ElementQueryInterface
    {
        $query = $this->buildSubmissionQuery($report, $user, $viewer);
        $search = trim((string)($viewer['search'] ?? ''));

        if ($search !== '') {
            $query->search($search);
        }

        $this->applyViewerSort(
            $query,
            (string)($viewer['sort'] ?? 'dateCreated'),
            (string)($viewer['sortDir'] ?? 'desc'),
        );

        return $query;
    }

    public function resolveFilters(Report $report, array $viewer = []): array
    {
        $filters = ReportDateBoundHelper::migrateLegacyFilters($report->getSettingsModel()->filters);
        $filters = ReportDateBoundHelper::applyResolvedDates($filters);

        foreach (['startDate', 'endDate'] as $dateKey) {
            if (!array_key_exists($dateKey, $viewer)) {
                continue;
            }

            $filters[$dateKey] = Formie::$plugin->getReportEditor()->normalizeFilterDateTime(
                $viewer[$dateKey],
                $dateKey === 'endDate',
            );
        }

        return $filters;
    }

    public function resolveFormIds(mixed $formIds, ?User $user = null): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();

        if ($formIds === '*' || $formIds === ['*']) {
            $forms = Form::find()->status(null)->all();
        } elseif ($formIds === null || $formIds === []) {
            return [];
        } else {
            $forms = Form::find()->id($formIds)->status(null)->all();
        }

        $resolved = [];

        foreach ($forms as $form) {
            if ($this->canViewFormSubmissions($user, $form)) {
                $resolved[] = (int)$form->id;
            }
        }

        return $resolved;
    }

    public function canViewFormSubmissions(?User $user, Form $form): bool
    {
        return Formie::$plugin->getPermissions()->canViewSubmissions($user, $form);
    }


    // Private Methods
    // =========================================================================

    private function applyStateFilters(ElementQueryInterface $query, array $filters): void
    {
        $includeComplete = (bool)($filters['includeComplete'] ?? true);
        $includeIncomplete = (bool)($filters['includeIncomplete'] ?? true);
        $includeSpam = (bool)($filters['includeSpam'] ?? false);

        if ($includeComplete && $includeIncomplete && $includeSpam) {
            $query->isIncomplete(null)->isSpam(null);

            return;
        }

        if (!$includeComplete && !$includeIncomplete && !$includeSpam) {
            $query->id(false);

            return;
        }

        if ($includeSpam && !$includeComplete && !$includeIncomplete) {
            $query->isSpam(true)->isIncomplete(false);

            return;
        }

        if ($includeIncomplete && !$includeComplete && !$includeSpam) {
            $query->isIncomplete(true)->isSpam(false);

            return;
        }

        if ($includeComplete && !$includeIncomplete && !$includeSpam) {
            $query->isIncomplete(false)->isSpam(false);

            return;
        }

        // Mixed inclusion requires OR semantics; fall back to broad query and filter at export time for now.
        $query->isIncomplete(null)->isSpam(null);
    }

    private function applyStatusFilters(ElementQueryInterface $query, array $filters): void
    {
        $statusIds = $filters['statusIds'] ?? [];

        if ($statusIds) {
            $query->statusId($statusIds);
        }
    }

    private function applyDateFilters(ElementQueryInterface $query, array $filters, ?DateTime $since = null): void
    {
        $startDate = DateTimeHelper::toDateTime($filters['startDate'] ?? null);
        $endDate = DateTimeHelper::toDateTime($filters['endDate'] ?? null);

        if ($since) {
            $startDate = $since;
        }

        $params = [];

        if ($startDate) {
            $params[] = '>= ' . Db::prepareDateForDb($startDate);
        }

        if ($endDate) {
            $params[] = '<= ' . Db::prepareDateForDb($endDate);
        }

        if ($params === []) {
            return;
        }

        if (count($params) === 1) {
            $query->dateCreated($params[0]);

            return;
        }

        $query->dateCreated(array_merge(['and'], $params));
    }

    private function _getSummaryCountsByForm(array $formIds, array $filters): array
    {
        if (!$formIds) {
            return [];
        }

        $query = (new Query())
            ->select([
                'formId' => 'submissions.formId',
                'complete' => new \yii\db\Expression(
                    'SUM(CASE WHEN [[submissions.isIncomplete]] = 0 AND [[submissions.isSpam]] = 0 THEN 1 ELSE 0 END)',
                ),
                'incomplete' => new \yii\db\Expression(
                    'SUM(CASE WHEN [[submissions.isIncomplete]] = 1 AND [[submissions.isSpam]] = 0 THEN 1 ELSE 0 END)',
                ),
                'spam' => new \yii\db\Expression(
                    'SUM(CASE WHEN [[submissions.isSpam]] = 1 THEN 1 ELSE 0 END)',
                ),
            ])
            ->from(['submissions' => Table::FORMIE_SUBMISSIONS])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[submissions.id]]')
            ->where([
                'submissions.formId' => $formIds,
                'elements.dateDeleted' => null,
            ]);

        $this->_applySummaryCountFilters($query, $filters);

        $countsByFormId = [];

        foreach ($query->groupBy(['submissions.formId'])->all() as $result) {
            $countsByFormId[(int)$result['formId']] = [
                'complete' => (int)$result['complete'],
                'incomplete' => (int)$result['incomplete'],
                'spam' => (int)$result['spam'],
            ];
        }

        return $countsByFormId;
    }

    private function _applySummaryCountFilters(Query $query, array $filters): void
    {
        $startDate = DateTimeHelper::toDateTime($filters['startDate'] ?? null);
        $endDate = DateTimeHelper::toDateTime($filters['endDate'] ?? null);

        if ($startDate) {
            $query->andWhere(['>=', 'elements.dateCreated', Db::prepareDateForDb($startDate)]);
        }

        if ($endDate) {
            $query->andWhere(['<=', 'elements.dateCreated', Db::prepareDateForDb($endDate)]);
        }

        $statusIds = $filters['statusIds'] ?? [];

        if ($statusIds) {
            $query->andWhere(['submissions.statusId' => $statusIds]);
        }
    }

    private function applyViewerSort(ElementQueryInterface $query, ?string $sort, string $sortDir = 'desc'): void
    {
        $allowed = ['id', 'dateCreated', 'dateUpdated', 'title', 'status'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'dateCreated';
        $direction = strtolower($sortDir) === 'asc' ? SORT_ASC : SORT_DESC;

        if ($sort === 'status') {
            $query->orderBy(['statusId' => $direction, 'dateCreated' => SORT_DESC]);

            return;
        }

        $query->orderBy([$sort => $direction]);
    }

    private function applyDbStateFilters(Query $query, array $filters): void
    {
        $includeComplete = (bool)($filters['includeComplete'] ?? true);
        $includeIncomplete = (bool)($filters['includeIncomplete'] ?? true);
        $includeSpam = (bool)($filters['includeSpam'] ?? false);

        if ($includeComplete && $includeIncomplete && $includeSpam) {
            return;
        }

        if (!$includeComplete && !$includeIncomplete && !$includeSpam) {
            $query->andWhere('0=1');

            return;
        }

        $conditions = ['or'];

        if ($includeComplete) {
            $conditions[] = [
                'and',
                ['submissions.isIncomplete' => false],
                ['submissions.isSpam' => false],
            ];
        }

        if ($includeIncomplete) {
            $conditions[] = [
                'and',
                ['submissions.isIncomplete' => true],
                ['submissions.isSpam' => false],
            ];
        }

        if ($includeSpam) {
            $conditions[] = ['submissions.isSpam' => true];
        }

        $query->andWhere($conditions);
    }

    private function applyDbStatusFilters(Query $query, array $filters): void
    {
        $statusIds = $filters['statusIds'] ?? [];

        if ($statusIds) {
            $query->andWhere(['submissions.statusId' => $statusIds]);
        }
    }

    private function applyDbDateFilters(Query $query, array $filters): void
    {
        $startDate = DateTimeHelper::toDateTime($filters['startDate'] ?? null);
        $endDate = DateTimeHelper::toDateTime($filters['endDate'] ?? null);

        if ($startDate) {
            $query->andWhere(['>=', 'elements.dateCreated', Db::prepareDateForDb($startDate)]);
        }

        if ($endDate) {
            $query->andWhere(['<=', 'elements.dateCreated', Db::prepareDateForDb($endDate)]);
        }
    }

    private function _resolveChartDateRange(array $filters, array $rows): array
    {
        $filterStart = DateTimeHelper::toDateTime($filters['startDate'] ?? null);
        $filterEnd = DateTimeHelper::toDateTime($filters['endDate'] ?? null);

        $rangeEnd = $filterEnd ? (clone $filterEnd) : new DateTime('today');
        $rangeEnd->setTime(23, 59, 59);

        if ($filterStart) {
            $rangeStart = (clone $filterStart);
            $rangeStart->setTime(0, 0, 0);
        } elseif ($filterEnd) {
            $rangeStart = (clone $rangeEnd)->modify('-29 days')->setTime(0, 0, 0);
        } else {
            $rangeStart = (clone $rangeEnd)->modify('-29 days')->setTime(0, 0, 0);
        }

        if ($rows !== [] && !$filterStart && !$filterEnd) {
            $firstBucket = DateTimeHelper::toDateTime($rows[0]['date']);
            $lastBucket = DateTimeHelper::toDateTime($rows[array_key_last($rows)]['date']);

            if ($firstBucket && $firstBucket < $rangeStart) {
                $rangeStart = (clone $firstBucket)->setTime(0, 0, 0);
            }

            if ($lastBucket && $lastBucket > $rangeEnd) {
                $rangeEnd = (clone $lastBucket)->setTime(23, 59, 59);
            }
        }

        if ($rangeStart > $rangeEnd) {
            [$rangeStart, $rangeEnd] = [$rangeEnd, $rangeStart];
            $rangeStart->setTime(0, 0, 0);
            $rangeEnd->setTime(23, 59, 59);
        }

        $daySpan = (int)$rangeStart->diff($rangeEnd)->format('%a') + 1;

        if ($daySpan > 366) {
            $rangeStart = (clone $rangeEnd)->modify('-365 days')->setTime(0, 0, 0);
        }

        return [$rangeStart, $rangeEnd];
    }

    private function _fillChartBuckets(array $rows, DateTime $rangeStart, DateTime $rangeEnd): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            $indexed[$row['date']] = [
                'complete' => (int)($row['complete'] ?? 0),
                'incomplete' => (int)($row['incomplete'] ?? 0),
                'spam' => (int)($row['spam'] ?? 0),
            ];
        }

        $filled = [];
        $cursor = (clone $rangeStart)->setTime(0, 0, 0);
        $end = (clone $rangeEnd)->setTime(0, 0, 0);

        while ($cursor <= $end) {
            $date = $cursor->format('Y-m-d');
            $complete = $indexed[$date]['complete'] ?? 0;
            $incomplete = $indexed[$date]['incomplete'] ?? 0;
            $spam = $indexed[$date]['spam'] ?? 0;

            $filled[] = [
                'date' => $date,
                'complete' => $complete,
                'incomplete' => $incomplete,
                'spam' => $spam,
                'total' => $complete + $incomplete + $spam,
            ];
            $cursor->modify('+1 day');
        }

        return $filled;
    }
}
