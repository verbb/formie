<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Table extends Node
{
    public function matching(): bool
    {
        return
            $this->DOMNode->nodeName === 'tbody' &&
            $this->DOMNode->parentNode->nodeName === 'table';
    }

    public function data(): ?array
    {
        return [
            'type' => 'table',
        ];
    }
}
