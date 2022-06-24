<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Image extends Node
{
    protected $nodeType = 'image';
    protected $tagName = 'img';

    public function selfClosing()
    {
        return true;
    }

    public function tag()
    {
        return [
            [
                'tag' => $this->tagName,
                'attrs' => $this->node->attrs,
            ],
        ];
    }
}