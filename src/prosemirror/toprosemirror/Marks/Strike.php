<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Strike extends Mark
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'strike'
            || $this->DOMNode->nodeName === 's'
            || $this->DOMNode->nodeName === 'del';
    }

    public function data(): array
    {
        return [
            'type' => 'strike',
        ];
    }
}
