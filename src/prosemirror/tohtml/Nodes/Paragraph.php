<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Paragraph extends Node
{
    protected ?string $nodeType = 'paragraph';
    protected string|null|array $tagName = 'p';
}
