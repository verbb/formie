<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class ListItem extends Node
{
    protected ?string $nodeType = 'list_item';
    protected string|null|array $tagName = 'li';
}
