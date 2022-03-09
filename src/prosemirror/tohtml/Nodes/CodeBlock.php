<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class CodeBlock extends Node
{
    protected ?string $nodeType = 'code_block';
    protected string|null|array $tagName = ['pre', 'code'];
}
