<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Mark
{
    public string $type = 'mark';

    protected mixed $DOMNode;

    public function __construct($DOMNode)
    {
        $this->DOMNode = $DOMNode;
    }

    public function matching(): bool
    {
        return false;
    }

    public function data(): array
    {
        return [];
    }
}
