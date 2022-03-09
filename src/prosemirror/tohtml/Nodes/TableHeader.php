<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class TableHeader extends TableCell
{
    protected ?string $nodeType = 'table_header';
    protected string|null|array $tagName  = 'th';
}
