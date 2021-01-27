<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class HorizontalRule extends Node
{
    protected $nodeType = 'horizontal_rule';
    protected $tagName = 'hr';

    public function selfClosing()
    {
        return true;
    }
}