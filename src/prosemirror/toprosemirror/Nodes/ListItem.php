<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class ListItem extends Node
{
    public ?array $wrapper = [
        'type' => 'paragraph',
    ];

    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'li';
    }

    public function data(): ?array
    {
        if ($this->DOMNode->childNodes->length === 1
            && $this->DOMNode->childNodes[0]->nodeName == "p") {
            $this->wrapper = null;
        }

        return [
            'type' => 'list_item',
        ];
    }
}
