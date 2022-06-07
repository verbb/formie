<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\formfields\Payment as PaymentField;

use craft\base\Model;

use DateTime;

class Payment extends Model
{
    // Constants
    // =========================================================================

    public const STATUS_PENDING = 'pending';
    public const STATUS_REDIRECT = 'redirect';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PROCESSING = 'processing';


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $integrationId = null;
    public ?int $submissionId = null;
    public ?int $fieldId = null;
    public ?int $subscriptionId = null;
    public float $amount;
    public ?string $currency = null;
    public ?string $status = null;
    public ?string $reference = null;
    public ?string $code = null;
    public ?string $message = null;
    public string $note = '';
    public ?array $response = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;

    private ?IntegrationInterface $_integration = null;
    private ?Submission $_submission = null;
    private ?PaymentField $_field = null;
    private ?Subscription $_subscription = null;


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

    public function getSubscription(): ?Subscription
    {
        if (!isset($this->_subscription)) {
            $this->_subscription = Formie::$plugin->getSubscriptions()->getSubscriptionById($this->subscriptionId);
        }

        return $this->_subscription;
    }
}
