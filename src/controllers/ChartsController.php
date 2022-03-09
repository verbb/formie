<?php
namespace verbb\formie\controllers;

use Craft;
use craft\controllers\ElementIndexesController;
use craft\helpers\ChartHelper;
use craft\helpers\DateTimeHelper;

use yii\base\Response;

class ChartsController extends ElementIndexesController
{
    // Public Methods
    // =========================================================================

    public function actionGetSubmissionsData(): Response
    {
        $request = Craft::$app->getRequest();

        $startDateParam = $request->getRequiredBodyParam('startDate');
        $endDateParam = $request->getRequiredBodyParam('endDate');

        $startDate = DateTimeHelper::toDateTime($startDateParam);
        $endDate = DateTimeHelper::toDateTime($endDateParam);
        $endDate->modify('+1 day');

        $intervalUnit = ChartHelper::getRunChartIntervalUnit($startDate, $endDate);

        $criteria = $request->getParam('criteria');

        // There must be a better way to do this?
        $checkParams = ['isIncomplete', 'isSpam', 'trashed', 'drafts'];

        foreach ($checkParams as $param) {
            if (isset($criteria[$param])) {
                $criteria[$param] = filter_var($criteria[$param], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $query = clone $this->getElementQuery()->search(null);

        Craft::configure($query, $criteria);

        // Get the chart data table
        $dataTable = ChartHelper::getRunChartDataFromQuery($query, $startDate, $endDate, 'formie_submissions.dateCreated', 'count', '*', [
            'intervalUnit' => $intervalUnit,
            'valueLabel' => Craft::t('formie', 'Submissions'),
            'valueType' => 'number',
        ]);

        // Get the total submissions
        $total = 0;

        foreach ($dataTable['rows'] as $row) {
            $total += $row[1];
        }

        return $this->asJson([
            'dataTable' => $dataTable,
            'total' => $query,
            'totalHtml' => $total,
            'formats' => ChartHelper::formats(),
            'orientation' => Craft::$app->locale->getOrientation(),
            'scale' => $intervalUnit,
            'localeDefinition' => [],
        ]);
    }
}
