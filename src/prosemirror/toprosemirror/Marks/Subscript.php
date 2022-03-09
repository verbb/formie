<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Subscript extends Mark
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'sub';
    }

    public function data(): array
    {
        return [
            'type' => 'subscript',
        ];
    }
}
