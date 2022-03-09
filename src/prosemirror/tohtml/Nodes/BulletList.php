<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class BulletList extends Node
{
    protected ?string $nodeType = 'bullet_list';
    protected string|null|array $tagName = 'ul';
}
