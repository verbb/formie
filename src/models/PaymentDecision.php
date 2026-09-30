<?php
namespace verbb\formie\models;

use verbb\formie\enums\PaymentDecisionStatus;

use craft\base\Model;

class PaymentDecision extends Model
{
    // Static Methods
    // =========================================================================

    public static function notRequired(array $config = []): self
    {
        return new self(array_merge(['status' => self::STATUS_NOT_REQUIRED], $config));
    }

    public static function succeeded(?string $provider = null, ?string $reference = null): self
    {
        return new self([
            'status' => self::STATUS_SUCCEEDED,
            'provider' => $provider,
            'reference' => $reference,
        ]);
    }

    public static function failed(?string $message = null, ?string $provider = null, ?string $reference = null): self
    {
        return new self([
            'status' => self::STATUS_FAILED,
            'message' => $message,
            'provider' => $provider,
            'reference' => $reference,
        ]);
    }

    public static function requiresAction(?string $reference, PaymentAction $action, array $config = []): self
    {
        $actionConfig = $action->toArray();

        return new self(array_merge([
            'status' => self::STATUS_ACTION_REQUIRED,
            'reference' => $reference,
            'provider' => $actionConfig['provider'] ?? null,
            'message' => $actionConfig['message'] ?? null,
            'action' => $actionConfig,
        ], $config));
    }

    public static function unknown(?string $message = null, ?string $provider = null, ?string $reference = null): self
    {
        return new self(['status' => self::STATUS_UNKNOWN, 'message' => $message, 'provider' => $provider, 'reference' => $reference]);
    }

    public static function cancelled(?string $message = null, ?string $provider = null, ?string $reference = null): self
    {
        return new self(['status' => self::STATUS_CANCELLED, 'message' => $message, 'provider' => $provider, 'reference' => $reference]);
    }

    public static function pending(?string $message = null, ?string $provider = null, ?string $reference = null): self
    {
        return new self([
            'status' => self::STATUS_PENDING,
            'message' => $message,
            'provider' => $provider,
            'reference' => $reference,
        ]);
    }

    // Constants
    // =========================================================================

    public const STATUS_NOT_REQUIRED = PaymentDecisionStatus::NOT_REQUIRED;
    public const STATUS_SUCCEEDED = PaymentDecisionStatus::SUCCEEDED;
    public const STATUS_FAILED = PaymentDecisionStatus::FAILED;
    public const STATUS_ACTION_REQUIRED = PaymentDecisionStatus::ACTION_REQUIRED;
    public const STATUS_PENDING = PaymentDecisionStatus::PENDING;
    public const STATUS_UNKNOWN = PaymentDecisionStatus::UNKNOWN;
    public const STATUS_CANCELLED = PaymentDecisionStatus::CANCELLED;

    // Properties
    // =========================================================================

    public PaymentDecisionStatus $status = self::STATUS_NOT_REQUIRED;
    public ?string $message = null;
    public ?array $action = null;
    public ?string $provider = null;
    public ?string $reference = null;
    public array $meta = [];


    // Public Methods
    // =========================================================================

    public function merge(self $other): self
    {
        if ($this->_priority($other->status) > $this->_priority($this->status)) {
            return $other;
        }

        return $this;
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return [
            'status' => $this->status->value,
            'message' => $this->message,
            'action' => $this->action,
            'provider' => $this->provider,
            'reference' => $this->reference,
            'meta' => $this->meta,
        ];
    }
    

    // Private Methods
    // =========================================================================

    private function _priority(PaymentDecisionStatus $status): int
    {
        return match ($status) {
            self::STATUS_UNKNOWN => 7,
            self::STATUS_CANCELLED => 6,
            self::STATUS_FAILED => 5,
            self::STATUS_ACTION_REQUIRED => 4,
            self::STATUS_PENDING => 3,
            self::STATUS_SUCCEEDED => 2,
            default => 1,
        };
    }

}
