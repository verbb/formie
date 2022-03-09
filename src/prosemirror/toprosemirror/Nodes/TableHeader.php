<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class TableHeader extends TableCell
{
    protected string|null|array $tagName = 'th';
    protected string $nodeType = 'table_header';
}
