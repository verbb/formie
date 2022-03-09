<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class TableWrapper extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'table';
    }

    public function data(): ?array
    {
        return null;
    }
}
