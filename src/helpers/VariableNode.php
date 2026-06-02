<?php
namespace verbb\formie\helpers;

use verbb\formie\prosemirror\tohtml\Nodes\Node;

class VariableNode extends Node
{
    // Public Methods
    // =========================================================================

    public function matching(): bool
    {
        if (isset($this->node->type)) {
            return $this->node->type === 'variableTag';
        }

        return false;
    }

    public function text(): ?string
    {
        return $this->node->attrs?->value ?? '';
    }
}
