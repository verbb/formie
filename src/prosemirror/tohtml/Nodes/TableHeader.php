<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class TableHeader extends TableCell
{
    protected $nodeType = 'table_header';
    protected $tagName  = 'th';
}
