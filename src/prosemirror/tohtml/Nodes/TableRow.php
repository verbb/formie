<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class TableRow extends Node
{
    protected ?string $nodeType = 'table_row';
    protected string|null|array $tagName = 'tr';
}
