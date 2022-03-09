<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Table extends Node
{
    protected ?string $nodeType = 'table';
    protected string|null|array $tagName = ['table', 'tbody'];
}
