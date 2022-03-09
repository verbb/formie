<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Image extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'img';
    }

    public function data(): ?array
    {
        return [
            'type' => 'image',
            'attrs' => [
                'alt' => $this->DOMNode->hasAttribute('alt') ? $this->DOMNode->getAttribute('alt') : null,
                'src' => $this->DOMNode->hasAttribute('src') ? $this->DOMNode->getAttribute('src') : null,
                'title' => $this->DOMNode->hasAttribute('title') ? $this->DOMNode->getAttribute('title') : null,
            ],
        ];
    }
}
