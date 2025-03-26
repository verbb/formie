<?php
namespace verbb\formie\widgets;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\web\assets\cp\WidgetsAsset;

use Craft;
use craft\base\Widget;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;

use yii\db\Expression;

use DateTime;
use DateInterval;

class RecentSubmissions extends Widget
{
    // Constants
    // =========================================================================

    public const DATE_RANGE_ALL = 'all';
    public const DATE_RANGE_TODAY = 'today';
    public const DATE_RANGE_THISWEEK = 'thisWeek';
    public const DATE_RANGE_THISMONTH = 'thisMonth';
    public const DATE_RANGE_THISYEAR = 'thisYear';
    public const DATE_RANGE_PAST7DAYS = 'past7Days';
    public const DATE_RANGE_PAST30DAYS = 'past30Days';
    public const DATE_RANGE_PAST90DAYS = 'past90Days';
    public const DATE_RANGE_PASTYEAR = 'pastYear';
    public const DATE_RANGE_CUSTOM = 'custom';

    public const START_DAY_INT_TO_DAY = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public const START_DAY_INT_TO_END_DAY = [
        0 => 'Saturday',
        1 => 'Sunday',
        2 => 'Monday',
        3 => 'Tuesday',
        4 => 'Wednesday',
        5 => 'Thursday',
        6 => 'Friday',
    ];

