<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\web\Request as WebRequest;

class SubmissionGuards extends Component
{
    // Constants
    // =========================================================================

    public const REPLAY_CACHE_DURATION = 86400;

    public const FORM_STARTED_AT_PARAM = 'formStartedAt';

    public const GLOBAL_THROTTLE_CACHE_KEY = 'formie.global-submission-throttle';


    // Public Methods
    // =========================================================================

    public function shouldRunGuards(SubmissionRequest $request): bool
    {
        if ($request->submitAction !== SubmissionWorkflow::SUBMIT_ACTION_SUBMIT) {
            return false;
        }

        if (!in_array($request->processMode, [SubmissionWorkflow::PROCESS_MODE_SUBMIT], true)) {
            return false;
        }

        if ($request->submission->isSpam) {
            return false;
        }

        return $this->_isBrowserFormSubmission();
    }

    public function validateRequest(SubmissionRequest $request): ?string
    {
        if (!$this->shouldRunGuards($request)) {
            return null;
        }

        $settings = Formie::$plugin->getSettings();

        if ($settings->enableGlobalSubmissionThrottling) {
            $reason = $this->_validateGlobalSubmissionThrottling($settings);

            if ($reason) {
                return $reason;
            }
        }

        if ($settings->enableIpSubmissionThrottling) {
            $reason = $this->_validateIpSubmissionThrottling($settings, $request);

            if ($reason) {
                return $reason;
            }
        }

        if ($settings->enableHoneypot) {
            $reason = $this->_validateHoneypot($settings);

            if ($reason) {
                return $reason;
            }
        }

        if ($settings->enableMinimumSubmitTime) {
            $reason = $this->_validateMinimumSubmitTime($settings);

            if ($reason) {
                return $reason;
            }
        }

        if ($settings->enableReplayProtection) {
            $reason = $this->_validateReplayProtection($request);

            if ($reason) {
                return $reason;
            }
        }

        if ($settings->enableFormSubmitExpiration) {
            $reason = $this->_validateFormSubmitExpiration($settings);

            if ($reason) {
                return $reason;
            }
        }

        return null;
    }

    public function isReplayTokenConsumed(string $formUid, string $requestToken): bool
    {
        $cacheKey = $this->_replayCacheKey($formUid, $requestToken);

        return Craft::$app->getCache()->get($cacheKey) !== false;
    }

    public function consumeReplayToken(string $formUid, string $requestToken): void
    {
        $requestToken = trim($requestToken);

        if ($requestToken === '') {
            return;
        }

        Craft::$app->getCache()->set(
            $this->_replayCacheKey($formUid, $requestToken),
            true,
            self::REPLAY_CACHE_DURATION,
        );
    }

    public function shouldConsumeReplayToken(SubmissionRequest $request): bool
    {
        if (!$this->shouldRunGuards($request)) {
            return false;
        }

        $settings = Formie::$plugin->getSettings();

        if (!$settings->enableReplayProtection) {
            return false;
        }

        $requestToken = trim((string)$request->requestToken);

        if ($requestToken === '') {
            return false;
        }

        return !$request->submission->isIncomplete;
    }


    // Private Methods
    // =========================================================================

    private function _validateGlobalSubmissionThrottling(Settings $settings): ?string
    {
        $limit = max(1, (int)$settings->globalSubmissionThrottleLimit);
        $window = max(1, (int)$settings->globalSubmissionThrottleWindowSeconds);
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $mutexKey = self::GLOBAL_THROTTLE_CACHE_KEY . '.lock';
        $now = time();
        $lockAcquired = $mutex?->acquire($mutexKey, 3) ?? false;

        try {
            $entry = $cache->get(self::GLOBAL_THROTTLE_CACHE_KEY);

            if (!is_array($entry) || !isset($entry['count'], $entry['resetAt']) || (int)$entry['resetAt'] <= $now) {
                $entry = [
                    'count' => 0,
                    'resetAt' => $now + $window,
                ];
            }

            $count = (int)$entry['count'];
            $resetAt = max($now + 1, (int)$entry['resetAt']);

            if ($count >= $limit) {
                return Craft::t('formie', 'Global submission rate limit exceeded.');
            }

            $entry['count'] = $count + 1;
            $cache->set(self::GLOBAL_THROTTLE_CACHE_KEY, $entry, max(1, $resetAt - $now));
        } finally {
            if ($lockAcquired) {
                $mutex?->release($mutexKey);
            }
        }

        return null;
    }

