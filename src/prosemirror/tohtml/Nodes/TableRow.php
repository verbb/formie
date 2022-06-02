<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class TableRow extends Node
{
    protected ?string $nodeType = 'tableRow';
    protected string|null|array $tagName = 'tr';
}
