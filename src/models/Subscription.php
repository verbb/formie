<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\enums\PaymentCapabilityPurpose;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\helpers\PaymentCapabilities;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;

use DateInterval;
use DateTime;

class Subscription extends Model
{
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

    public ?int $integrationId = null;
    public ?int $submissionId = null;
    public ?int $fieldId = null;
    public ?int $planId = null;
    public ?string $reference = null;
    public ?array $subscriptionData = null;
    public ?int $trialDays = 0;
    public ?DateTime $nextPaymentDate = null;
    public ?DateTime $dateSuspended = null;
    public ?DateTime $dateCanceled = null;
    public ?DateTime $dateExpired = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;

    private SubscriptionStatus $_status = SubscriptionStatus::PENDING;
    private ?IntegrationInterface $_integration = null;
    private ?Submission $_submission = null;
    private ?PaymentField $_field = null;
    private ?Plan $_plan = null;


    // Public Methods
    // =========================================================================

    public function getIntegration(): ?IntegrationInterface
    {
        if (!$this->integrationId) { return null; }
        if (!isset($this->_integration)) {
            $this->_integration = Formie::$plugin->getIntegrations()->getIntegrationById($this->integrationId);
        }

        return $this->_integration;
    }

    public function getSubmission(): ?Submission
    {
        if (!$this->submissionId) { return null; }
        if (!isset($this->_submission)) {
            $this->_submission = Formie::$plugin->getSubmissions()->getSubmissionById($this->submissionId);
        }

        return $this->_submission;
    }

    public function getField(): ?PaymentField
    {
        if (!$this->fieldId) { return null; }
        if (!isset($this->_field)) {
            $this->_field = Formie::$plugin->getFields()->getFieldById($this->fieldId);
        }

        return $this->_field;
    }

    public function getPlan(): ?Plan
    {
        if (!$this->planId) { return null; }
        if (!isset($this->_plan)) {
            $this->_plan = Formie::$plugin->getPlans()->getPlanById($this->planId);
        }

        return $this->_plan;
    }

    public function canReactivate(): bool
    {
        return $this->isCanceled && !$this->isExpired;
    }

    public function getIsOnTrial(): bool
    {
        if ($this->isExpired) {
            return false;
        }

        return $this->trialDays > 0 && time() <= $this->getTrialExpires()->getTimestamp();
    }

    public function getTrialExpires(): ?DateTIme
    {
        $created = clone $this->dateCreated;

        return $created->add(new DateInterval('P' . $this->trialDays . 'D'));
    }

    public function getStatus(): string
    {
        return $this->_status->value;
    }

    public function setStatus(string|SubscriptionStatus $status): void
    {
        $this->_status = is_string($status) ? SubscriptionStatus::from($status) : $status;
    }

    public function getState(): SubscriptionStatus
    {
        return $this->_status;
    }

    // Stable template projections; the aggregate has only one authoritative state.
    public function getHasStarted(): bool
    {
        return !in_array($this->_status, [SubscriptionStatus::PENDING, SubscriptionStatus::UNKNOWN], true);
    }

    public function getIsSuspended(): bool
    {
        return $this->_status === SubscriptionStatus::SUSPENDED;
    }

    public function getIsCanceled(): bool
    {
        return in_array($this->_status, [SubscriptionStatus::CANCELLED, SubscriptionStatus::CANCELLING], true);
    }

    public function getIsExpired(): bool
    {
        return $this->_status === SubscriptionStatus::EXPIRED;
    }


    public function getCancelUrl(): string
    {
        $token = PaymentCapabilities::issue(PaymentCapabilityPurpose::CANCEL, (int)$this->id, ['subscriptionUid' => $this->uid, 'integrationId' => $this->integrationId, 'submissionId' => $this->submissionId], 86400);
        return UrlHelper::actionUrl('formie/payment-subscriptions/cancel', ['id' => $this->id, 'token' => $token]);
    }
}
