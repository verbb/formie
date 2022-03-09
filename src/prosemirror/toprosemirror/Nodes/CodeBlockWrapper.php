<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class CodeBlockWrapper extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'pre';
    }

    public function data(): ?array
    {
        return null;
    }
}