    private function _validateIpSubmissionThrottling(Settings $settings, SubmissionRequest $request): ?string
    {
        $minutes = max(1, (int)$settings->ipSubmissionThrottleMinutes);
        $ip = trim((string)($request->submission->ipAddress ?? $this->_requestUserIp()));

        if ($ip === '') {
            return null;
        }

        $formId = (int)$request->form->id;

        if ($formId < 1) {
            return null;
        }

        $since = (new \DateTimeImmutable("-{$minutes} minutes"))->format('Y-m-d H:i:s');
        $count = (int)(new Query())
            ->from(['s' => Table::FORMIE_SUBMISSIONS])
            ->innerJoin(
                ['e' => CraftTable::ELEMENTS],
                '[[e.id]] = [[s.id]] AND [[e.dateDeleted]] IS NULL',
            )
            ->where([
                's.formId' => $formId,
                's.ipAddress' => $ip,
            ])
            ->andWhere(['>', 's.dateCreated', $since])
            ->count('*', Craft::$app->getDb());

        if ($count > 0) {
            return Craft::t('formie', 'Too many submissions from this IP address.');
        }

        return null;
    }

    private function _requestUserIp(): string
    {
        $request = Craft::$app->getRequest();

        if (!method_exists($request, 'getUserIP')) {
            return '';
        }

        return (string)($request->getUserIP() ?? '');
    }

    private function _validateHoneypot(Settings $settings): ?string
    {
        $fieldName = $this->_normalizeFieldName($settings->honeypotFieldName);

        if ($fieldName === '') {
            return null;
        }

        $value = $this->_getBodyParam($fieldName);

        if ($value === null) {
            return Craft::t('formie', 'Honeypot param missing: {v}.', ['v' => $fieldName]);
        }

        if (trim((string)$value) !== '') {
            return Craft::t('formie', 'Honeypot input has value: {v}.', ['v' => $value]);
        }

        return null;
    }

    private function _validateMinimumSubmitTime(Settings $settings): ?string
    {
        $startedAt = $this->_getBodyParam(self::FORM_STARTED_AT_PARAM);

        if ($startedAt === null || !is_numeric($startedAt)) {
            return Craft::t('formie', 'Minimum submit time param missing.');
        }

        $elapsedSeconds = (microtime(true) * 1000 - (float)$startedAt) / 1000;
        $minimumSeconds = max(1, (int)$settings->minimumSubmitTime);

        if ($elapsedSeconds < $minimumSeconds) {
            return Craft::t('formie', 'Form submitted too quickly.');
        }

        return null;
    }

    private function _validateFormSubmitExpiration(Settings $settings): ?string
    {
        $startedAt = $this->_getBodyParam(self::FORM_STARTED_AT_PARAM);

        if ($startedAt === null || !is_numeric($startedAt)) {
            return null;
        }

        $elapsedSeconds = (microtime(true) * 1000 - (float)$startedAt) / 1000;
        $maxSeconds = max(1, (int)$settings->formSubmitExpiration);

        if ($elapsedSeconds > $maxSeconds) {
            return Craft::t('formie', 'Form session has expired.');
        }

        return null;
    }

    private function _validateReplayProtection(SubmissionRequest $request): ?string
    {
        $requestToken = trim((string)$request->requestToken);

        if ($requestToken === '') {
            return Craft::t('formie', 'Replay protection token missing.');
        }

        $formUid = (string)$request->form->uid;

        if ($this->isReplayTokenConsumed($formUid, $requestToken)) {
            return Craft::t('formie', 'Request token has already been used.');
        }

        return null;
    }

    private function _isBrowserFormSubmission(): bool
    {
        $request = Craft::$app->getRequest();

        if (!$request instanceof WebRequest) {
            return false;
        }

        if (!$request->getIsPost()) {
            return false;
        }

        $handle = $request->getBodyParam('handle');
        $submitAction = $request->getBodyParam('submitAction');

        return is_string($handle) && trim($handle) !== ''
            && is_string($submitAction) && trim($submitAction) !== '';
    }

    private function _getBodyParam(string $name): mixed
    {
        return Craft::$app->getRequest()->getBodyParam($name);
    }

    private function _normalizeFieldName(?string $fieldName): string
    {
        $fieldName = trim((string)$fieldName);

        if ($fieldName === '') {
            return 'formieHoneypot';
        }

        return $fieldName;
    }

    private function _replayCacheKey(string $formUid, string $requestToken): string
    {
        return sprintf(
            'formie.replay.%s.%s',
            $formUid,
            sha1($requestToken),
        );
    }
}
