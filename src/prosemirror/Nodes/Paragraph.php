<?php

namespace verbb\formie\prosemirror\Nodes;

class Paragraph extends Node
{
    public function matching()
    {
        return $this->node->type === 'paragraph';
    }

    public function tag()
    {
        return 'p';
    }
}
