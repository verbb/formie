<?php

namespace verbb\formie\prosemirror\Nodes;

class Table extends Node
{
    public function matching()
    {
        return $this->node->type === 'table';
    }

    public function tag()
    {
        return [
            'table',
            'tbody',
        ];
    }
}
