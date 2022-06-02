<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class CodeBlock extends Node
{
    protected ?string $nodeType = 'codeBlock';
    protected string|null|array $tagName = ['pre', 'code'];
}
