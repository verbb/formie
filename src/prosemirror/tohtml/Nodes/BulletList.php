<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class BulletList extends Node
{
    protected ?string $nodeType = 'bulletList';
    protected string|null|array $tagName = 'ul';
}