    public const DATE_RANGE_INTERVAL = [
        self::DATE_RANGE_TODAY => 'day',
        self::DATE_RANGE_THISWEEK => 'day',
        self::DATE_RANGE_THISMONTH => 'day',
        self::DATE_RANGE_THISYEAR => 'month',
        self::DATE_RANGE_PAST7DAYS => 'day',
        self::DATE_RANGE_PAST30DAYS => 'day',
        self::DATE_RANGE_PAST90DAYS => 'day',
        self::DATE_RANGE_PASTYEAR => 'month',
        self::DATE_RANGE_ALL => 'month',
    ];


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Recent Form Submissions');
    }

    public static function icon(): string
    {
        return Craft::getAlias('@verbb/formie/icon-mask.svg');
    }

    public static function isSelectable(): bool
    {
        // Ensure users have minimum permissions
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return false;
        }

        if (!$user->can('accessPlugin-formie') || !$user->can('formie-accessSubmissions')) {
            return false;
        }

        return true;
    }


    // Properties
    // =========================================================================

    public ?string $title = null;
    public array|string|null $formIds = [];
    public ?int $limit = 5;
    public ?string $displayType = 'list';
    public ?DateTime $startDate = null;
    public ?DateTime $endDate = null;
    public mixed $dateRange = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        if (array_key_exists('weekStartDay', $config)) {
            unset($config['weekStartDay']);
        }

        parent::__construct($config);
    }

    public function init(): void
    {
        parent::init();

        if (!$this->title) {
            $this->title = self::displayName();
        }
    }

    public function getTitle(): string
    {
        return $this->title ?: static::displayName();
    }

    public function getBodyHtml(): ?string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(WidgetsAsset::class);

        $id = 'recent-submissions' . StringHelper::randomString();
        $namespaceId = Craft::$app->getView()->namespaceInputId($id);

        $user = Craft::$app->getUser()->getIdentity();

        $variables = [
            'namespaceId' => $namespaceId,
            'widget' => $this,
            'labels' => [],
            'datasets' => [],
            'totalSubmissions' => [],
            'submissions' => [],
        ];

        // Postgres won't like querying `*`
        $formIds = ($this->formIds === '*' || $this->formIds === ['*']) ? null : $this->formIds;

        if ($this->displayType === 'list') {
            $filteredFormIds = [];
            $forms = Form::find()->id($formIds)->all();

            foreach ($forms as $form) {
                if (!$user->can('formie-viewSubmissions')) {
                    if (!$user->can('formie-viewSubmissions:' . $form->uid)) {
                        continue;
                    }
                }

                $filteredFormIds[] = $form->id;
            }

            $variables['submissions'] = Submission::find()->limit($this->limit)->formId($filteredFormIds)->all();
        }

        if ($this->displayType === 'pie') {
            $forms = Form::find()->id($formIds)->all();

            foreach ($forms as $form) {
                if (!$user->can('formie-viewSubmissions')) {
                    if (!$user->can('formie-viewSubmissions:' . $form->uid)) {
                        continue;
                    }
                }

                $variables['labels'][] = $form->title;
                $variables['totalSubmissions'][] = $this->getQuery($form)->count();
            }
        }

        if ($this->displayType === 'line') {
            $forms = Form::find()->id($formIds)->all();

            $combinedChartData = [];
            $formTitles = [];

            foreach ($forms as $form) {
                if (!$user->can('formie-viewSubmissions')) {
                    if (!$user->can('formie-viewSubmissions:' . $form->uid)) {
                        continue;
                    }
                }

                $formTitles[] = $form->title;

                $chartData = $this->_createChartQuery($this->getQuery($form), [
                    new Expression('COUNT([[submissions.id]]) as total'),
                ], [
                    'total' => 0,
                ]);

                if ($chartData) {
                    foreach ($chartData as $key => $data) {
                        $combinedChartData[$key][$form->title] = $data['total'];
                    }
                }
            }

            ksort($combinedChartData);

            // Normalise the chart data for multiple forms
            $normalisedData = [];

            foreach ($combinedChartData as $date => $chartData) {
                $variables['labels'][] = $date;

                foreach ($formTitles as $key => $formTitle) {
                    $variables['datasets'][$key]['label'] = $formTitle;
                    $variables['datasets'][$key]['data'][] = $chartData[$formTitle] ?? 0;
                }
            }
        }

        return $view->renderTemplate('formie/widgets/submissions/body', $variables);
    }

    public function getSettingsHtml(): string
    {
        $id = 'recent-submissions' . StringHelper::randomString();
        $namespaceId = Craft::$app->getView()->namespaceInputId($id);

        $formOptions = [];

        $user = Craft::$app->getUser()->getIdentity();

        foreach (Form::find()->all() as $form) {
            if (!$user->can('formie-viewSubmissions')) {
                if (!$user->can('formie-viewSubmissions:' . $form->uid)) {
                    continue;
                }
            }

            $formOptions[$form->id] = $form->title;
        }

        return Craft::$app->getView()->renderTemplate('formie/widgets/submissions/settings', [
            'id' => $id,
            'namespaceId' => $namespaceId,
            'widget' => $this,
            'formOptions' => $formOptions,
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        return [
            [['formIds'], 'required'],
        ];
    }


    // Private Methods
    // =========================================================================

    private function getQuery(Form $form): Query
    {
        $startDate = null;
        $endDate = null;

        if ($this->dateRange == self::DATE_RANGE_CUSTOM) {
            $startDate = DateTimeHelper::toDateTime($this->startDate);
            $endDate = DateTimeHelper::toDateTime($this->endDate);
        } else if ($this->dateRange) {
            $startDate = $this->_getStartDate($this->dateRange);
            $endDate = $this->_getEndDate($this->dateRange);
        }

        $query = (new Query)
            ->from(['submissions' => Table::FORMIE_SUBMISSIONS])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[submissions.id]]')
            ->where(['formId' => $form->id])
            ->andWhere(['elements.dateDeleted' => null]);

        if ($startDate) {
            $query->andWhere(['>=', 'elements.dateCreated', Db::prepareDateForDb($startDate)]);
        }

        if ($endDate) {
            $query->andWhere(['<=', 'elements.dateCreated', Db::prepareDateForDb($endDate)]);
        }

        return $query;
    }

    private function _getStartDate(?string $dateRange): DateTime|bool|null
    {
        $date = new DateTime();

        if ($dateRange === self::DATE_RANGE_ALL) {
            return null;
        }

        if ($dateRange === self::DATE_RANGE_THISMONTH) {
            $date = DateTimeHelper::toDateTime(strtotime('first day of this month'));
        }

        if ($dateRange === self::DATE_RANGE_THISWEEK) {
            $weekStartDay = DateTimeHelper::firstWeekDay();

            if (date('l') != self::START_DAY_INT_TO_DAY[$weekStartDay]) {
                $date = DateTimeHelper::toDateTime(strtotime('last ' . self::START_DAY_INT_TO_DAY[$weekStartDay]));
            }
        }

        if ($dateRange === self::DATE_RANGE_THISYEAR) {
            $date->setDate($date->format('Y'), 1, 1);
        }

        if ($dateRange === self::DATE_RANGE_PAST7DAYS || $dateRange === self::DATE_RANGE_PAST30DAYS || $dateRange === self::DATE_RANGE_PAST90DAYS) {
            $number = str_replace(['past', 'Days'], '', $dateRange);
            // Minus one so we include today as a "past day"
            $number--;
            $date = $this->_getEndDate($dateRange);

            if ($date) {
                $interval = new DateInterval('P' . $number . 'D');
                $date->sub($interval);
            }
        }

        if ($dateRange === self::DATE_RANGE_PASTYEAR) {
            $date = $this->_getEndDate($dateRange);

            if ($date) {
                $interval = new DateInterval('P1Y');
                $date->sub($interval);
                $date->add(new DateInterval('P1M'));
            }
        }

        if ($date instanceof DateTime) {
            $date->setTime(0, 0, 0);
        }

        return $date;
    }

    private function _getEndDate(?string $dateRange): DateTime|bool
    {
        $date = new DateTime();

        if ($dateRange === self::DATE_RANGE_THISMONTH) {
            $date = DateTimeHelper::toDateTime(strtotime('last day of this month'));
        }

        if ($dateRange === self::DATE_RANGE_THISWEEK) {
            $weekStartDay = DateTimeHelper::firstWeekDay();
            $endDayOfWeek = self::START_DAY_INT_TO_END_DAY[$weekStartDay] ?? null;

            if (date('l') != $endDayOfWeek) {
                $date = DateTimeHelper::toDateTime(strtotime('next ' . $endDayOfWeek));
            }
        }

        if ($date instanceof DateTime) {
            $date->setTime(23, 59, 59);
        }

        return $date;
    }

    private function _createChartQuery($query, array $select = [], array $resultsDefaults = []): ?array
    {
        // Allow the passing in of a custom query in case we need to add extra logic
        $defaults = [];
        $dateRangeInterval = $this->getDateRangeInterval();
        $options = $this->getChartQueryOptionsByInterval($dateRangeInterval);

        if (!$options) {
            return null;
        }

        $startDate = $this->_getStartDate($this->dateRange);

        if (!$startDate) {
            return null;
        }

        $dateKeyDate = DateTimeHelper::toDateTime($startDate->format('U'));
        $endDate = $this->_getEndDate($this->dateRange);

        if (!$endDate) {
            return null;
        }

        while ($dateKeyDate <= $endDate) {
            $key = $dateKeyDate->format($options['dateKeyFormat']);

            // Setup default results values
            $tmp = $resultsDefaults;
            $tmp['datekey'] = $key;

            $defaults[$key] = $tmp;
            $dateKeyDate->add(new DateInterval($options['interval']));
        }

        // Add defaults to select
        $select[] = new Expression($options['dateKey'] . ' as datekey');
        $results = $query
            ->select($select)
            ->groupBy(new Expression($options['groupBy']))
            ->orderBy(new Expression($options['orderBy']))
            ->indexBy('datekey')
            ->all();

        $return = array_replace($defaults, $results);
        ksort($return, SORT_NATURAL);

        return $return;
    }

    private function getDateRangeInterval(): string
    {
        if ($this->dateRange == self::DATE_RANGE_CUSTOM) {
            $interval = date_diff(DateTimeHelper::toDateTime($this->startDate), DateTimeHelper::toDateTime($this->endDate));
            return ($interval->days > 90) ? 'month' : 'day';
        }

        return self::DATE_RANGE_INTERVAL[$this->dateRange] ?? 'day';
    }

    private function getChartQueryOptionsByInterval(string $interval): ?array
    {
        if (Craft::$app->getDb()->getIsMysql()) {
            // The fallback if timezone can't happen in sql is simply just extract the information from the UTC date stored in `dateOrdered`.
            $timezoneConversionSql = "[[elements.dateCreated]]";

            if (Db::supportsTimeZones()) {
                $timezoneConversionSql = "CONVERT_TZ([[elements.dateCreated]], 'UTC', '" . Craft::$app->getTimeZone() . "')";
            } else {
                Craft::getLogger()->log('For accurate Formie statistics it is recommend to make sure you have the timezones table populated. https://craftcms.com/knowledge-base/populating-mysql-mariadb-timezone-tables', Craft::getLogger()::LEVEL_WARNING, 'formie');
            }
        } else {
            $timezoneConversionSql = "(([[elements.dateCreated]] AT TIME ZONE 'UTC') AT TIME ZONE '" . Craft::$app->getTimeZone() . "')";
        }

        switch ($interval) {
            case 'month':
            {
                $sqlExpression = "CONCAT(EXTRACT(YEAR FROM " . $timezoneConversionSql . "), '-', EXTRACT(MONTH FROM " . $timezoneConversionSql . "))";

                return [
                    'interval' => 'P1M',
                    'dateKeyFormat' => 'Y-n',
                    'dateKey' => $sqlExpression,
                    'groupBy' => $sqlExpression,
                    'orderBy' => $sqlExpression . ' ASC',
                ];
            }
            case 'day':
            {
                $sqlExpression = "DATE(" . $timezoneConversionSql . ")";

                return [
                    'interval' => 'P1D',
                    'dateKeyFormat' => 'Y-m-d',
                    'dateKey' => $sqlExpression,
                    'groupBy' => $sqlExpression,
                    'orderBy' => $sqlExpression,
                ];
            }
        }

        return null;
    }
}
