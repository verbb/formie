<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Paragraph extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'p';
    }

    public function data(): ?array
    {
        return [
            'type' => 'paragraph',
        ];
    }
}
