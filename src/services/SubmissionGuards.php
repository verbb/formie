<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

use Craft;
use craft\base\Component;

class SubmissionGuards extends Component
{
    // Constants
    // =========================================================================

    public const REPLAY_CACHE_DURATION = 86400;

    public const FORM_STARTED_AT_PARAM = 'formStartedAt';


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
