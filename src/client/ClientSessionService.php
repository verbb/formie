<?php
namespace verbb\formie\client;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\client\models\FormSession;
use verbb\formie\client\models\PageTransitionRequest;
use verbb\formie\client\models\SessionRefreshRequest;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\state\DraftSubmissionState;

use Craft;

use yii\base\Component;
use yii\web\TooManyRequestsHttpException;

class ClientSessionService extends Component
{
    // Constants
    // =========================================================================

    private const RATE_SCOPE_BOOTSTRAP = 'bootstrap';
    private const RATE_SCOPE_REFRESH = 'refresh';
    private const RATE_SCOPE_PAGE = 'page';
    

    // Public Methods
    // =========================================================================

    public function issueInitialSession(Form $form, ?string $currentPageId = null, bool $enforceAbuseLimit = false, ?bool $includeProgressContinuation = null): FormSession
    {
        if ($enforceAbuseLimit) {
            $this->_enforceAnonymousClientRateLimit($form, self::RATE_SCOPE_BOOTSTRAP);
        }

        return $this->_buildSession($form, $currentPageId, $includeProgressContinuation);
    }

    public function refreshSession(SessionRefreshRequest $request, bool $enforceAbuseLimit = false): FormSession
    {
        $form = Formie::$plugin->getSubmissionProcessor()->requireFormByHandle($request->handle, $request->siteId);
        $currentPageId = (string)($request->session['currentPageId'] ?? ($form->getCurrentPage()?->id ?? ''));

        if ($enforceAbuseLimit) {
            $this->_enforceAnonymousClientRateLimit($form, self::RATE_SCOPE_REFRESH);
        }

        return $this->_buildSession($form, $currentPageId);
    }

    public function persistPageState(PageTransitionRequest $request, bool $enforceAbuseLimit = false): FormSession
    {
        $result = Formie::$plugin->getSubmissionProcessor()->execute(new \verbb\formie\client\models\SubmitRequest([
            'handle' => $request->handle,
            'siteId' => $request->siteId,
            'action' => 'back',
            'targetPageId' => $request->targetPageId ? (int)$request->targetPageId : null,
            'session' => $request->session,
            'values' => $request->values,
            'operationId' => $request->operationId,
        ]), \verbb\formie\enums\SubmissionAuthorityType::VISITOR);
        if (!$result->success) {
            throw new \yii\web\HttpException($result->httpStatus, 'Page state could not be updated.');
        }
        return $result->session;
    }


    public function enforceAnonymousRateLimit(Form $form, string $scope = self::RATE_SCOPE_REFRESH): void
    {
        $this->_enforceAnonymousClientRateLimit($form, $scope);
    }

    public function buildTokenPayload(Form $form, bool $enforceAbuseLimit = false): array
    {
        if ($enforceAbuseLimit) {
            $this->_enforceAnonymousClientRateLimit($form, self::RATE_SCOPE_REFRESH);
        }

        Craft::$app->getSession()->open();

        $request = Craft::$app->getRequest();
        $csrfToken = null;
        $csrfName = null;

        if (method_exists($request, 'getCsrfToken')) {
            $csrfToken = $request->getCsrfToken();
            $csrfName = $request->csrfParam ?? null;
        }

        $payload = array_filter([
            'csrf' => ($csrfToken && $csrfName) ? [
                'param' => $csrfName,
                'token' => $csrfToken,
            ] : null,
            'requestToken' => $form->getRequestToken(),
            'renderId' => $form->getRenderId(),
        ], static function($value) {
            return $value !== null && $value !== '';
        });
        $captchas = [];

        foreach (Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form, null, true) as $captcha) {
            if ($jsVariables = $captcha->getRefreshJsVariables($form)) {
                $captchas[$captcha->handle] = $jsVariables;
            }
        }

        if ($captchas) {
            $payload['captchas'] = $captchas;
        }

