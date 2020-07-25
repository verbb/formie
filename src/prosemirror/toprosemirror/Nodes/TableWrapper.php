<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class TableWrapper extends Node
{
    public function matching()
    {
        return $this->DOMNode->nodeName === 'table';
    }

    public function data()
    {
        return null;
    }
}
