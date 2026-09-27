<?php
namespace verbb\formie\models;

use verbb\formie\enums\CompletionBehavior;

final class CompletionOutcome
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly CompletionBehavior $behavior,
        public readonly ?string $url = null,
        public readonly string $target = 'same-tab',
        public readonly ?string $message = null,
        public readonly bool $hideForm = false,
    ) {
    }

    public function toArray(): array
    {
        return ['behavior' => $this->behavior->value, 'url' => $this->url, 'target' => $this->target,
            'message' => $this->message, 'hideForm' => $this->hideForm];
    }
}
