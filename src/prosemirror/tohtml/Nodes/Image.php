<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Image extends Node
{
    protected ?string $nodeType = 'image';
    protected string|null|array $tagName = 'img';

    public function selfClosing(): bool
    {
        return true;
    }

    public function tag(): array
    {
        return [
            [
                'tag' => $this->tagName,
                'attrs' => $this->node->attrs,
            ],
        ];
    }
}