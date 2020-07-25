<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Iframe extends Node
{
    protected $nodeType = 'iframe';
    protected $tagName = 'iframe';

    public function tag()
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
