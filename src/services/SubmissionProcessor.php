<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\client\models\SubmitRequest;
use verbb\formie\client\models\SubmitResult;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\enums\SubmissionPolicy;
use verbb\formie\errors\StateConflict;
use verbb\formie\errors\SubmissionUnavailableException;
use verbb\formie\helpers\ClientEventsHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\Settings;
use verbb\formie\models\SubmissionAuthority;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionExecutionResult;
use verbb\formie\models\SubmissionOutcome;
use verbb\formie\models\SubmissionProgress as ProgressState;
use verbb\formie\models\SubmissionResponse;

use Craft;
use craft\helpers\UrlHelper;

use yii\base\Component;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class SubmissionProcessor extends Component
{
    // Public Methods
    // =========================================================================

    public function execute(SubmitRequest $input, SubmissionAuthorityType $authorityType): SubmitResult
    {
        try {
            return $this->_executeClient($input, $authorityType);
        } catch (\yii\web\HttpException $exception) {
            if (!in_array($exception->statusCode, [403, 429], true)) {
                throw $exception;
            }
            return SubmitResult::rejection($exception->statusCode);
        }
    }

    public function executeManaged(ManagedSubmissionRequest $input, SubmissionAuthorityType $authorityType): SubmissionExecutionResult
    {
        if (!in_array($authorityType, [SubmissionAuthorityType::VISITOR, SubmissionAuthorityType::CONTROL_PANEL], true)) {
            throw new ForbiddenHttpException('Unsupported managed submission authority.');
        }
        $form = $this->requireFormByHandle($input->handle, $input->siteId);
        $draftContext = $input->draftContextToken ? $form->resolveDraftContextToken($input->draftContextToken) : $input->draftContext;
        $this->applyFormRequestContext($form, $input->renderId, $draftContext, $input->requestToken);
        $progress = $authorityType === SubmissionAuthorityType::VISITOR ? $this->resolveProgressState($form) : null;
        $submission = $this->_resolveManagedContinuationSubmission($form, $progress, $input->submissionId, $input->resumeToken, $input->submissionUid, $input->operation === SubmissionOperation::REVISE ? null : true) ?? new Submission();
        $submission->setForm($form);
        $operation = $input->operation;

        if ($authorityType === SubmissionAuthorityType::CONTROL_PANEL) {
            $this->_requireCpPermission($form, $submission);
            $operation = $submission->id ? SubmissionOperation::REVISE : SubmissionOperation::SUBMIT;
        } else {
            $this->_authorizeVisitor($form, $input, $progress, $submission);
            if ($operation === SubmissionOperation::REVISE && $submission->isIncomplete) {
                $operation = SubmissionOperation::SUBMIT;
            }
        }
        if ($operation === SubmissionOperation::SUBMIT && $input->submitAction === 'save') {
            $operation = SubmissionOperation::SAVE_DRAFT;
        }
        $policy = $authorityType === SubmissionAuthorityType::CONTROL_PANEL && !$submission->id
            ? SubmissionPolicy::ADMINISTRATIVE_CREATE : SubmissionPolicy::STANDARD;
        $navigation = $authorityType === SubmissionAuthorityType::CONTROL_PANEL || $operation === SubmissionOperation::REVISE
            ? NavigationIntent::STAY : $this->_navigation($input->submitAction, $input->targetPageId);
        $body = (array)Craft::$app->getRequest()->getBodyParams();
        unset($body[Craft::$app->getRequest()->csrfParam], $body['requestToken']);
        $body['_uploadedFiles'] = $this->_uploadedFileFingerprint();
        $body['_target'] = [$input->submissionId, $input->submissionUid, $input->resumeToken, $input->submissionEditToken];
        return $this->_executeResolved(
            $form, $submission, $operation, $navigation, $authorityType, $input->expectedVersion ?? ($submission->id ? null : 0),
            $input->operationId ?? $input->requestToken, $input->requestToken,
            $body + ['operation' => $operation->value, 'page' => $input->pageId, 'target' => $input->targetPageId],
            function () use ($submission, $form, $progress, $input, $authorityType, $navigation): void {
                $this->primeSubmission($submission, $form, $progress, $input->siteId);
                if ($navigation !== NavigationIntent::BACK || Formie::$plugin->getSettings()->enableBackSubmission) {
                    $submission->setFieldValuesFromRequest($input->fieldParamNamespace);
                }
                $submission->setFieldParamNamespace($input->fieldParamNamespace);
                if ($authorityType === SubmissionAuthorityType::CONTROL_PANEL) {
                    Formie::$plugin->getSubmissions()->applyCpRequestAttributes($submission);
                    if ($input->userId !== null) {
                        $submission->userId = $input->userId;
                    }
                }
            },
            $input->pageId ?? $progress?->currentPageId, $input->targetPageId,
            $policy, true,
        );
    }

    public function executeMutation(Form $form, Submission $submission, array $arguments, callable $populate): SubmissionExecutionResult
    {
        $permission = $submission->id ? 'save' : 'create';
        if (!\craft\helpers\Gql::canSchema('formieSubmissions.all', $permission) && !\craft\helpers\Gql::canSchema('formieSubmissions.' . $form->uid, $permission)) {
            throw new ForbiddenHttpException('Unable to perform the action.');
        }
        $submission->setForm($form);
        return $this->_executeResolved(
            $form, $submission, $submission->id ? SubmissionOperation::REVISE : SubmissionOperation::SUBMIT,
            $submission->id ? NavigationIntent::STAY : NavigationIntent::ADVANCE, SubmissionAuthorityType::GRAPHQL_ADMIN,
            isset($arguments['expectedVersion']) ? (int)$arguments['expectedVersion'] : ($submission->id ? null : 0),
            $arguments['operationId'] ?? $arguments['requestToken'] ?? null, null, $arguments, $populate,
            $submission->id ? null : (int)$form->getPages()[array_key_last($form->getPages())]->id,
        );
    }

    public function executePaymentReplay(PaymentModel $payment): SubmissionExecutionResult
    {
        $submission = $payment->getSubmission();
        $form = $submission?->getForm();
        if (!$submission || !$form) {
            throw new BadRequestHttpException('Unable to resolve the payment submission.');
        }
        // The caller is a verified provider/domain adapter. Never populate from browser input or progress.
        return $this->_executeResolved(
            $form, $submission, SubmissionOperation::PAYMENT_REPLAY, NavigationIntent::STAY,
            SubmissionAuthorityType::PAYMENT_REPLAY, $submission->stateVersion,
            $this->_createPaymentReplayRequestToken($payment), null,
            ['payment' => $payment->id, 'status' => $payment->status, 'reference' => $payment->reference], static function (): void {},
        );
    }

    public function executeCommand(SubmissionCommand $command): SubmissionOutcome
    {
        if ($command->authority->type !== SubmissionAuthorityType::TRUSTED_INTERNAL) {
            throw new ForbiddenHttpException('Use the transport-specific boundary for external commands.');
        }
        return Formie::$plugin->getSubmissionOperations()->execute($command, fn() => Formie::$plugin->getSubmissionWorkflow()->process($command));
    }

    public function replayPaymentIfSuccessful(PaymentModel $payment): ?SubmissionExecutionResult
    {
        if ($payment->status !== PaymentModel::STATUS_SUCCESS && ($payment->scope['providerOutcome']['status'] ?? null) !== PaymentModel::STATUS_SUCCESS) {
            return null;
        }

        $submission = $payment->getSubmission();

        if (!$submission || !$submission->isIncomplete) {
            return null;
        }

        return $this->executePaymentReplay($payment);
    }

    public function exchangeGrant(Form $form, string $token, string $purpose): Submission
    {
        $grant = Formie::$plugin->getSubmissionGrants()->exchange($token, $purpose, $form);
        $submission = $grant ? $this->_findSubmissionById($grant->submissionId, $purpose === SubmissionGrants::CONTINUE, (int)$form->id) : null;
        if (!$submission) {
            throw new ForbiddenHttpException('Submission is unavailable.');
        }
        if ($purpose === SubmissionGrants::REVISE) {
            $form->setSubmission($submission);
        } else {
            $form->setCurrentSubmission($submission);
            $progress = $grant->progressId ? Formie::$plugin->getSubmissionProgress()->loadProgress($grant->progressId) : null;
            if ($progress?->currentPageId) {
                $form->setCurrentPage($this->_resolvePageById($form, $progress->currentPageId));
            }
        }
        return $submission;
    }

    public function requireFormByHandle(string $handle, ?int $siteId = null): Form
    {
        $form = Formie::$plugin->getForms()->getFormByHandle($handle, $siteId);

        if (!$form) {
            throw new BadRequestHttpException('Form not found');
        }

        return clone $form;
    }

    public function resolveProgressState(Form $form): ?ProgressState
    {
        return Formie::$plugin->getSubmissionProgress()->getProgressState($form);
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
            (new RuntimeConfiguration())->restoreToken($form, trim($requestToken));
        }
    }

    public function resolveContinuationSubmission(Form $form, ?ProgressState $progressState = null, ?string $submissionUid = null, ?bool $isIncomplete = true): ?Submission
    {
        if ($progressState?->submissionId && $this->_mayUseProgressStateForContinuation($form, $submissionUid, $progressState)) {
            $submission = $this->_findSubmissionById((int)$progressState->submissionId, $isIncomplete, (int)$form->id);

            if ($submission) {
                return $submission;
            }
        }

        return null;
    }

    public function resolveClientContinuationSubmission(
        Form $form,
        ?ProgressState $progressState = null,
        array $continuation = [],
        ?bool $isIncomplete = true
    ): ?Submission {
        $continuationSubmissionId = $this->_resolveSubmissionIdFromClientGrant($form, $continuation);

        // Prefer an explicit client continuation token. When automatic restore is
        // off, bare progress alone must not revive a previous visit.
        if ($progressState?->submissionId) {
            $progressSubmissionId = (int)$progressState->submissionId;
            $mayUseProgress = $form->settings->automaticSubmissionState
                || ((int)($continuation['progressId'] ?? 0) === $progressState->id)
                || ($continuationSubmissionId !== null && $continuationSubmissionId === $progressSubmissionId);

            if ($mayUseProgress) {
                $submission = $this->_findSubmissionById($progressSubmissionId, $isIncomplete, (int)$form->id);

                if ($submission) {
                    return $submission;
                }
            }
        }

        if (!$continuationSubmissionId) {
            return null;
        }

        return $this->_findSubmissionById($continuationSubmissionId, $isIncomplete, (int)$form->id);
    }

    public function primeSubmission(Submission $submission, Form $form, ?ProgressState $progressState = null, ?int $siteId = null): void
    {
        $submission->setForm($form);
        $submission->siteId = $siteId ?? $submission->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;

        if (!$submission->id && is_array($progressState?->content) && $progressState->content) {
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
        $submissionProgress = Formie::$plugin->getSubmissionProgress();
        // A receipt retry must not recreate authority after a grant was revoked.
        if (!Formie::$plugin->getSubmissionGrants()->bound($form, SubmissionGrants::CONTINUE, (int)$submission->id)) {
            throw new ForbiddenHttpException('Submission is unavailable.');
        }
        $draftState = $submissionProgress->getProgressState($form);

        if (!$draftState) {
            return [];
        }

        $resumeToken = Formie::$plugin->getSubmissionGrants()->issue($submission, SubmissionGrants::CONTINUE, $draftState->id);

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

    private function _executeClient(SubmitRequest $input, SubmissionAuthorityType $authorityType): SubmitResult
    {
        if ($authorityType !== SubmissionAuthorityType::VISITOR) {
            throw new ForbiddenHttpException('This adapter requires visitor authority.');
        }
        $form = $this->requireFormByHandle($input->handle, $input->siteId);
        $this->applyFormRequestContext($form, $input->session['tokens']['render'] ?? null, $input->session['continuation']['draftContext'] ?? null, $input->session['tokens']['request'] ?? null);
        $progress = $this->resolveProgressState($form);
        $revise = $input->action === 'revise' || ($input->session['continuation']['purpose'] ?? null) === SubmissionGrants::REVISE;
        if ($revise) {
            $continuation = $input->session['continuation'] ?? [];
            $grant = !empty($continuation['grantToken'])
                ? Formie::$plugin->getSubmissionGrants()->exchange($continuation['grantToken'], SubmissionGrants::REVISE, $form)
                : Formie::$plugin->getSubmissionGrants()->bound($form, SubmissionGrants::REVISE, (int)($continuation['submissionId'] ?? 0));
            if (!$grant) {
                throw new ForbiddenHttpException('Submission is unavailable.');
            }
            $submission = $this->_findSubmissionById($grant->submissionId, false, (int)$form->id);
            if (!$submission) {
                throw new ForbiddenHttpException('Submission is unavailable.');
            }
            $form->setSubmission($submission);
        } else {
            $submission = $this->resolveClientContinuationSubmission($form, $progress, (array)($input->session['continuation'] ?? [])) ?? new Submission();
        }
        $submission->setForm($form);
        $operation = $revise ? SubmissionOperation::REVISE : ($input->action === 'save' ? SubmissionOperation::SAVE_DRAFT : SubmissionOperation::SUBMIT);
        $navigation = $revise ? NavigationIntent::STAY : $this->_navigation($input->action, $input->targetPageId);
        $token = $input->session['tokens']['request'] ?? null;
        $result = $this->_executeResolved(
            $form, $submission, $operation, $navigation, $authorityType,
            isset($input->session['version']) ? (int)$input->session['version'] : ($submission->id ? null : 0),
            $input->operationId ?? $token, $token,
            ['browserData' => $input->browserData, 'values' => $input->values, 'action' => $input->action, 'page' => $input->session['currentPageId'] ?? null, 'target' => $input->targetPageId, 'version' => $input->session['version'] ?? null, 'continuation' => $input->session['continuation'] ?? null],
            function () use ($submission, $form, $progress, $input, $navigation): void {
                $this->primeSubmission($submission, $form, $progress, $input->siteId);
                foreach ($input->browserData as $name => $value) {
                    if (is_string($name) && is_scalar($value)) {
                        $submission->setCaptchaData($name, ['value' => (string)$value]);
                    }
                }
                // Module-owned payment token inputs supplement only declared payment
                // fields, never request credentials, grants or administrative options.
                $values = $input->values;
                parse_str(http_build_query($input->browserData), $moduleInputs);
                foreach (($moduleInputs['fields'] ?? []) as $handle => $value) {
                    if ($form->getFieldByHandle($handle) instanceof \verbb\formie\fields\Payment) {
                        $values[$handle] = $value;
                    }
                }
                if ($navigation !== NavigationIntent::BACK || Formie::$plugin->getSettings()->enableBackSubmission) {
                    foreach ($values as $handle => $value) {
                        $submission->setFieldValueFromRequest($handle, $value);
                    }
                }
            },
            $this->_normalizeNullableInt($input->session['currentPageId'] ?? $progress?->currentPageId),
            $input->targetPageId,
        );
        $form->resetRequestToken();
        return $this->_buildClientResult($result->command, $result->response, $input);
    }

    private function _executeResolved(
        Form $form, Submission $submission, SubmissionOperation $operation, NavigationIntent $navigation,
        SubmissionAuthorityType $authorityType, ?int $expectedVersion, ?string $operationId, ?string $requestToken,
        array $payload, callable $populate, ?int $pageId = null, ?int $targetPageId = null,
        SubmissionPolicy $policy = SubmissionPolicy::STANDARD, bool $browser = false,
    ): SubmissionExecutionResult {
        $scope = match ($authorityType) {
            SubmissionAuthorityType::VISITOR => 'session:' . $this->_sessionScope(),
            SubmissionAuthorityType::CONTROL_PANEL => 'user:' . Craft::$app->getUser()->getId(),
            SubmissionAuthorityType::GRAPHQL_ADMIN => 'schema:' . Craft::$app->getGql()->getActiveSchema()->uid,
            SubmissionAuthorityType::PAYMENT_REPLAY => 'payment:' . $submission->id,
            default => 'internal',
        };
        $authority = new SubmissionAuthority($authorityType, (int)$form->id, $submission->id ? (int)$submission->id : null, $scope);
        $operations = Formie::$plugin->getSubmissionOperations();
        $command = new SubmissionCommand(
            $operation, $navigation, $authority, $form, $submission, $expectedVersion, $operationId,
            $operations->fingerprint(['payload' => $payload, 'site' => $form->siteId, 'operation' => $operation->value, 'navigation' => $navigation->value, 'version' => $operation === SubmissionOperation::PAYMENT_REPLAY ? null : $expectedVersion]),
            $pageId, $targetPageId,
            $authorityType !== SubmissionAuthorityType::CONTROL_PANEL || $form->cpSubmissionFollowsFieldConditions(),
            $policy, $requestToken,
            $authorityType === SubmissionAuthorityType::CONTROL_PANEL && StringHelper::toBoolean((string)Craft::$app->getRequest()->getBodyParam('sendNotifications')),
            $authorityType === SubmissionAuthorityType::CONTROL_PANEL && StringHelper::toBoolean((string)Craft::$app->getRequest()->getBodyParam('triggerIntegrations')),
        );
        $guardReason = Formie::$plugin->getSubmissionGuards()->validateRequest($command, $browser);
        $outcome = $operations->execute($command, function () use ($command, $populate, $guardReason): SubmissionOutcome {
            if ($guardReason !== null) {
                // Cheap bot checks can deliberately fake success, but never persist posted input.
                return new SubmissionOutcome(SubmissionOutcomeType::REJECTED, data: [
                    'fakeSuccess' => Formie::$plugin->getSettings()->spamBehaviour === Settings::SPAM_BEHAVIOUR_SUCCESS,
                ]);
            }
            if ($command->isInteractive() && $command->submission->id) {
                $purpose = $command->submission->isIncomplete ? SubmissionGrants::CONTINUE : SubmissionGrants::REVISE;
                if (!Formie::$plugin->getSubmissionGrants()->bound($command->form, $purpose, (int)$command->submission->id)) {
                    throw new ForbiddenHttpException('Submission is unavailable.');
                }
            }
            if ($command->isInteractive() && !$command->submission->id) {
                $current = Formie::$plugin->getSubmissionProgress()->getProgressState($command->form);
                if ($current?->submissionId && $command->form->settings->automaticSubmissionState) {
                    throw new StateConflict($current->version);
                }
            }
            $populate();
            (new RuntimeConfiguration())->applyValues($command->submission);
            return Formie::$plugin->getSubmissionWorkflow()->process($command);
        });
        // A lost-response retry may resolve a new in-memory element. Restore the durable identity for adapters.
        if ($outcome->submissionId && (int)$submission->id !== $outcome->submissionId) {
            $submission = $this->_findSubmissionById($outcome->submissionId, null, (int)$form->id);
            if (!$submission) {
                throw new ForbiddenHttpException('Submission is unavailable.');
            }
        }
        $submission->clearErrors();
        $submission->addErrors($outcome->errors);
        $response = SubmissionResponse::fromOutcome($outcome, $form, $submission, $command);
        if (!$response->success && !$submission->hasErrors('form') && !in_array($outcome->type, [SubmissionOutcomeType::PAYMENT_ACTION_REQUIRED, SubmissionOutcomeType::PAYMENT_PENDING], true)) {
            $submission->addError('form', $form->settings->getErrorMessage());
        }
        return new SubmissionExecutionResult(['command' => $command, 'response' => $response]);
    }

    private function _sessionScope(): string
    {
        $session = Craft::$app->getSession();
        $session->open();
        if (!$session->has('formie:authority')) {
            $session->set('formie:authority', Craft::$app->getSecurity()->generateRandomString());
        }
        return hash('sha256', $session->get('formie:authority'));
    }

    private function _uploadedFileFingerprint(): array
    {
        $files = $_FILES;
        $hash = function (mixed $value) use (&$hash): mixed {
            if (is_array($value)) {
                return array_map($hash, $value);
            }
            return is_string($value) && is_file($value) ? hash_file('sha256', $value) : null;
        };
        foreach ($files as &$file) {
            // Temporary paths change between retries; file contents and posted field paths identify the input.
            $file['tmp_name'] = $hash($file['tmp_name'] ?? null);
        }
        return $files;
    }

    private function _navigation(?string $action, ?int $targetPageId): NavigationIntent
    {
        return $targetPageId ? NavigationIntent::TARGET : match ($action) {
            'back' => NavigationIntent::BACK,
            'save' => NavigationIntent::STAY,
            default => NavigationIntent::ADVANCE,
        };
    }

    private function _requireCpPermission(Form $form, Submission $submission): void
    {
        $user = Craft::$app->getUser()->getIdentity();
        $permission = $submission->id ? 'formie-saveSubmissions' : 'formie-createSubmissions';
        if (!$user || ($submission->id
            ? !Formie::$plugin->getPermissions()->canSaveSubmissions($user, $form)
            : (!$user->can($permission) && !$user->can($permission . ':' . $form->uid)
                && !$user->can($permission . ':' . Formie::$plugin->getPermissions()->groupScope(Formie::$plugin->getPermissions()->getFormGroupHandle($form)))))) {
            throw new ForbiddenHttpException('User is not permitted to perform this action.');
        }
    }

    private function _resolveManagedContinuationSubmission(
        Form $form,
        ?ProgressState $progressState = null,
        ?int $submissionId = null,
        ?string $resumeToken = null,
        ?string $submissionUid = null,
        ?bool $isIncomplete = true
    ): ?Submission {
        $submissionId = $this->_normalizeNullableInt($submissionId)
            ?? $this->_resolveSubmissionIdFromSubmissionGrant($form, $resumeToken);

        if (!$submissionId && $progressState?->submissionId) {
            $progressSubmissionId = (int)$progressState->submissionId;

            // Automatic restore uses bare progress. With it off, only continue when
            // the browser already holds this submission (multi-page / same visit).
            if ($this->_mayUseProgressStateForContinuation($form, $submissionUid, $progressState)) {
                $submissionId = $progressSubmissionId;
            } else {
                Formie::$plugin->getSubmissionProgress()->clearProgressState($form);
            }
        }

        if ($submissionId) {
            $submission = $this->_findSubmissionById($submissionId, $isIncomplete, (int)$form->id);

            if (!$submission) {
                // Managed resume tokens are expected to point at a specific
                // draft. Starting a fresh submission instead would silently
                // discard the caller's continuation state.
                throw new SubmissionUnavailableException($form, 'submissionId', (string)$submissionId);
            }

            return $submission;
        }

        return null;
    }

    /**
     * Whether leftover draft progress may continue this request.
     *
     * When automatic restore is enabled, progress alone is enough. When it is
     * disabled, the posted submission UID must match the progress submission so
     * a fresh page-1 visit cannot revive an abandoned incomplete submission.
     */
    private function _mayUseProgressStateForContinuation(
        Form $form,
        ?string $submissionUid,
        ?ProgressState $progressState,
    ): bool {
        if (!$progressState?->submissionId) {
            return false;
        }

        if ($form->settings->automaticSubmissionState) {
            return true;
        }

        $posted = $this->_findSubmissionByUid($submissionUid, true, (int)$form->id);

        return $posted !== null && (int)$posted->id === (int)$progressState->submissionId;
    }

    private function _authorizeVisitor(
        Form $form,
        ManagedSubmissionRequest $request,
        ?ProgressState $progressState,
        Submission $submission
    ): void {
        // Front-end save-submission is edit-only. Anonymous callers must use submit
        // so captcha, spam screening, and workflow policies still apply to new entries.
        if ($request->operation === SubmissionOperation::REVISE && !$submission->id) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        if (!$submission->id) {
            return;
        }

        if ($request->operation === SubmissionOperation::REVISE) {
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

    private function _buildClientResult(SubmissionCommand $submissionRequest, SubmissionResponse $response, SubmitRequest $request): SubmitResult
    {
        $form = $submissionRequest->form;
        $submission = $response->submission;
        $submitAction = $response->submitAction;
        $domainErrors = \verbb\formie\models\SubmissionErrors::fromSubmission($submission);
        $canonicalErrors = $domainErrors->toClient();
        $rawErrors = ['form' => $canonicalErrors['form']];
        $fieldErrors = $canonicalErrors['fields'];

        $currentPage = $this->_resolvePageById($form, $domainErrors->firstPageId()) ?: $response->nextPage ?: $form->getCurrentPage();
        $previousPage = $currentPage ? $form->getPreviousPage($currentPage, $submission) : null;
        $nextPageId = $response->nextPage?->id ? (string)$response->nextPage->id : null;
        $currentPageId = $currentPage?->id ? (string)$currentPage->id : null;

        // After a successful in-progress submit, always attach progress continuation
        // so the next page can continue even when automatic restore is disabled.
        $includeProgressContinuation = $response->success
            && (bool)$submission->id
            && ($response->nextPage || $submitAction === 'save');

        $session = Formie::$plugin->getClientSessionService()->issueInitialSession(
            $form,
            $currentPageId,
            false,
            $includeProgressContinuation ? true : null,
        );
        $session->continuation = array_filter([
            ...($session->continuation ?? []),
            'draftContext' => $request->session['continuation']['draftContext'] ?? ($session->continuation['draftContext'] ?? null),
            'draftContextToken' => $request->session['continuation']['draftContextToken'] ?? ($session->continuation['draftContextToken'] ?? null),
        ], static function($value) {
            return $value !== null && $value !== '';
        }) ?: null;

        $notice = null;
        $error = null;

        $notice = $response->outcome->data['completion']['message'] ?? null;

        if ($submitAction === 'save' && $response->success) {
            $notice = StringHelper::sanitizeMessageHtml($form->settings->getSubmitActionMessage($submission));
        }

        if (!$response->success) {
            $paymentFollowUpRequired = in_array($response->paymentStatus, [
                PaymentDecision::STATUS_ACTION_REQUIRED->value,
                PaymentDecision::STATUS_UNKNOWN->value,
                PaymentDecision::STATUS_PENDING->value,
            ], true);

            if ($paymentFollowUpRequired) {
                $notice = $response->paymentMessage
                    ? StringHelper::sanitizeMessageHtml($response->paymentMessage)
                    : null;
                $error = null;
                $rawErrors['form'] = [];
            } else {
                $errorMessages = $rawErrors['form'] ?? [];
                $error = $errorMessages
                    ? StringHelper::sanitizeMessageHtml(implode(' ', $errorMessages))
                    : StringHelper::sanitizeMessageHtml($form->settings->getErrorMessage());
                $rawErrors['form'] = array_map([\verbb\formie\models\SubmissionErrors::class, 'plainText'], $rawErrors['form'] ?? []);
            }
        }

        $submittedPageId = (int)($submissionRequest->pageId ?? 0);

        if ($submittedPageId <= 0) {
            if ($response->nextPage) {
                $previousPage = $form->getPreviousPage($response->nextPage, $submission);
                $submittedPageId = $previousPage?->id ? (int)$previousPage->id : 0;
            } else {
                $pages = $form->getPages();
                $submittedPageId = $pages ? (int)$pages[0]->id : 0;
            }
        }

        $clientEvents = $response->success
            ? ClientEventsHelper::resolveForSubmittedPage(
                $form,
                $submission,
                $submittedPageId ?: null,
                $submitAction,
            )
            : [];

        $savePayload = [];
        if ($response->outcome->type === SubmissionOutcomeType::DRAFT_SAVED) {
            $requestUrl = Craft::$app->getRequest();
            $savePayload = $this->createSaveResumePayload($form, $submission, $this->resolveTrustedResumeBaseUrl($requestUrl->getReferrer(), ''));
        }
        return new SubmitResult(array_merge([
            'success' => $response->success,
            'outcome' => $response->outcome->type->value,
            'version' => $response->outcome->version,
            'httpStatus' => $response->httpStatus,
            'submissionUid' => $submission->uid ?: null,
            'currentPageId' => $currentPageId,
            'nextPageId' => $nextPageId,
            'previousPageId' => $previousPage?->id ? (string)$previousPage->id : null,
            'isFinalPage' => $nextPageId === null,
            'errors' => [
                'form' => $rawErrors['form'] ?? [],
                'fields' => $fieldErrors,
            ],
            'messages' => [
                'notice' => $notice,
                'error' => $error,
            ],
            'session' => $session,
            'quizResult' => $response->quizResult,
            'completion' => $response->outcome->data['completion'] ?? null,
            'redirect' => $response->outcome->data['redirect'] ?? null,
            'clientEvents' => $clientEvents,
        ], $this->_resolvePaymentSubmitResultFields($response), $savePayload));
    }

    private function _resolvePaymentSubmitResultFields(SubmissionResponse $response): array
    {
        if (!$response->paymentStatus) {
            return [];
        }

        $fields = [
            'paymentStatus' => $response->paymentStatus,
            'keepSubmitLoading' => in_array($response->paymentStatus, [
                PaymentDecision::STATUS_ACTION_REQUIRED->value,
                PaymentDecision::STATUS_UNKNOWN->value,
                PaymentDecision::STATUS_PENDING->value,
            ], true),
        ];

        if ($response->paymentMessage) {
            $fields['paymentMessage'] = StringHelper::sanitizeMessageHtml($response->paymentMessage);
        }

        if ($response->paymentRedirectUrl) {
            $fields['paymentRedirectUrl'] = $response->paymentRedirectUrl;
        }

        if ($response->paymentAction) {
            $fields['paymentAction'] = $response->paymentAction;
        }

        if ($response->paymentDecision) {
            $fields['paymentDecision'] = $response->paymentDecision;
        }

        return $fields;
    }

    private function _resolveSubmissionIdFromSubmissionGrant(Form $form, ?string $resumeToken, string $purpose = SubmissionGrants::CONTINUE): ?int
    {
        if (!is_string($resumeToken) || trim($resumeToken) === '') {
            return null;
        }

        $verifiedSubmissionGrant = Formie::$plugin->getSubmissionGrants()->exchange(trim($resumeToken), $purpose, $form);

        if (!$verifiedSubmissionGrant || $verifiedSubmissionGrant->formId !== (int)$form->id || !$verifiedSubmissionGrant->submissionId) {
            throw new BadRequestHttpException('Invalid or expired resume token.');
        }

        return (int)$verifiedSubmissionGrant->submissionId;
    }

    private function _resolveSubmissionIdFromClientGrant(Form $form, array $continuation = []): ?int
    {
        $token = $continuation['grantToken'] ?? null;

        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        $verifiedToken = Formie::$plugin->getSubmissionGrants()->exchange(trim($token), SubmissionGrants::CONTINUE, $form);

        if (!$verifiedToken || $verifiedToken->formId !== (int)$form->id || !$verifiedToken->submissionId) {
            return null;
        }

        return (int)$verifiedToken->submissionId;
    }

    private function _validateSubmissionEditToken(Form $form, Submission $submission, ?string $token): bool
    {
        if (!is_string($token) || trim($token) === '') {
            return Formie::$plugin->getSubmissionGrants()->bound($form, $submission->isIncomplete ? SubmissionGrants::CONTINUE : SubmissionGrants::REVISE, (int)$submission->id) !== null;
        }

        $verifiedToken = Formie::$plugin->getSubmissionGrants()->exchange(trim($token), $submission->isIncomplete ? SubmissionGrants::CONTINUE : SubmissionGrants::REVISE, $form, (int)$submission->id);

        return $verifiedToken !== null &&
            (int)$verifiedToken->formId === (int)$form->id &&
            (int)$verifiedToken->submissionId === (int)$submission->id;
    }

    private function _validateSubmissionUpdateToken(Form $form, Submission $submission, ?string $token): bool
    {
        if (!is_string($token) || trim($token) === '') {
            return Formie::$plugin->getSubmissionGrants()->bound($form, $submission->isIncomplete ? SubmissionGrants::CONTINUE : SubmissionGrants::REVISE, (int)$submission->id) !== null;
        }

        $verifiedToken = Formie::$plugin->getSubmissionGrants()->exchange(trim($token), SubmissionGrants::CONTINUE, $form);

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
