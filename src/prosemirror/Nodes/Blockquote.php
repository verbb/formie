<?php

namespace verbb\formie\prosemirror\Nodes;

class Blockquote extends Node
{
    public function matching()
    {
        return $this->node->type === 'blockquote';
    }

    public function tag()
    {
        return 'blockquote';
    }
}
