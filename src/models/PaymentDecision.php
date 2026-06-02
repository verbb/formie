<?php
namespace verbb\formie\models;

use craft\base\Model;

class PaymentDecision extends Model
{
    // Constants
    // =========================================================================

    public const STATUS_NOT_REQUIRED = 'notRequired';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ACTION_REQUIRED = 'actionRequired';
    public const STATUS_PENDING = 'pending';

    public const ACTION_TYPE_REDIRECT = PaymentAction::TYPE_REDIRECT;
    public const ACTION_TYPE_CONFIRM = PaymentAction::TYPE_CONFIRM;
    public const ACTION_TYPE_CHALLENGE = PaymentAction::TYPE_CHALLENGE;
    public const ACTION_TYPE_INITIALIZE = PaymentAction::TYPE_INITIALIZE;


    // Properties
    // =========================================================================

    public string $status = self::STATUS_NOT_REQUIRED;
    public ?string $message = null;
    public ?string $redirectUrl = null;
    public ?array $action = null;
    public ?string $provider = null;
    public ?string $reference = null;
    public array $meta = [];


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

    public static function actionRequired(string|array|null $message = null, ?string $redirectUrl = null, PaymentAction|array|null $action = null, ?string $provider = null, ?string $reference = null): self
    {
        if (is_array($message)) {
            $config = $message;
            $config['status'] = self::STATUS_ACTION_REQUIRED;
            $config['action'] = self::_normalizeAction(
                $config['action'] ?? null,
                $config['provider'] ?? null,
                $config['message'] ?? null,
                $config['redirectUrl'] ?? null,
            );

            return new self($config);
        }

        return new self([
            'status' => self::STATUS_ACTION_REQUIRED,
            'message' => $message,
            'redirectUrl' => $redirectUrl,
            'action' => self::_normalizeAction($action, $provider, $message, $redirectUrl),
            'provider' => $provider,
            'reference' => $reference,
        ]);
    }

    public static function requiresAction(?string $reference, PaymentAction $action, array $config = []): self
    {
        $actionConfig = $action->toArray();

        return self::actionRequired(array_merge([
            'reference' => $reference,
            'provider' => $actionConfig['provider'] ?? null,
            'message' => $actionConfig['message'] ?? null,
            'redirectUrl' => $actionConfig['url'] ?? null,
            'action' => $action,
        ], $config));
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

    public static function action(
        string $type,
        ?string $provider = null,
        ?string $event = null,
        ?string $message = null,
        ?string $url = null,
        array $payload = [],
        ?array $resume = null,
    ): array {
        return PaymentAction::create([
            'type' => $type,
            'provider' => $provider,
            'event' => $event,
            'message' => $message,
            'url' => $url,
            'payload' => $payload,
            'resume' => $resume,
        ])->toArray();
    }


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
            'status' => $this->status,
            'message' => $this->message,
            'redirectUrl' => $this->redirectUrl,
            'action' => $this->action,
            'provider' => $this->provider,
            'reference' => $this->reference,
            'meta' => $this->meta,
        ];
    }
    

    // Private Methods
    // =========================================================================

    private function _priority(string $status): int
    {
        return match ($status) {
            self::STATUS_FAILED => 5,
            self::STATUS_ACTION_REQUIRED => 4,
            self::STATUS_PENDING => 3,
            self::STATUS_SUCCEEDED => 2,
            default => 1,
        };
    }

    private static function _normalizeAction(PaymentAction|array|null $action, ?string $provider, ?string $message, ?string $redirectUrl): ?array
    {
        if ($action === null && $redirectUrl === null && $message === null) {
            return null;
        }

        $defaults = PaymentAction::create([
            'type' => $redirectUrl ? PaymentAction::TYPE_REDIRECT : PaymentAction::TYPE_CONFIRM,
            'provider' => $provider,
            'message' => $message,
            'url' => $redirectUrl,
        ])->toArray();

        if ($action instanceof PaymentAction) {
            $action = $action->toArray();
        }

        if ($action === null) {
            return $defaults;
        }

        return array_merge($defaults, $action, [
            'provider' => $action['provider'] ?? $provider,
            'message' => $action['message'] ?? $message,
            'url' => $action['url'] ?? $redirectUrl,
            'payload' => $action['payload'] ?? [],
            'resume' => $action['resume'] ?? null,
        ]);
    }
}
