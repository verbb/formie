<?php
namespace verbb\formie\models;

use verbb\formie\enums\CompletionBehavior;
use verbb\formie\enums\RedirectTarget;

final class CompletionOutcome
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly CompletionBehavior $behavior,
        public readonly ?string $url = null,
        public readonly RedirectTarget $target = RedirectTarget::SameTab,
        public readonly ?string $message = null,
        public readonly bool $hideForm = false,
    ) {
    }

    public function toArray(): array
    {
        return ['behavior' => $this->behavior->value, 'url' => $this->url, 'target' => $this->target->value,
            'message' => $this->message, 'hideForm' => $this->hideForm];
    }
}
