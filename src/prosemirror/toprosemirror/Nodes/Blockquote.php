<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Blockquote extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'blockquote';
    }

    public function data(): ?array
    {
        return [
            'type' => 'blockquote',
        ];
    }
}
