<?php

namespace verbb\formie\prosemirror\Nodes;

class HardBreak extends Node
{
    public function matching()
    {
        return $this->node->type === 'hard_break';
    }

    public function selfClosing()
    {
        return true;
    }

    public function tag()
    {
        return 'br';
    }
}
