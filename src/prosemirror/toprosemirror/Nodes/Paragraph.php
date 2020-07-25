<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Paragraph extends Node
{
    public function matching()
    {
        return $this->DOMNode->nodeName === 'p';
    }

    public function data()
    {
        return [
            'type' => 'paragraph',
        ];
    }
}
