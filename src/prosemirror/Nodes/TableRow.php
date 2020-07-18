<?php

namespace verbb\formie\prosemirror\Nodes;

class TableRow extends Node
{
    public function matching()
    {
        return $this->node->type === 'table_row';
    }

    public function tag()
    {
        return 'tr';
    }
}
