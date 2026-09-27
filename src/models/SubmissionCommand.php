<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionPolicy;

use InvalidArgumentException;

/**
 * Transport-independent, resolved input to the trusted workflow.
 * Input is applied only after the processor locks and verifies the target version.
 */
final class SubmissionCommand
{
    // Properties
    // =========================================================================

    public readonly SubmissionUploadClaims $uploadClaims;


    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly SubmissionOperation $operation,
        public readonly NavigationIntent $navigation,
        public readonly SubmissionAuthority $authority,
        public readonly Form $form,
        public readonly Submission $submission,
        public readonly ?int $expectedVersion = null,
        public readonly ?string $operationId = null,
        public readonly ?string $payloadFingerprint = null,
        public readonly ?int $pageId = null,
        public readonly ?int $targetPageId = null,
        public readonly bool $clearConditionallyHiddenFields = true,
        public readonly SubmissionPolicy $policy = SubmissionPolicy::STANDARD,
        public readonly ?string $requestToken = null,
        public readonly bool $sendNotificationsOnSpamUnmark = false,
        public readonly bool $triggerIntegrationsOnSpamUnmark = false,
        ?SubmissionUploadClaims $uploadClaims = null,
        public readonly bool $allowLegacyUploadIds = false,
    ) {
        $this->uploadClaims = $uploadClaims ?? $submission->getContentState()->uploadClaims ?? new SubmissionUploadClaims();
        $submission->getContentState()->uploadClaims = $this->uploadClaims;

        if ($policy === SubmissionPolicy::ADMINISTRATIVE_CREATE && ($operation !== SubmissionOperation::SUBMIT || $submission->id || !in_array($authority->type, [SubmissionAuthorityType::CONTROL_PANEL, SubmissionAuthorityType::GRAPHQL_ADMIN, SubmissionAuthorityType::TRUSTED_INTERNAL], true))) {
            throw new InvalidArgumentException('Administrative creation requires a new, administratively authorized Submit.');
        }

        if ($authority->formId !== (int)$form->id || $authority->submissionId !== ($submission->id ? (int)$submission->id : null)) {
            throw new InvalidArgumentException('Submission authority does not match the command target.');
        }

        if ($expectedVersion !== null && $expectedVersion < 0) {
            throw new InvalidArgumentException('Expected version must be non-negative.');
        }

        if ($navigation === NavigationIntent::TARGET && !$targetPageId) {
            throw new InvalidArgumentException('Target navigation requires a page identity.');
        }

        if (in_array($operation, [SubmissionOperation::REVISE, SubmissionOperation::PAYMENT_REPLAY], true) && !$submission->id) {
            throw new InvalidArgumentException('This operation requires a persisted submission.');
        }
    }

    public function isInteractive(): bool
    {
        return $this->authority->type === SubmissionAuthorityType::VISITOR;
    }

    public function usesVisitorProgression(): bool
    {
        return $this->policy === SubmissionPolicy::STANDARD && in_array($this->operation, [SubmissionOperation::SUBMIT, SubmissionOperation::SAVE_DRAFT], true);
    }
}
