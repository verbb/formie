<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Node
{
    public ?array $wrapper = null;

    public string $type = 'node';

    protected mixed $DOMNode;

    public function __construct($DOMNode)
    {
        $this->DOMNode = $DOMNode;
    }

    public function matching(): bool
    {
        return false;
    }

    public function data(): ?array
    {
        return [];
    }
}
