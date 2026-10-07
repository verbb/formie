<?php
namespace verbb\formie\models;

use verbb\formie\enums\PaymentResumeMode;

final readonly class PaymentAction
{
    // Static Methods
    // =========================================================================

    public static function redirect(?string $provider = null, ?string $event = null, ?string $message = null, ?string $url = null, array $payload = [], ?PaymentResumeMode $resumeMode = null, ?string $resumeUrl = null): self
    {
        return new self(self::TYPE_REDIRECT, $provider, $event, $message, $url, $payload, $resumeMode, $resumeUrl);
    }

    public static function confirm(?string $provider = null, ?string $event = null, ?string $message = null, ?string $url = null, array $payload = [], ?PaymentResumeMode $resumeMode = null, ?string $resumeUrl = null): self
    {
        return new self(self::TYPE_CONFIRM, $provider, $event, $message, $url, $payload, $resumeMode, $resumeUrl);
    }

    public static function challenge(?string $provider = null, ?string $event = null, ?string $message = null, ?string $url = null, array $payload = [], ?PaymentResumeMode $resumeMode = null, ?string $resumeUrl = null): self
    {
        return new self(self::TYPE_CHALLENGE, $provider, $event, $message, $url, $payload, $resumeMode, $resumeUrl);
    }

    public static function initialize(?string $provider = null, ?string $event = null, ?string $message = null, ?string $url = null, array $payload = [], ?PaymentResumeMode $resumeMode = null, ?string $resumeUrl = null): self
    {
        return new self(self::TYPE_INITIALIZE, $provider, $event, $message, $url, $payload, $resumeMode, $resumeUrl);
    }


    // Constants
    // =========================================================================

    public const TYPE_REDIRECT = 'redirect';
    public const TYPE_CONFIRM = 'confirm';
    public const TYPE_CHALLENGE = 'challenge';
    public const TYPE_INITIALIZE = 'initialize';


    // Public Methods
    // =========================================================================

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'provider' => $this->provider,
            'event' => $this->event,
            'message' => $this->message,
            'url' => $this->url,
            'payload' => $this->payload,
            'resume' => $this->resumeMode ? [
                'mode' => $this->resumeMode->value,
                'url' => $this->resumeUrl,
            ] : null,
        ];
    }


    // Private Methods
    // =========================================================================

    private function __construct(
        public string $type,
        public ?string $provider = null,
        public ?string $event = null,
        public ?string $message = null,
        public ?string $url = null,
        public array $payload = [],
        public ?PaymentResumeMode $resumeMode = null,
        public ?string $resumeUrl = null,
    ) {
    }
}
