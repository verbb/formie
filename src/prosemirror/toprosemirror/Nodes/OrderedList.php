<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class OrderedList extends Node
{
    public function matching()
    {
        return $this->DOMNode->nodeName === 'ol';
    }

    public function data()
    {
        return [
            'type' => 'ordered_list',
            'attrs' => [
                'order' => 1,
            ],
        ];
    }
}
