<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class OrderedList extends Node
{
    protected ?string $nodeType = 'orderedList';
    protected string|null|array $tagName = 'ol';

    public function tag(): array
    {
        $attrs = [];

        if (isset($this->node->attrs->order)) {
            $attrs['start'] = $this->node->attrs->order;
        }

        return [
            [
                'tag' => $this->tagName,
                'attrs' => $attrs,
            ],
        ];
    }
}
