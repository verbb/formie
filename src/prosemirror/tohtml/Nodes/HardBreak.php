<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class HardBreak extends Node
{
    protected $nodeType = 'hard_break';
    protected $tagName = 'br';

    public function selfClosing()
    {
        return true;
    }
}
