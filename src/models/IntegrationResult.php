<?php
namespace verbb\formie\models;

use verbb\formie\enums\IntegrationStatus;
use verbb\formie\errors\IntegrationStepException;

use Throwable;

use GuzzleHttp\Exception\RequestException;

/** Describes delivery certainty. Unknown results require reconciliation, never blind retry. */
final class IntegrationResult
{
    // Static Methods
    // =========================================================================

    public static function succeeded(?string $providerId = null): self
    {
        return new self(IntegrationStatus::Succeeded, providerId: $providerId);
    }

    public static function skipped(string $code = 'ineligible'): self
    {
        return new self(IntegrationStatus::Skipped, code: $code);
    }

    public static function rejected(string $code = 'provider_rejected'): self
    {
        return new self(IntegrationStatus::Rejected, code: $code);
    }

    public static function failed(string $code = 'delivery_failed', bool $retryable = false): self
    {
        return new self(IntegrationStatus::Failed, code: $code, retryable: $retryable);
    }

    public static function unknown(string $code = 'outcome_unknown'): self
    {
        return new self(IntegrationStatus::Unknown, code: $code);
    }

    public static function fromException(Throwable $error): self
    {
        do {
            if ($error instanceof IntegrationStepException) {
                return $error->result;
            }

            if ($error instanceof RequestException) {
                $status = $error->getResponse()?->getStatusCode();

                if ($status !== null && $status >= 400 && $status < 500 && !in_array($status, [408, 409, 429], true)) {
                    return self::rejected('http_' . $status);
                }
            }
        } while ($error = $error->getPrevious());

        return self::unknown();
    }

    public static function fromStorage(array $value): self
    {
        return new self(
            IntegrationStatus::from($value['status']),
            (string)($value['message'] ?? ''),
            (string)($value['code'] ?? ''),
            (bool)($value['retryable'] ?? false),
            $value['providerId'] ?? null,
            (array)($value['diagnostics'] ?? []),
            (array)($value['outputs'] ?? []),
            (bool)($value['failQueue'] ?? true),
        );
    }


    // Properties
    // =========================================================================

    public readonly IntegrationStatus $status;
    public readonly string $message;
    public readonly string $code;
    public readonly bool $retryable;
    public readonly ?string $providerId;
    public readonly array $diagnostics;
    public readonly array $outputs;
    public readonly bool $failQueue;


    // Public Methods
    // =========================================================================

    public function __construct(IntegrationStatus $status, string $message = '', string $code = '', bool $retryable = false, ?string $providerId = null, array $diagnostics = [], array $outputs = [], bool $failQueue = true)
    {
        $this->status = $status;
        $this->message = $message;
        $this->code = $code;
        $this->retryable = $status === IntegrationStatus::Failed && $retryable;
        $this->providerId = $providerId;
        $this->diagnostics = $diagnostics;
        $this->outputs = $outputs;
        $this->failQueue = $status === IntegrationStatus::Unknown || $failQueue;
    }

    public function withOutputs(array $outputs): self
    {
        return new self($this->status, $this->message, $this->code, $this->retryable, $this->providerId, $this->diagnostics, $outputs + $this->outputs, $this->failQueue);
    }

    public function withQueueFailure(bool $failQueue): self
    {
        return new self($this->status, $this->message, $this->code, $this->retryable, $this->providerId, $this->diagnostics, $this->outputs, $failQueue);
    }

    public function shouldFailQueue(): bool
    {
        return $this->failQueue && !in_array($this->status, [IntegrationStatus::Succeeded, IntegrationStatus::Skipped], true);
    }

    public function isSuccessful(): bool
    {
        return $this->status === IntegrationStatus::Succeeded;
    }

    public function requiresReconciliation(): bool
    {
        return $this->status === IntegrationStatus::Unknown;
    }

    public function toStorage(): array
    {
        return ['status' => $this->status->value, 'message' => $this->message, 'code' => $this->code, 'retryable' => $this->retryable, 'providerId' => $this->providerId, 'diagnostics' => $this->diagnostics, 'outputs' => $this->outputs, 'failQueue' => $this->failQueue];
    }
}
