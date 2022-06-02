<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class TableHeader extends TableCell
{
    protected ?string $nodeType = 'tableHeader';
    protected string|null|array $tagName = 'th';
}
