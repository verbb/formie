<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Text extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === '#text';
    }

    public function data(): ?array
    {
        $text = ltrim($this->DOMNode->nodeValue, "\n");

        if ($text === '') {
            return [];
        }

        return [
            'type' => 'text',
            'text' => $text,
        ];
    }
}
