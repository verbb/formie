<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Heading extends Node
{
    protected $nodeType = 'heading';

    public function tag()
    {
        return "h{$this->node->attrs->level}";
    }
}
