<?php
namespace verbb\formie\models;

use craft\base\Model;
use craft\helpers\Json;

class IntegrationDispatchContext extends Model
{
    // Properties
    // =========================================================================

    /** @var array<string, array<string, mixed>> */
    public array $results = [];


    // Public Methods
    // =========================================================================

    public static function fromSubmission(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $value = Json::decodeIfJson($value);
        }

        if (!is_array($value)) {
            $value = [];
        }

        return new self([
            'results' => is_array($value['results'] ?? null) ? $value['results'] : [],
        ]);
    }

    public function record(string $handle, array $result): void
    {
        $this->results[$handle] = $result;
    }

    public function wasSuccessful(string $handle): bool
    {
        return (bool)($this->results[$handle]['success'] ?? false);
    }

    public function getResult(string $handle): ?array
    {
        $result = $this->results[$handle] ?? null;

        return is_array($result) ? $result : null;
    }

    public function toStorageArray(): array
    {
        return [
            'results' => $this->results,
        ];
    }
}
