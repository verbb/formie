<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\compatibility\payments\LegacySubscriptionData;
use verbb\formie\elements\Submission;
use verbb\formie\enums\PaymentCapabilityPurpose;
use verbb\formie\enums\SubscriptionCancellationMode;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\helpers\PaymentCapabilities;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;

use DateInterval;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;

class Subscription extends Model
{
    // Traits
    // =========================================================================

    use LegacySubscriptionData;

    // Constants
    // =========================================================================

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';


    // Properties
    // =========================================================================

    public ?int $id = null;
    public int $version = 0;
    public ?array $history = null;
    public ?array $scope = null;
    public ?string $idempotencyKey = null;
    public ?DateTime $archivedAt = null;
    public ?int $providerUpdatedAt = null;
    public ?string $providerStatus = null;

    public ?int $integrationId = null;
    public ?int $submissionId = null;
    public ?int $fieldId = null;
    public ?int $planId = null;
    public ?string $reference = null;
    public array $providerData = [];
    public ?string $accountFingerprint = null;
    public ?array $terms = null;
    public ?DateTimeInterface $lastSyncedAt = null;
    public ?int $trialDays = 0;
    public ?DateTimeInterface $startedAt = null;
    public ?DateTimeInterface $trialStartsAt = null;
    public ?DateTimeInterface $trialEndsAt = null;
    public ?DateTimeInterface $currentPeriodStartsAt = null;
    public ?DateTimeInterface $currentPeriodEndsAt = null;
    public ?DateTimeInterface $nextPaymentAt = null;
    public ?DateTimeInterface $pausedAt = null;
    public ?DateTimeInterface $cancelAt = null;
    public ?DateTimeInterface $cancelledAt = null;
    public ?DateTimeInterface $endedAt = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;

    private SubscriptionStatus $_status = SubscriptionStatus::PENDING;
    private ?SubscriptionCancellationMode $_cancellationMode = null;
    private ?IntegrationInterface $_integration = null;
    private ?Submission $_submission = null;
    private ?PaymentField $_field = null;
    private ?SubscriptionPlan $_plan = null;


    // Public Methods
    // =========================================================================

    public function getIntegration(): ?IntegrationInterface
    {
        if (!$this->integrationId) {
            return null;
        }

        if (!isset($this->_integration)) {
            $this->_integration = Formie::$plugin->getIntegrations()->getIntegrationById($this->integrationId);
        }

        return $this->_integration;
    }

    public function getSubmission(): ?Submission
    {
        if (!$this->submissionId) {
            return null;
        }

        if (!isset($this->_submission)) {
            $this->_submission = Formie::$plugin->getSubmissions()->getSubmissionById($this->submissionId);
        }

        return $this->_submission;
    }

    public function getField(): ?PaymentField
    {
        if (!$this->fieldId) {
            return null;
        }

        if (!isset($this->_field)) {
            $this->_field = Formie::$plugin->getFields()->getFieldById($this->fieldId);
        }

        return $this->_field;
    }

    public function getPlan(): ?SubscriptionPlan
    {
        if (!$this->planId) {
            return null;
        }

        if (!isset($this->_plan)) {
            $this->_plan = Formie::$plugin->getPlans()->getPlanById($this->planId);
        }

        return $this->_plan;
    }

    public function canReactivate(): bool
    {
        return $this->cancelAt !== null && !$this->_status->isTerminal();
    }

    public function getIsOnTrial(): bool
    {
        if ($this->_status !== SubscriptionStatus::TRIALING || $this->isExpired) {
            return false;
        }

        $expires = $this->getTrialExpires();

        return $expires === null || time() <= $expires->getTimestamp();
    }

    public function getTrialExpires(): ?DateTimeInterface
    {
        if ($this->trialEndsAt) {
            return $this->trialEndsAt;
        }

        if (!$this->dateCreated || !$this->trialDays) {
            return null;
        }

        $created = clone $this->dateCreated;

        return $created->add(new DateInterval('P' . $this->trialDays . 'D'));
    }

    public function getStatus(): string
    {
        return $this->_status->value;
    }

