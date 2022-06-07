<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\formfields\Payment as PaymentField;

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
    public ?int $integrationId = null;
    public ?int $submissionId = null;
    public ?int $fieldId = null;
    public ?int $planId = null;
    public ?string $reference = null;
    public ?array $subscriptionData = null;
    public ?int $trialDays = null;
    public ?DateTime $nextPaymentDate = null;
    public bool $hasStarted = true;
    public bool $isSuspended = false;
    public ?DateTime $dateSuspended = null;
    public bool $isCanceled = false;
    public ?DateTime $dateCanceled = null;
    public bool $isExpired = false;
    public ?DateTime $dateExpired = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;

    private ?IntegrationInterface $_integration = null;
    private ?Submission $_submission = null;
    private ?PaymentField $_field = null;
    private ?Plan $_plan = null;


    // Public Methods
    // =========================================================================

    public function getIntegration(): ?IntegrationInterface
    {
        if (!isset($this->_integration)) {
            $this->_integration = Formie::$plugin->getIntegrations()->getIntegrationById($this->integrationId);
        }

        return $this->_integration;
    }

    public function getSubmission(): ?Submission
    {
        if (!isset($this->_submission)) {
            $this->_submission = Formie::$plugin->getSubmissions()->getSubmissionById($this->submissionId);
        }

        return $this->_submission;
    }

    public function getField(): ?PaymentField
    {
        if (!isset($this->_field)) {
            $this->_field = Craft::$app->getFields()->getFieldById($this->fieldId);
        }

        return $this->_field;
    }

    public function getPlan(): ?Plan
    {
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

    public function getStatus(): ?string
    {
        if ($this->isExpired) {
            return self::STATUS_EXPIRED;
        }

        if ($this->isCanceled) {
            return self::STATUS_CANCELLED;
        }

        return $this->isSuspended ? self::STATUS_SUSPENDED : self::STATUS_ACTIVE;
    }

    public function getCancelUrl(): string
    {
        $reference = Craft::$app->getSecurity()->hashData($this->reference);

        return UrlHelper::actionUrl('formie/payment-subscriptions/cancel', ['id' => $this->id, 'hash' => $reference]);
    }
}
