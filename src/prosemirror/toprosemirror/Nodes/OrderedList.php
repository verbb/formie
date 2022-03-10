<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class OrderedList extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'ol';
    }

    public function data(): ?array
    {
        return [
            'type' => 'ordered_list',
            'attrs' => [
                'order' =>
                    $this->DOMNode->getAttribute('start') ?
                        (int)$this->DOMNode->getAttribute('start') :
                        1,
            ],
        ];
    }
}
