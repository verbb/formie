<?php

namespace verbb\formie\prosemirror\Nodes;

class CodeBlock extends Node
{
    public function matching()
    {
        return $this->node->type === 'code_block';
    }

    public function tag()
    {
        return ['pre', 'code'];
    }
}
