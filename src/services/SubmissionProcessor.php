<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\StaleSubmissionStateException;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\SubmissionExecutionResult;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\models\SubmissionResponse;
use verbb\formie\client\models\SubmitRequest;
use verbb\formie\client\models\SubmitResult;
use verbb\formie\state\DraftSubmissionState;

use Craft;
use craft\base\Element;
use craft\helpers\UrlHelper;

use yii\base\Component;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class SubmissionProcessor extends Component
{
    // Public Methods
    // =========================================================================

    public function execute(SubmitRequest $request): SubmitResult
    {
        $form = $this->requireFormByHandle($request->handle, $request->siteId);
        Formie::$plugin->getClientSessionService()->enforceAnonymousRateLimit($form);
        $this->applyFormRequestContext(
            $form,
            $request->session['tokens']['render'] ?? null,
            $request->session['continuation']['draftContext'] ?? null,
            $request->session['tokens']['request'] ?? null,
        );

        $progressState = $this->resolveProgressState($form);
        $submission = $this->resolveClientContinuationSubmission(
            $form,
            $progressState,
            (array)($request->session['continuation'] ?? [])
        );
        $submission ??= new Submission();
        $this->primeSubmission($submission, $form, $progressState, $request->siteId);
        $submission->setFieldValues($request->values);
        $submissionRequest = $this->createComponentSubmissionRequest($request, $form, $submission, $progressState);
        $response = $this->runSubmissionRequest($submissionRequest);
        $this->persistProgressState($submissionRequest, $response);

        return $this->_buildClientResult($submissionRequest, $response, $request);
    }

    public function executeManaged(ManagedSubmissionRequest $request): SubmissionExecutionResult
    {
        $form = $this->requireFormByHandle($request->handle, $request->siteId);

        if (Craft::$app->getRequest()->getIsSiteRequest() && Craft::$app->getUser()->isGuest) {
            Formie::$plugin->getClientSessionService()->enforceAnonymousRateLimit($form);
        }

        $draftContext = $request->draftContextToken
            ? $form->resolveDraftContextToken($request->draftContextToken)
            : $request->draftContext;
        $isIncomplete = $request->processMode === SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING ? null : true;

        $this->applyFormRequestContext(
            $form,
            $request->renderId,
            $draftContext,
            $request->requestToken,
        );

        $progressState = $this->resolveProgressState($form);
        $submission = $this->resolveManagedContinuationSubmission(
            $form,
            $progressState,
            $request->submissionId,
            $request->resumeToken,
            $isIncomplete,
        );
        $submission ??= new Submission();

        $this->enforceManagedSiteSubmissionAuthorization($form, $request, $progressState, $submission);
        $this->primeSubmission($submission, $form, $progressState, $request->siteId);
        $submission->setFieldValuesFromRequest($request->fieldParamNamespace);
        $submission->setFieldParamNamespace($request->fieldParamNamespace);
        Formie::$plugin->getSubmissions()->applyCpRequestAttributes($submission);

        if ($request->userId !== null) {
            $submission->userId = $request->userId;
        }

        $submissionRequest = $this->createManagedSubmissionRequest($request, $form, $submission, $progressState, $draftContext);
        $response = $this->runSubmissionRequest($submissionRequest);
        $this->persistProgressState($submissionRequest, $response);

        return new SubmissionExecutionResult([
            'submissionRequest' => $submissionRequest,
            'response' => $response,
        ]);
    }

    public function executeMutation(Form $form, Submission $submission, array $arguments): SubmissionExecutionResult
    {
        $submissionRequest = $this->createMutationSubmissionRequest($form, $submission, $arguments);

        $this->applyFormRequestContext(
            $form,
            null,
            null,
            $submissionRequest->requestToken,
        );
        $this->primeSubmission(
            $submission,
            $form,
            null,
            $submissionRequest->siteId ?? $submission->siteId
        );

        return new SubmissionExecutionResult([
            'submissionRequest' => $submissionRequest,
            'response' => $this->runSubmissionRequest($submissionRequest),
        ]);
    }

    public function executePaymentReplay(PaymentModel $payment): SubmissionExecutionResult
    {
        $submission = $payment->getSubmission();

        if (!$submission) {
            throw new BadRequestHttpException('Unable to find submission for payment replay.');
        }

        $form = $submission->getForm();

        if (!$form) {
            throw new BadRequestHttpException('Unable to find form for payment replay.');
        }

        $requestToken = $this->_createPaymentReplayRequestToken($payment);

        $this->applyFormRequestContext($form, null, null, $requestToken);
        $this->primeSubmission($submission, $form, null, $submission->siteId);

        if ($defaultStatus = $form->getDefaultStatus()) {
            $submission->setStatus($defaultStatus);
        }

        $submission->setScenario(Element::SCENARIO_LIVE);
        $submission->validateCurrentPageOnly = false;

        $submissionRequest = new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'siteId' => $submission->siteId,
            'requestToken' => $requestToken,
        ]);

        $response = $this->runSubmissionRequest($submissionRequest);
        $this->persistProgressState($submissionRequest, $response);

        return new SubmissionExecutionResult([
            'submissionRequest' => $submissionRequest,
            'response' => $response,
        ]);
    }

    public function replayPaymentIfSuccessful(PaymentModel $payment): ?SubmissionExecutionResult
    {
        if ($payment->status !== PaymentModel::STATUS_SUCCESS) {
            return null;
        }

        $submission = $payment->getSubmission();

        if (!$submission || !$submission->isIncomplete) {
            return null;
        }

        return $this->executePaymentReplay($payment);
    }

    public function requireFormByHandle(string $handle, ?int $siteId = null): Form
    {
        $form = Formie::$plugin->getForms()->getFormByHandle($handle, $siteId);

        if (!$form) {
            throw new BadRequestHttpException('Form not found');
        }

        return $form;
    }

    public function resolveProgressState(Form $form): ?DraftSubmissionState
    {
        return Formie::$plugin->getSubmissionDrafts()->getProgressState($form);
    }

    public function applyFormRequestContext(Form $form, ?string $renderId = null, ?string $draftContext = null, ?string $requestToken = null): void
    {
        if (is_string($renderId) && trim($renderId) !== '') {
            $form->setRenderId(trim($renderId));
        }

        if (is_string($draftContext) && trim($draftContext) !== '') {
            $form->setDraftContext(trim($draftContext));
        }

        if (is_string($requestToken) && trim($requestToken) !== '') {
            $form->setRequestToken(trim($requestToken));
        }
    }

    public function resolveContinuationSubmission(Form $form, ?DraftSubmissionState $progressState = null, ?string $submissionUid = null, ?bool $isIncomplete = true): ?Submission
    {
        if ($progressState?->submissionId) {
            $submission = $this->_findSubmissionById((int)$progressState->submissionId, $isIncomplete, (int)$form->id);

            if ($submission) {
                return $submission;
            }
        }

        return null;
    }

    public function resolveClientContinuationSubmission(
        Form $form,
        ?DraftSubmissionState $progressState = null,
        array $continuation = [],
        ?bool $isIncomplete = true
    ): ?Submission {
        if ($progressState?->submissionId) {
            $submission = $this->_findSubmissionById((int)$progressState->submissionId, $isIncomplete, (int)$form->id);

            if ($submission) {
                return $submission;
            }
        }

        $submissionId = $this->_resolveSubmissionIdFromContinuationToken($form, $continuation);

        if (!$submissionId) {
            return null;
        }

        return $this->_findSubmissionById($submissionId, $isIncomplete, (int)$form->id);
    }

    public function primeSubmission(Submission $submission, Form $form, ?DraftSubmissionState $progressState = null, ?int $siteId = null): void
    {
        $submission->setForm($form);
        $submission->siteId = $siteId ?? $submission->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;

        if (is_array($progressState?->content) && $progressState->content) {
            $submission->getContentManager()->normalizeFromDb($submission, $progressState->content);
        }

        if (!$submission->id && $form->settings->collectIp) {
            $submission->ipAddress = Craft::$app->getRequest()->userIP;
        }

        if ($form->settings->collectUser && !$submission->userId && ($user = Craft::$app->getUser()->getIdentity())) {
            $submission->setUser($user);
        }
    }

    public function createSaveResumePayload(Form $form, Submission $submission, string $baseUrl): array
    {
        $submissionDrafts = Formie::$plugin->getSubmissionDrafts();
        $draftState = $submissionDrafts->upsertProgressState($form, $submission, $form->getCurrentPage()?->id);

        if (!$draftState) {
            return [];
        }

        $resumeToken = $submissionDrafts->issueResumeToken($draftState, [
            SubmissionDrafts::RESUME_CAPABILITY_READ,
            SubmissionDrafts::RESUME_CAPABILITY_UPDATE,
        ]);

        return [
            'resumeToken' => $resumeToken->token,
            'resumeUrl' => UrlHelper::urlWithParams($baseUrl, [
                'resumeToken' => $resumeToken->token,
            ]),
            'resumeTokenExpiresAt' => $resumeToken->expiresAt,
        ];
    }

    public function resolveTrustedResumeBaseUrl(?string $candidateUrl, string $fallbackPath): string
    {
        $fallbackUrl = UrlHelper::siteUrl(trim($fallbackPath, '/'));
        $candidateUrl = trim((string)$candidateUrl);

        if ($candidateUrl === '' || !$this->_isSameOriginUrl($candidateUrl, $fallbackUrl)) {
            return $fallbackUrl;
        }

        return $candidateUrl;
    }


    // Private Methods
    // =========================================================================

    private function createManagedSubmissionRequest(
        ManagedSubmissionRequest $request,
        Form $form,
        Submission $submission,
        ?DraftSubmissionState $progressState,
        ?string $draftContext
    ): SubmissionRequest {
        $submitAction = $this->_normalizeManagedSubmitAction($request->submitAction);
        $processMode = $request->processMode;

        if ($processMode === SubmissionWorkflow::PROCESS_MODE_SUBMIT && $submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE) {
            // Managed clients can express "save" as a submit-style request. Fold
            // that into the explicit save-draft mode before the workflow sees it
            // so downstream stage policies remain unambiguous.
            $processMode = SubmissionWorkflow::PROCESS_MODE_SAVE_DRAFT;
        }

        return new SubmissionRequest([
            'processMode' => $processMode,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => $submitAction,
            'siteId' => $this->_normalizeNullableInt($request->siteId),
            'pageId' => $this->_normalizeNullableInt($request->pageId)
                ?? $this->_normalizeNullableInt($progressState?->currentPageId),
            'targetPageId' => $this->_normalizeNullableInt($request->targetPageId),
            'requestToken' => $this->_normalizeNullableString($request->requestToken),
            'draftContext' => $this->_normalizeNullableString($draftContext),
            'clearConditionallyHiddenFields' => Craft::$app->getRequest()->getIsCpRequest()
                && $form->cpSubmissionFollowsFieldConditions(),
        ]);
    }

    private function createComponentSubmissionRequest(
        SubmitRequest $clientRequest,
        Form $form,
        Submission $submission,
        ?DraftSubmissionState $progressState = null
    ): SubmissionRequest {
        $submitAction = $this->_normalizeComponentSubmitAction($clientRequest->action);

        return new SubmissionRequest([
            'processMode' => $submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE
                ? SubmissionWorkflow::PROCESS_MODE_SAVE_DRAFT
                : SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => $submitAction,
            'siteId' => $this->_normalizeNullableInt($clientRequest->siteId),
            'pageId' => $this->_normalizeNullableInt($clientRequest->session['currentPageId'] ?? null)
                ?? $this->_normalizeNullableInt($progressState?->currentPageId),
            'targetPageId' => null,
            'requestToken' => $this->_normalizeNullableString($clientRequest->session['tokens']['request'] ?? null),
            'draftContext' => $this->_normalizeNullableString($clientRequest->session['continuation']['draftContext'] ?? null),
            'clearConditionallyHiddenFields' => true,
        ]);
    }

    private function createMutationSubmissionRequest(Form $form, Submission $submission, array $arguments): SubmissionRequest
    {
        return new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'siteId' => $this->_normalizeNullableInt($arguments['siteId'] ?? null),
            'requestToken' => $this->_normalizeNullableString($arguments['requestToken'] ?? null),
        ]);
    }

    private function runSubmissionRequest(SubmissionRequest $request): SubmissionResponse
    {
        $response = Formie::$plugin->getSubmissionWorkflow()->processSubmissionRequest($request);
        $submission = $response->submission ?? $request->submission;
        $form = $response->form ?? $request->form;

        if (!$response->success && $submission->hasErrors() && !$submission->hasErrors('form')) {
            $submission->addError('form', $form->settings->getErrorMessage());
        }

        return $response;
    }

    private function persistProgressState(SubmissionRequest $request, SubmissionResponse $response): void
    {
        if (!$response->success) {
            // Preserve the last known resume state on validation or workflow
            // failures; clearing it here would strand multi-page drafts after a
            // recoverable error.
            return;
        }

        $submissionDrafts = Formie::$plugin->getSubmissionDrafts();

        if ($request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE) {
            $submissionDrafts->upsertProgressState($request->form, $request->submission, $request->form->getCurrentPage()?->id);

            return;
        }

        if ($response->nextPage) {
            $submissionDrafts->upsertProgressState($request->form, $request->submission, $response->nextPage->id);

            return;
        }

        $submissionDrafts->clearProgressState($request->form);
    }

    private function resolveManagedContinuationSubmission(
        Form $form,
        ?DraftSubmissionState $progressState = null,
        ?int $submissionId = null,
        ?string $resumeToken = null,
        ?bool $isIncomplete = true
    ): ?Submission {
        $submissionId = $this->_normalizeNullableInt($submissionId)
            ?? ($progressState?->submissionId ? (int)$progressState->submissionId : null)
            ?? $this->_resolveSubmissionIdFromResumeToken($form, $resumeToken);

        if ($submissionId) {
            $submission = $this->_findSubmissionById($submissionId, $isIncomplete, (int)$form->id);

            if (!$submission) {
                // Managed resume tokens are expected to point at a specific
                // draft. Starting a fresh submission instead would silently
                // discard the caller's continuation state.
                throw new StaleSubmissionStateException($form, 'submissionId', (string)$submissionId);
            }

            return $submission;
        }

        return null;
    }

    private function enforceManagedSiteSubmissionAuthorization(
        Form $form,
        ManagedSubmissionRequest $request,
        ?DraftSubmissionState $progressState,
        Submission $submission
    ): void {
        if (!Craft::$app->getRequest()->getIsSiteRequest() || !$submission->id) {
            return;
        }

        if ($request->processMode === SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING) {
            if (!$this->_validateSubmissionEditToken($form, $submission, $request->submissionEditToken)) {
                throw new ForbiddenHttpException('User is not permitted to perform this action');
            }

            return;
        }

        $progressSubmissionId = $progressState?->submissionId ? (int)$progressState->submissionId : null;

        if ($progressSubmissionId === (int)$submission->id) {
            return;
        }

        if ($this->_validateSubmissionUpdateToken($form, $submission, $request->resumeToken)) {
            return;
        }

        throw new ForbiddenHttpException('User is not permitted to perform this action');
    }

    private function _buildClientResult(SubmissionRequest $submissionRequest, SubmissionResponse $response, SubmitRequest $request): SubmitResult
    {
        $form = $submissionRequest->form;
        $submission = $submissionRequest->submission;
        $submitAction = $submissionRequest->submitAction;
        $fieldIdByHandle = [];

        foreach ($form->getFields() as $field) {
            $fieldIdByHandle[$field->handle] = (string)$field->id;
        }

        $rawErrors = $submission->getErrors();
        $fieldErrors = [];

        foreach ($rawErrors as $key => $messages) {
            if ($key === 'form') {
                continue;
            }

            // The client addresses fields by builder IDs, but server-side
            // validation keys are handle-based (and may include nested paths).
            // Rewrite them here so the client can attach messages to the same
            // field instances the user is editing.
            $segments = explode('.', (string)$key);
            $topLevelHandle = array_shift($segments) ?: '';
            $resolvedKey = $fieldIdByHandle[$topLevelHandle] ?? $topLevelHandle ?: $key;

            if ($segments) {
                $resolvedKey .= '.' . implode('.', $segments);
            }

            $fieldErrors[$resolvedKey] = StringHelper::sanitizeMessageHtmlRecursive($messages);
        }

        $currentPage = $response->nextPage ?: $this->_resolvePageById($form, $request->session['currentPageId'] ?? null) ?: $form->getCurrentPage();
        $previousPage = $currentPage ? $form->getPreviousPage($currentPage, $submission) : null;
        $nextPageId = $response->nextPage?->id ? (string)$response->nextPage->id : null;
        $currentPageId = $currentPage?->id ? (string)$currentPage->id : null;

        $session = Formie::$plugin->getClientSessionService()->issueInitialSession($form, $currentPageId);
        $session->continuation = array_filter([
            ...($session->continuation ?? []),
            'draftContext' => $request->session['continuation']['draftContext'] ?? ($session->continuation['draftContext'] ?? null),
            'draftContextToken' => $request->session['continuation']['draftContextToken'] ?? ($session->continuation['draftContextToken'] ?? null),
        ], static function($value) {
            return $value !== null && $value !== '';
        }) ?: null;

        $notice = null;
        $error = null;

        if ($response->success && $nextPageId === null) {
            $notice = $form->settings->submitAction === 'message'
                ? StringHelper::sanitizeMessageHtml($form->settings->getSubmitActionMessage($submission))
                : StringHelper::sanitizeMessageHtml(Craft::t('formie', 'Form submitted successfully.'));
        }

        if ($submitAction === SubmissionWorkflow::SUBMIT_ACTION_SAVE && $response->success) {
            $notice = StringHelper::sanitizeMessageHtml($form->settings->getSubmitActionMessage($submission));
        }

        if (!$response->success) {
            $errorMessages = $rawErrors['form'] ?? [];
            $error = $errorMessages
                ? StringHelper::sanitizeMessageHtml(implode(' ', $errorMessages))
                : StringHelper::sanitizeMessageHtml($form->settings->getErrorMessage());
            $rawErrors['form'] = StringHelper::sanitizeMessageHtmlRecursive($rawErrors['form'] ?? []);
        }

        return new SubmitResult([
            'success' => $response->success,
            'submissionUid' => $submission->uid ?: null,
            'currentPageId' => $currentPageId,
            'nextPageId' => $nextPageId,
            'previousPageId' => $previousPage?->id ? (string)$previousPage->id : null,
            'isFinalPage' => $nextPageId === null,
            'errors' => [
                'form' => $rawErrors['form'] ?? [],
                'fields' => $fieldErrors,
                'pages' => [],
            ],
            'messages' => [
                'notice' => $notice,
                'error' => $error,
            ],
            'session' => $session,
        ]);
    }

    private function _resolveSubmissionIdFromResumeToken(Form $form, ?string $resumeToken, array $capabilities = [SubmissionDrafts::RESUME_CAPABILITY_UPDATE]): ?int
    {
        if (!is_string($resumeToken) || trim($resumeToken) === '') {
            return null;
        }

        $verifiedResumeToken = Formie::$plugin->getSubmissionDrafts()->verifyResumeToken(trim($resumeToken), $capabilities);

        if (!$verifiedResumeToken || $verifiedResumeToken->formId !== (int)$form->id || !$verifiedResumeToken->submissionId) {
            throw new BadRequestHttpException('Invalid or expired resume token.');
        }

        return (int)$verifiedResumeToken->submissionId;
    }

    private function _resolveSubmissionIdFromContinuationToken(Form $form, array $continuation = []): ?int
    {
        $token = $continuation['continuationToken'] ?? ($continuation['resumeToken'] ?? null);

        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        $verifiedToken = Formie::$plugin->getSubmissionDrafts()->verifyResumeToken(trim($token), [
            SubmissionDrafts::RESUME_CAPABILITY_UPDATE,
        ]);

        if (!$verifiedToken || $verifiedToken->formId !== (int)$form->id || !$verifiedToken->submissionId) {
            return null;
        }

        return (int)$verifiedToken->submissionId;
    }

    private function _validateSubmissionEditToken(Form $form, Submission $submission, ?string $token): bool
    {
        if (!is_string($token) || trim($token) === '') {
            return false;
        }

        $verifiedToken = Formie::$plugin->getSubmissionDrafts()->verifyResumeToken(trim($token), [
            SubmissionDrafts::RESUME_CAPABILITY_EDIT,
        ]);

        return $verifiedToken !== null &&
            (int)$verifiedToken->formId === (int)$form->id &&
            (int)$verifiedToken->submissionId === (int)$submission->id;
    }

    private function _validateSubmissionUpdateToken(Form $form, Submission $submission, ?string $token): bool
    {
        if (!is_string($token) || trim($token) === '') {
            return false;
        }

        $verifiedToken = Formie::$plugin->getSubmissionDrafts()->verifyResumeToken(trim($token), [
            SubmissionDrafts::RESUME_CAPABILITY_UPDATE,
        ]);

        return $verifiedToken !== null &&
            (int)$verifiedToken->formId === (int)$form->id &&
            (int)$verifiedToken->submissionId === (int)$submission->id;
    }

    private function _isSameOriginUrl(string $candidateUrl, string $referenceUrl): bool
    {
        $candidateParts = parse_url($candidateUrl);
        $referenceParts = parse_url($referenceUrl);

        if (!is_array($candidateParts) || !is_array($referenceParts)) {
            return false;
        }

        $candidateHost = strtolower((string)($candidateParts['host'] ?? ''));
        $referenceHost = strtolower((string)($referenceParts['host'] ?? ''));
        $candidateScheme = strtolower((string)($candidateParts['scheme'] ?? ''));
        $referenceScheme = strtolower((string)($referenceParts['scheme'] ?? ''));
        $candidatePort = isset($candidateParts['port']) ? (int)$candidateParts['port'] : null;
        $referencePort = isset($referenceParts['port']) ? (int)$referenceParts['port'] : null;

        return $candidateHost !== ''
            && $candidateHost === $referenceHost
            && $candidateScheme !== ''
            && $candidateScheme === $referenceScheme
            && $candidatePort === $referencePort;
    }

    private function _findSubmissionById(?int $submissionId, ?bool $isIncomplete = true, ?int $formId = null): ?Submission
    {
        if (!$submissionId) {
            return null;
        }

        $query = Submission::find()
            ->id($submissionId)
            ->isIncomplete($isIncomplete)
            ->isSpam(null);

        if ($formId !== null && $formId > 0) {
            $query->formId($formId);
        }

        return $query->one();
    }

    private function _findSubmissionByUid(?string $submissionUid, ?bool $isIncomplete = true, ?int $formId = null): ?Submission
    {
        if (!is_string($submissionUid) || trim($submissionUid) === '') {
            return null;
        }

        $query = Submission::find()
            ->uid(trim($submissionUid))
            ->isIncomplete($isIncomplete)
            ->isSpam(null);

        if ($formId !== null && $formId > 0) {
            $query->formId($formId);
        }

        return $query->one();
    }

    private function _normalizeManagedSubmitAction(mixed $action): string
    {
        $normalizedAction = $this->_normalizeNullableString($action);

        if ($normalizedAction && in_array($normalizedAction, SubmissionWorkflow::getAllowedSubmitActions(), true)) {
            return $normalizedAction;
        }

        return SubmissionWorkflow::SUBMIT_ACTION_SUBMIT;
    }

    private function _normalizeComponentSubmitAction(mixed $action): string
    {
        return match ($this->_normalizeNullableString($action)) {
            SubmissionWorkflow::SUBMIT_ACTION_BACK => SubmissionWorkflow::SUBMIT_ACTION_BACK,
            SubmissionWorkflow::SUBMIT_ACTION_SAVE => SubmissionWorkflow::SUBMIT_ACTION_SAVE,
            default => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        };
    }

    private function _normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }

    private function _normalizeNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function _createPaymentReplayRequestToken(PaymentModel $payment): string
    {
        $suffix = $payment->reference ?: $payment->uid ?: (string)$payment->id;

        return "payment-replay:{$suffix}";
    }

    private function _resolvePageById(Form $form, mixed $pageId): ?FieldLayoutPage
    {
        if (!$pageId) {
            return null;
        }

        foreach ($form->getPages() as $page) {
            if ((string)$page->id === (string)$pageId) {
                return $page;
            }
        }

        return null;
    }
}
