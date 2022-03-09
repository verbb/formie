<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class HorizontalRule extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'hr';
    }

    public function data(): ?array
    {
        return [
            'type' => 'horizontal_rule',
        ];
    }
}
