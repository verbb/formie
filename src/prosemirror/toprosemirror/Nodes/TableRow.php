<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class TableRow extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'tr';
    }

    public function data(): ?array
    {
        return [
            'type' => 'table_row',
        ];
    }
}