    public function setStatus(string|SubscriptionStatus $status): void
    {
        $this->_status = is_string($status) ? SubscriptionStatus::fromStored($status, $this->providerStatus) : $status;
    }

    public function getState(): SubscriptionStatus
    {
        return $this->_status;
    }

    public function getStatusLabel(): string
    {
        return Craft::t('formie', match ($this->_status) {
            SubscriptionStatus::PENDING => 'Pending',
            SubscriptionStatus::TRIALING => 'Trialing',
            SubscriptionStatus::ACTIVE => 'Active',
            SubscriptionStatus::PAST_DUE => 'Past Due',
            SubscriptionStatus::PAUSED => 'Paused',
            SubscriptionStatus::CANCELLED => 'Cancelled',
            SubscriptionStatus::FAILED => 'Failed',
            SubscriptionStatus::COMPLETED => 'Completed',
            SubscriptionStatus::UNKNOWN => 'Unknown',
        });
    }

    public function getCanCancel(): bool
    {
        return $this->reference !== null
            && $this->getIntegration() instanceof PaymentIntegration
            && !$this->_status->isTerminal()
            && $this->cancelAt === null
            && empty($this->scope['cancellationPending']);
    }

    // Stable template projections; the aggregate has only one authoritative state.
    public function getHasStarted(): bool
    {
        return $this->startedAt !== null || $this->_status->isEstablished() || $this->_status === SubscriptionStatus::COMPLETED;
    }

    public function getIsSuspended(): bool
    {
        return in_array($this->_status, [SubscriptionStatus::PAST_DUE, SubscriptionStatus::PAUSED], true);
    }

    public function getIsCanceled(): bool
    {
        return $this->_status === SubscriptionStatus::CANCELLED;
    }

    public function getIsExpired(): bool
    {
        return in_array($this->_status, [SubscriptionStatus::FAILED, SubscriptionStatus::COMPLETED], true);
    }

    public function getCancellationMode(): ?SubscriptionCancellationMode
    {
        return $this->_cancellationMode;
    }

    public function setCancellationMode(string|SubscriptionCancellationMode|null $mode): void
    {
        $this->_cancellationMode = is_string($mode) ? SubscriptionCancellationMode::tryFrom($mode) : $mode;
    }

    // Formie 3 template projections remain aliases over the canonical timeline.
    public function getDateSuspended(): ?DateTimeInterface
    {
        return $this->pausedAt;
    }

    public function setDateSuspended(?DateTimeInterface $value): void
    {
        $this->pausedAt = $value;
    }

    public function getDateCanceled(): ?DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setDateCanceled(?DateTimeInterface $value): void
    {
        $this->cancelledAt = $value;
    }

    public function getDateExpired(): ?DateTimeInterface
    {
        return $this->endedAt;
    }

    public function setDateExpired(?DateTimeInterface $value): void
    {
        $this->endedAt = $value;
    }

    public function getNextPaymentDate(): ?DateTimeInterface
    {
        return $this->nextPaymentAt;
    }

    public function setNextPaymentDate(?DateTimeInterface $value): void
    {
        $this->nextPaymentAt = $value;
    }

    public function getCancelUrl(?SubscriptionCancellationMode $mode = null): string
    {
        $integration = $this->getIntegration();
        $mode ??= $integration instanceof PaymentIntegration
            ? $integration->getDefaultSubscriptionCancellationMode()
            : SubscriptionCancellationMode::IMMEDIATE;

        if ($integration instanceof PaymentIntegration && !in_array($mode, $integration->getSubscriptionCancellationModes(), true)) {
            throw new InvalidArgumentException('The payment provider does not support this cancellation mode.');
        }

        $token = PaymentCapabilities::issue(PaymentCapabilityPurpose::CANCEL, (int)$this->id, [
            'subscriptionUid' => $this->uid,
            'integrationId' => $this->integrationId,
            'submissionId' => $this->submissionId,
            'cancellationMode' => $mode->value,
        ], 86400);

        return UrlHelper::actionUrl('formie/payment-subscriptions/cancel', [
            'id' => $this->id,
            'token' => $token,
            'mode' => $mode->value,
        ]);
    }
}
