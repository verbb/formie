<?php
namespace verbb\formie\models;

use craft\base\Model;

class PaymentAction extends Model
{
    // Constants
    // =========================================================================

    public const TYPE_REDIRECT = 'redirect';
    public const TYPE_CONFIRM = 'confirm';
    public const TYPE_CHALLENGE = 'challenge';
    public const TYPE_INITIALIZE = 'initialize';

    public const RESUME_MODE_CALLBACK = 'callback';
    public const RESUME_MODE_WEBHOOK = 'webhook';
    public const RESUME_MODE_CLIENT = 'client';


    // Properties
    // =========================================================================

    public string $type = self::TYPE_CONFIRM;
    public ?string $provider = null;
    public ?string $event = null;
    public ?string $message = null;
    public ?string $url = null;
    public array $payload = [];
    public ?array $resume = null;


    // Static Methods
    // =========================================================================

    public static function create(array $config = []): self
    {
        return new self($config);
    }

    public static function redirect(array $config = []): self
    {
        return new self(array_merge(['type' => self::TYPE_REDIRECT], $config));
    }

    public static function redirectEvent(string $event, ?string $url = null): self
    {
        return self::redirect([
            'event' => $event,
            'url' => $url,
        ]);
    }

    public static function confirm(array $config = []): self
    {
        return new self(array_merge(['type' => self::TYPE_CONFIRM], $config));
    }

    public static function confirmEvent(string $event, ?string $url = null): self
    {
        return self::confirm([
            'event' => $event,
            'url' => $url,
        ]);
    }

    public static function challenge(array $config = []): self
    {
        return new self(array_merge(['type' => self::TYPE_CHALLENGE], $config));
    }

    public static function challengeEvent(string $event, ?string $url = null): self
    {
        return self::challenge([
            'event' => $event,
            'url' => $url,
        ]);
    }

    public static function initialize(array $config = []): self
    {
        return new self(array_merge(['type' => self::TYPE_INITIALIZE], $config));
    }

    public static function initializeEvent(string $event, ?string $url = null): self
    {
        return self::initialize([
            'event' => $event,
            'url' => $url,
        ]);
    }


    // Public Methods
    // =========================================================================

    public function forProvider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function withMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function withUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function withPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function withResume(?array $resume): self
    {
        $this->resume = $resume;

        return $this;
    }

    public function resumeMode(string $mode, ?string $url = null): self
    {
        $this->resume = [
            'mode' => $mode,
            'url' => $url,
        ];

        return $this;
    }

    public function resumeCallback(?string $url = null): self
    {
        return $this->resumeMode(self::RESUME_MODE_CALLBACK, $url);
    }

    public function resumeWebhook(?string $url = null): self
    {
        return $this->resumeMode(self::RESUME_MODE_WEBHOOK, $url);
    }

    public function resumeClient(?string $url = null): self
    {
        return $this->resumeMode(self::RESUME_MODE_CLIENT, $url);
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return [
            'type' => $this->type,
            'provider' => $this->provider,
            'event' => $this->event,
            'message' => $this->message,
            'url' => $this->url,
            'payload' => $this->payload,
            'resume' => $this->resume,
        ];
    }
}
