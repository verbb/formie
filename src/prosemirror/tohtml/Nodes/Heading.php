<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Heading extends Node
{
    protected ?string $nodeType = 'heading';

    public function tag(): string
    {
        return "h{$this->node->attrs->level}";
    }
}
