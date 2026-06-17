<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use craft\helpers\DateTimeHelper;

use DateTime;
use DateTimeZone;

class ReportDateBoundHelper
{
    // Static Methods
    // =========================================================================

    public static function defaultBound(): array
    {
        return [
            'option' => '',
            'date' => null,
            'offset' => 'add',
            'offsetNumber' => 0,
            'offsetType' => 'days',
        ];
    }

    public static function migrateLegacyFilters(array $filters): array
    {
        $startBound = self::normalizeBound($filters['startBound'] ?? null, false);
        $endBound = self::normalizeBound($filters['endBound'] ?? null, true);

        if ($startBound['option'] === '' && !empty($filters['startDate'])) {
            $startBound = self::boundFromLegacyDate($filters['startDate'], false);
        }

        if ($endBound['option'] === '' && !empty($filters['endDate'])) {
            $endBound = self::boundFromLegacyDate($filters['endDate'], true);
        }

        $filters['startBound'] = $startBound;
        $filters['endBound'] = $endBound;
        unset($filters['startDate'], $filters['endDate']);

        return $filters;
    }

    public static function boundFromLegacyDate(mixed $value, bool $isEnd): array
    {
        $normalized = Formie::$plugin->getReportEditor()->normalizeFilterDateTime($value, $isEnd);

        if (!$normalized) {
            return self::defaultBound();
        }

        return [
            'option' => 'date',
            'date' => $normalized,
            'offset' => 'add',
            'offsetNumber' => 0,
            'offsetType' => 'days',
        ];
    }

    public static function normalizeBound(mixed $bound, bool $isEnd): array
    {
        if (!is_array($bound)) {
            return self::defaultBound();
        }

        $normalized = array_merge(self::defaultBound(), $bound);
        $option = (string)($normalized['option'] ?? '');

        if (!in_array($option, ['', 'today', 'date'], true)) {
            $option = '';
        }

        $normalized['option'] = $option;

        if ($option !== 'date') {
            $normalized['date'] = null;
        } else {
            $normalized['date'] = Formie::$plugin->getReportEditor()->normalizeFilterDateTime(
                $normalized['date'] ?? null,
                $isEnd,
            );
        }

        $offset = (string)($normalized['offset'] ?? 'add');
        $normalized['offset'] = $offset === 'subtract' ? 'subtract' : 'add';
        $normalized['offsetNumber'] = max(0, (int)($normalized['offsetNumber'] ?? 0));

        $offsetType = (string)($normalized['offsetType'] ?? 'days');

        if (!in_array($offsetType, ['days', 'weeks', 'months', 'years'], true)) {
            $offsetType = 'days';
        }

        $normalized['offsetType'] = $offsetType;

        return $normalized;
    }

    public static function applyResolvedDates(array $filters): array
    {
        $filters['startDate'] = self::resolveBound($filters['startBound'] ?? null, false)?->format('Y-m-d H:i:s');
        $filters['endDate'] = self::resolveBound($filters['endBound'] ?? null, true)?->format('Y-m-d H:i:s');

        return $filters;
    }

    public static function resolveBound(mixed $bound, bool $isEnd): ?DateTime
    {
        $bound = self::normalizeBound($bound, $isEnd);
        $option = $bound['option'];

        if ($option === '') {
            return null;
        }

        if ($option === 'today') {
            $operator = $bound['offset'] === 'add' ? '+' : '-';
            $interval = "{$operator}{$bound['offsetNumber']} {$bound['offsetType']}";

            $date = (new DateTime('now', new DateTimeZone('UTC')))->modify($interval);
            $date->setTime($isEnd ? 23 : 0, $isEnd ? 59 : 0, $isEnd ? 59 : 0);

            return $date;
        }

        if ($option === 'date' && $bound['date']) {
            return DateTimeHelper::toDateTime($bound['date']);
        }

        return null;
    }
}
