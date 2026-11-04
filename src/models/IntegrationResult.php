<?php
namespace verbb\formie\models;

use verbb\formie\enums\IntegrationStatus;
use verbb\formie\errors\IntegrationStepException;

use Throwable;

use GuzzleHttp\Exception\RequestException;

/** A delivery either passes or fails. Retrying a failed delivery always runs it again. */
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

    /** @deprecated Use failed(). A rejection is a failure, and keeps its code. */
    public static function rejected(string $code = 'provider_rejected'): self
    {
        return new self(IntegrationStatus::Rejected, code: $code);
    }

    public static function failed(string $code = 'delivery_failed', bool $retryable = false): self
    {
        return new self(IntegrationStatus::Failed, code: $code, retryable: $retryable);
    }

    /** @deprecated Use failed(). An unconfirmed outcome is a failure, and keeps its code. */
    public static function unknown(string $code = 'outcome_unknown'): self
    {
        return new self(IntegrationStatus::Unknown, code: $code);
    }

    public static function fromException(Throwable $error): self
    {
        $status = null;

        do {
            if ($error instanceof IntegrationStepException) {
                return $error->result;
            }

            if ($error instanceof RequestException) {
                $status ??= $error->getResponse()?->getStatusCode();
            }
        } while ($error = $error->getPrevious());

        return self::failed($status !== null ? 'http_' . $status : 'delivery_failed');
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
    /** @deprecated Every failure can be retried. */
    public readonly bool $retryable;
    public readonly ?string $providerId;
    public readonly array $diagnostics;
    public readonly array $outputs;
    public readonly bool $failQueue;


    // Public Methods
    // =========================================================================

    public function __construct(IntegrationStatus $status, string $message = '', string $code = '', bool $retryable = false, ?string $providerId = null, array $diagnostics = [], array $outputs = [], bool $failQueue = true)
    {
        // Results from custom providers and earlier releases can still use the retired statuses.
        $status = in_array($status, [IntegrationStatus::Rejected, IntegrationStatus::Unknown], true) ? IntegrationStatus::Failed : $status;

        // Earlier policies and custom providers acknowledge errors with this flag. Normalize
        // them at the result boundary so a batch cannot turn them back into queue failures.
        if ($status === IntegrationStatus::Failed && !$failQueue) {
            $status = IntegrationStatus::Skipped;
        }

        $this->status = $status;
        $this->message = $message;
        $this->code = $code;
        $this->retryable = $status === IntegrationStatus::Failed;
        $this->providerId = $providerId;
        $this->diagnostics = $diagnostics;
        $this->outputs = $outputs;
        $this->failQueue = $failQueue;
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

    /** @deprecated Nothing needs confirming. A failed delivery can always be retried. */
    public function requiresReconciliation(): bool
    {
        return false;
    }

    public function toStorage(): array
    {
        return ['status' => $this->status->value, 'message' => $this->message, 'code' => $this->code, 'retryable' => $this->retryable, 'providerId' => $this->providerId, 'diagnostics' => $this->diagnostics, 'outputs' => $this->outputs, 'failQueue' => $this->failQueue];
    }
}
