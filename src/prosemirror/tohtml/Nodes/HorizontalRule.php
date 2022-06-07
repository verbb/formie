<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class HorizontalRule extends Node
{
    protected ?string $nodeType = 'horizontalRule';
    protected string|null|array $tagName = 'hr';

    public function selfClosing(): bool
    {
        return true;
    }
}