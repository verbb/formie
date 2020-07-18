<?php
namespace verbb\formie\helpers;

use verbb\formie\prosemirror\Nodes\Node;

class VariableNode extends Node
{
    // Public Methods
    // =========================================================================

    public function matching()
    {
        return $this->node->type === 'variableTag';
    }

    public function text()
    {
        return $this->node->attrs->value ?? '';
    }
}
