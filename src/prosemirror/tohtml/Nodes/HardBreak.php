<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class HardBreak extends Node
{
    protected ?string $nodeType = 'hard_break';
    protected string|null|array $tagName = 'br';

    public function selfClosing(): bool
    {
        return true;
    }
}
