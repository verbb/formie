<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Iframe extends Node
{
    protected ?string $nodeType = 'iframe';
    protected string|null|array $tagName = 'iframe';

    public function tag(): array
    {
        $attrs = [];
        if (isset($this->node->attrs)) {
            $attrs = $this->node->attrs;
        }

        return [
            [
                'tag' => $this->tagName,
                'attrs' => $attrs,
            ],
        ];
    }
}
