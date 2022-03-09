<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class Node
{
    protected mixed $node = null;
    protected ?string $nodeType = null;
    protected string|null|array $tagName = null;

    public function __construct($node)
    {
        $this->node = $node;
    }

    public function matching(): bool
    {
        if (isset($this->node->type)) {
            return $this->node->type === $this->nodeType;
        }
        return false;
    }

    public function selfClosing(): bool
    {
        return false;
    }

    public function tag(): array|string|null
    {
        return $this->tagName;
    }

    public function text(): ?string
    {
        return null;
    }
}