        return $payload;
    }


    // Private Methods
    // =========================================================================

    private function _buildSession(Form $form, ?string $currentPageId, ?bool $includeProgressContinuation = null): FormSession
    {
        $tokens = $this->buildTokenPayload($form);

        return new FormSession([
            'id' => (string)$form->getRenderId(),
            'version' => $form->getCurrentSubmission()?->stateVersion ?? 0,
            'currentPageId' => $currentPageId ?: (string)($form->getCurrentPage()?->id ?? ''),
            'tokens' => [
                'csrf' => isset($tokens['csrf']) ? [
                    'name' => $tokens['csrf']['param'],
                    'value' => $tokens['csrf']['token'],
                ] : null,
                'request' => $tokens['requestToken'] ?? null,
                'render' => $tokens['renderId'] ?? null,
                'captchas' => $tokens['captchas'] ?? [],
            ],
            'continuation' => $this->_buildContinuation($form, $includeProgressContinuation),
        ]);
    }

    private function _buildContinuation(Form $form, ?bool $includeProgressContinuation = null): ?array
    {
        $progressState = Formie::$plugin->getSubmissionDrafts()->getProgressState($form);
        // Default follows the form setting: automatic restore advertises leftover
        // progress on bootstrap; disabled forms only get a token when a caller
        // forces include (e.g. post-submit session rebuild for the next page).
        $includeProgressContinuation ??= $form->settings->automaticSubmissionState;
        $continuationToken = $includeProgressContinuation
            ? $this->_resolveContinuationToken($progressState)
            : null;
        $continuation = array_filter([
            'draftContext' => $form->getDraftContext(),
            'draftContextToken' => $form->getDraftContextToken(),
            'continuationToken' => $continuationToken,
        ], static function($value) {
            return $value !== null && $value !== '';
        });

        return $continuation ?: null;
    }

    private function _resolveContinuationToken(?DraftSubmissionState $progressState = null): ?string
    {
        if (!$progressState?->submissionId) {
            return null;
        }

        $submissionDrafts = Formie::$plugin->getSubmissionDrafts();
        $existingToken = is_string($progressState->resumeToken) ? trim($progressState->resumeToken) : '';

        if ($existingToken !== '' && $submissionDrafts->verifyResumeToken($existingToken, [
            SubmissionDrafts::RESUME_CAPABILITY_UPDATE,
        ])) {
            return $existingToken;
        }

        // Browser-managed flows only need an update-scoped token here. Reuse an
        // existing valid token when possible so refresh/bootstrap calls keep the
        // same continuation identity instead of rotating on every request.
        return $submissionDrafts->issueResumeToken($progressState, [
            SubmissionDrafts::RESUME_CAPABILITY_UPDATE,
        ])->token;
    }

    private function _enforceAnonymousClientRateLimit(Form $form, string $scope): void
    {
        $settings = Formie::$plugin->getSettings();
        $limit = match ($scope) {
            self::RATE_SCOPE_BOOTSTRAP => (int)$settings->anonymousClientBootstrapRateLimit,
            self::RATE_SCOPE_PAGE,
            self::RATE_SCOPE_REFRESH => (int)$settings->anonymousClientRefreshRateLimit,
            default => 0,
        };

        if ($limit < 1) {
            return;
        }

        $window = max(1, (int)$settings->anonymousClientRateWindowSeconds);
        $fingerprint = $this->_resolveClientRateLimitFingerprint($form, $scope);
        $cacheKey = 'formie.client-rate-limit.' . md5($fingerprint);
        $mutexKey = 'formie.client-rate-limit-lock.' . md5($fingerprint);
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $now = time();

        $lockAcquired = $mutex?->acquire($mutexKey, 3) ?? false;

        if (!$lockAcquired) {
            throw new TooManyRequestsHttpException('Please retry shortly.');
        }

        try {
            $entry = $cache->get($cacheKey);

            if (!is_array($entry) || !isset($entry['count'], $entry['resetAt']) || (int)$entry['resetAt'] <= $now) {
                $entry = [
                    'count' => 0,
                    'resetAt' => $now + $window,
                ];
            }

            $count = (int)$entry['count'];
            $resetAt = max($now + 1, (int)$entry['resetAt']);

            if ($count >= $limit) {
                $retryAfter = max(1, $resetAt - $now);
                Craft::$app->getResponse()->getHeaders()->set('Retry-After', (string)$retryAfter);

                throw new TooManyRequestsHttpException('Too many requests. Please try again shortly.');
            }

            $entry['count'] = $count + 1;
            $cache->set($cacheKey, $entry, max(1, $resetAt - $now));
        } finally {
            if ($lockAcquired) {
                $mutex?->release($mutexKey);
            }
        }
    }

    private function _resolveClientRateLimitFingerprint(Form $form, string $scope): string
    {
        $request = Craft::$app->getRequest();
        $ip = trim((string)($request->getUserIP() ?? 'unknown'));

        // The shared address budget must survive client/session header changes.
        return implode('|', [
            $scope,
            (string)$form->uid,
            $ip !== '' ? $ip : 'unknown',
        ]);
    }
}
