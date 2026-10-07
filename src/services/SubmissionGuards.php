<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\helpers\Table;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionCommand;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\Json;

use yii\web\ForbiddenHttpException;
use yii\web\TooManyRequestsHttpException;

use DateTimeImmutable;

class SubmissionGuards extends Component
{
    // Constants
    // =========================================================================

    public const REPLAY_CACHE_DURATION = 86400;

    public const FORM_STARTED_AT_PARAM = 'formStartedAt';

    public const GLOBAL_THROTTLE_CACHE_KEY = 'formie.global-submission-throttle';


    // Public Methods
    // =========================================================================

    public function issueRequestToken(Form $form): string
    {
        return Craft::$app->getSecurity()->hashData(Json::encode([
            'form' => $form->uid,
            'site' => $form->siteId,
            'issued' => time(),
            'nonce' => Craft::$app->getSecurity()->generateRandomString(),
            'config' => (new RuntimeConfiguration())->persistInstance($form),
        ]));
    }

    public function validateRequest(SubmissionCommand $request, bool $browser): ?string
    {
        if (!$request->isInteractive()) {
            return null;
        }

        $payload = Craft::$app->getSecurity()->validateData((string)$request->requestToken);
        $token = $payload === false ? null : Json::decodeIfJson($payload);

        if (!is_array($token) || ($token['form'] ?? null) !== $request->form->uid
            || (int)($token['site'] ?? 0) !== (int)$request->form->siteId
            || (int)($token['issued'] ?? 0) > time()
            || (int)($token['issued'] ?? 0) < time() - SubmissionOperations::RETENTION_SECONDS) {
            throw new ForbiddenHttpException('Invalid or expired submission request.');
        }

        Formie::$plugin->getClientSessionService()->enforceAnonymousRateLimit($request->form);
        $settings = Formie::$plugin->getSettings();

        if ($settings->enableGlobalSubmissionThrottling && ($reason = $this->_validateGlobalSubmissionThrottling($settings))) {
            throw new TooManyRequestsHttpException($reason);
        }

        if ($settings->enableIpSubmissionThrottling && ($reason = $this->_validateIpSubmissionThrottling($settings, $request))) {
            throw new TooManyRequestsHttpException($reason);
        }

        // Browser honeypots protect every write. Minimum elapsed time applies to
        // forward Submit only; saving/back navigation remains usable immediately.
        if ($browser && $settings->enableHoneypot && ($reason = $this->_validateHoneypot($settings))) {
            return $reason;
        }

        if ($browser && $request->operation === SubmissionOperation::SUBMIT
            && $request->navigation === NavigationIntent::ADVANCE
            && $settings->enableMinimumSubmitTime && ($reason = $this->_validateMinimumSubmitTime($settings))) {
            return $reason;
        }

        if ($browser && $settings->enableFormSubmitExpiration) {
            return $this->_validateFormSubmitExpiration($settings);
        }
        return null;
    }

    public function isReplayTokenConsumed(string $formUid, string $requestToken): bool
    {
        $cacheKey = $this->_replayCacheKey($formUid, $requestToken);

        return Craft::$app->getCache()->get($cacheKey) !== false;
    }

    /**
     * Add a replay marker if it does not already exist. Workflow execution
     * is serialized separately and consumes this marker at finalization.
     * Returns false when the token was already claimed/consumed.
     */
    public function claimReplayToken(string $formUid, string $requestToken): bool
    {
        $requestToken = trim($requestToken);

        if ($requestToken === '') {
            return false;
        }

        return Craft::$app->getCache()->add(
            $this->_replayCacheKey($formUid, $requestToken),
            true,
            self::REPLAY_CACHE_DURATION,
        );
    }

    public function consumeReplayToken(string $formUid, string $requestToken): void
    {
        $requestToken = trim($requestToken);

        if ($requestToken === '') {
            return;
        }

        // Refresh the expiry if this token has already been consumed.
        if ($this->claimReplayToken($formUid, $requestToken)) {
            return;
        }

        Craft::$app->getCache()->set(
            $this->_replayCacheKey($formUid, $requestToken),
            true,
            self::REPLAY_CACHE_DURATION,
        );
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

        if (!$lockAcquired) {
            throw new TooManyRequestsHttpException('Please retry shortly.');
        }

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

    private function _validateIpSubmissionThrottling(Settings $settings, SubmissionCommand $request): ?string
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

        $since = (new DateTimeImmutable("-{$minutes} minutes"))->format('Y-m-d H:i:s');
        $query = (new Query())
            ->from(['s' => Table::FORMIE_SUBMISSIONS])
            ->innerJoin(
                ['e' => CraftTable::ELEMENTS],
                '[[e.id]] = [[s.id]] AND [[e.dateDeleted]] IS NULL',
            )
            ->where([
                's.formId' => $formId,
                's.ipAddress' => $ip,
            ])
            ->andWhere(['>', 's.dateCreated', $since]);

        // Continuing a saved page or payment must not throttle the submission against itself.
        if ($request->submission->id) {
            $query->andWhere(['not', ['s.id' => $request->submission->id]]);
        }

        $count = (int)$query->count('*', Craft::$app->getDb());

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
