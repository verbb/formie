<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Blockquote extends Node
{
    protected ?string $nodeType = 'blockquote';
    protected string|null|array $tagName = 'blockquote';
}
