<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Blockquote extends Node
{
    public function matching()
    {
        return $this->DOMNode->nodeName === 'blockquote';
    }

    public function data()
    {
        return [
            'type' => 'blockquote',
        ];
    }
}
