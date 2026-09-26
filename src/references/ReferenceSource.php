<?php
namespace verbb\formie\references;

use Closure;
use InvalidArgumentException;

final readonly class ReferenceSource
{
    // Public Methods
    // =========================================================================

    public function __construct(public ReferenceDefinition $definition, public Closure $resolver)
    {
        if (!preg_match('/^[a-z][a-z0-9-]*\/[a-z][a-z0-9-]*$/D', $definition->id)) {
            throw new InvalidArgumentException('Custom reference IDs must be namespaced: vendor/name.');
        }
    }
}
