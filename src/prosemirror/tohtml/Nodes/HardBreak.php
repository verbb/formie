<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class HardBreak extends Node
{
    protected ?string $nodeType = 'hardBreak';
    protected string|null|array $tagName = 'br';

    public function selfClosing(): bool
    {
        return true;
    }
}
