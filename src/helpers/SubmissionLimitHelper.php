<?php
namespace verbb\formie\helpers;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\models\FormSettings;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;

use DateTime;

class SubmissionLimitHelper
{
    // Constants
    // =========================================================================

    public const SCOPE_FORM = 'form';
    public const SCOPE_IP = 'ipAddress';
    public const SCOPE_USER = 'user';

    public const PERIOD_TOTAL = 'total';
    public const PERIOD_DAY = 'day';
    public const PERIOD_WEEK = 'week';
    public const PERIOD_MONTH = 'month';
    public const PERIOD_YEAR = 'year';


    // Static Methods
    // =========================================================================

    public static function isEnabled(FormSettings $settings): bool
    {
        return (bool)$settings->limitSubmissions || $settings->limitSubmissions === self::SCOPE_IP;
    }

    public static function getScope(FormSettings $settings): string
    {
        $scope = trim((string)($settings->limitSubmissionsScope ?? ''));

        if ($scope !== '' && self::_isValidScope($scope)) {
            return $scope;
        }

        if ($settings->limitSubmissions === self::SCOPE_IP) {
            return self::SCOPE_IP;
        }

        return self::SCOPE_FORM;
    }

    public static function getLimit(FormSettings $settings): int
    {
        $scope = self::getScope($settings);

        if ($scope === self::SCOPE_IP && $settings->limitSubmissionsIpAddressNumber) {
            return max(0, (int)$settings->limitSubmissionsIpAddressNumber);
        }

        return max(0, (int)($settings->limitSubmissionsNumber ?? 0));
    }

    public static function getPeriod(FormSettings $settings): string
    {
        $scope = self::getScope($settings);

        if ($scope === self::SCOPE_IP && $settings->limitSubmissionsIpAddressType) {
            return self::_normalizePeriod($settings->limitSubmissionsIpAddressType);
        }

        return self::_normalizePeriod($settings->limitSubmissionsType);
    }

    public static function hasReachedLimit(Form $form, ?Submission $submission = null): bool
    {
        $settings = $form->getSettings();

        if (!self::isEnabled($settings)) {
            return false;
        }

        $limit = self::getLimit($settings);

        if ($limit < 1) {
            return false;
        }

        $scope = self::getScope($settings);
        $query = Submission::find()->formId($form->id);

        if ($scope === self::SCOPE_FORM) {
            self::_applyPeriod($query, self::getPeriod($settings));

            return (int)$query->count() >= $limit;
        }

        if ($scope === self::SCOPE_IP) {
            $ip = self::_resolveIp($submission);

            if ($ip === '') {
                return false;
            }

            $query->andWhere(['formie_submissions.ipAddress' => $ip]);
            self::_applyPeriod($query, self::getPeriod($settings));

            return (int)$query->count() >= $limit;
        }

        $userId = self::_resolveUserId($form, $submission);

        if (!$userId) {
            return false;
        }

        $query->userId($userId);
        self::_applyPeriod($query, self::getPeriod($settings));

        return (int)$query->count() >= $limit;
    }

    public static function shouldCloseFormByLimit(Form $form): bool
    {
        $settings = $form->getSettings();

        if (!self::isEnabled($settings)) {
            return false;
        }

        if (self::getScope($settings) !== self::SCOPE_FORM) {
            return false;
        }

        return self::hasReachedLimit($form);
    }

    private static function _resolveIp(?Submission $submission): string
    {
        $ip = trim((string)($submission?->ipAddress ?? ''));

        if ($ip !== '') {
            return $ip;
        }

        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            return trim((string)Craft::$app->getRequest()->getUserIP());
        }

        return '';
    }

    private static function _resolveUserId(Form $form, ?Submission $submission): ?int
    {
        $userId = (int)($submission?->userId ?? 0);

        if ($userId) {
            return $userId;
        }

        if ($form->settings->collectUser || $form->settings->requireUser) {
            $identity = Craft::$app->getUser()->getIdentity();

            if ($identity) {
                return (int)$identity->id;
            }
        }

        return null;
    }

    private static function _applyPeriod(SubmissionQuery $query, string $period): void
    {
        if ($period === self::PERIOD_TOTAL) {
            return;
        }

        [$startDate, $endDate] = self::_periodBounds($period);

        $query->dateCreated([
            'and',
            '>= ' . Db::prepareDateForDb($startDate),
            '<= ' . Db::prepareDateForDb($endDate),
        ]);
    }

    /**
     * @return array{0: \DateTime, 1: \DateTime}
     */
    private static function _periodBounds(string $period): array
    {
        return match ($period) {
            self::PERIOD_DAY => [
                DateTimeHelper::toDateTime(new DateTime('today')),
                DateTimeHelper::toDateTime(new DateTime('tomorrow')),
            ],
            self::PERIOD_WEEK => [
                DateTimeHelper::toDateTime(new DateTime('monday this week'))->modify('-1 day'),
                DateTimeHelper::toDateTime(new DateTime('monday next week'))->modify('-1 day'),
            ],
            self::PERIOD_MONTH => [
                DateTimeHelper::toDateTime(new DateTime('first day of this month'))->setTime(0, 0, 0),
                DateTimeHelper::toDateTime(new DateTime('first day of next month'))->setTime(0, 0, 0),
            ],
            self::PERIOD_YEAR => [
                DateTimeHelper::toDateTime(new DateTime('first day of January'))->setTime(0, 0, 0),
                DateTimeHelper::toDateTime(new DateTime('first day of January next year'))->setTime(0, 0, 0),
            ],
            default => [
                DateTimeHelper::toDateTime(new DateTime('today')),
                DateTimeHelper::toDateTime(new DateTime('tomorrow')),
            ],
        };
    }

    private static function _normalizePeriod(mixed $period): string
    {
        $period = trim((string)($period ?? self::PERIOD_TOTAL));

        return self::_isValidPeriod($period) ? $period : self::PERIOD_TOTAL;
    }

    private static function _isValidScope(string $scope): bool
    {
        return in_array($scope, [self::SCOPE_FORM, self::SCOPE_IP, self::SCOPE_USER], true);
    }

    private static function _isValidPeriod(string $period): bool
    {
        return in_array($period, [
            self::PERIOD_TOTAL,
            self::PERIOD_DAY,
            self::PERIOD_WEEK,
            self::PERIOD_MONTH,
            self::PERIOD_YEAR,
        ], true);
    }
}
