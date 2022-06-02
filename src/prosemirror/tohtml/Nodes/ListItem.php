<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class ListItem extends Node
{
    protected ?string $nodeType = 'listItem';
    protected string|null|array $tagName = 'li';
}
