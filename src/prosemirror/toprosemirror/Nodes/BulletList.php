<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class BulletList extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'ul';
    }

    public function data(): ?array
    {
        return [
            'type' => 'bullet_list',
        ];
    }
}
