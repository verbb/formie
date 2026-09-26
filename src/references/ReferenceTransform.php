<?php
namespace verbb\formie\references;

use verbb\formie\fields\definitions\FieldValueType;

use Closure;
use InvalidArgumentException;

final readonly class ReferenceTransform
{
    // Public Methods
    // =========================================================================

    public function __construct(public string $id, public FieldValueType $inputType, public FieldValueType $outputType, public Closure $transform, public bool $server = true, public bool $browser = false, public array $parameters = [])
    {
        if (!preg_match('/^[a-z][a-z0-9-]*\/[a-z][a-z0-9-]*$/D', $id)) {
            throw new InvalidArgumentException('Custom transform IDs must be namespaced: vendor/name.');
        }
    }
}
