<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Table extends Node
{
    protected $nodeType = 'table';
    protected $tagName = ['table', 'tbody'];
}
