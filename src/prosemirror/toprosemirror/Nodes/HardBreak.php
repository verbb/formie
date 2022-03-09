<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class HardBreak extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'br';
    }

    public function data(): ?array
    {
        return [
            'type' => 'hard_break',
        ];
    }
}
