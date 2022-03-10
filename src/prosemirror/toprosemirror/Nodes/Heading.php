<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Heading extends Node
{
    public function matching(): bool
    {
        return (boolean)$this->getLevel($this->DOMNode->nodeName);
    }

    public function data(): ?array
    {
        return [
            'type' => 'heading',
            'attrs' => [
                'level' => $this->getLevel($this->DOMNode->nodeName),
            ],
        ];
    }

    private function getLevel($value)
    {
        preg_match("/^h([1-6])$/", $value, $match);

        return $match[1] ?? null;
    }
}
