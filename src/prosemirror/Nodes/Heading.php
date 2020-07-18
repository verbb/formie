<?php

namespace verbb\formie\prosemirror\Nodes;

class Heading extends Node
{
    public function matching()
    {
        return $this->node->type === 'heading';
    }

    public function tag()
    {
        return "h{$this->node->attrs->level}";
    }
}
