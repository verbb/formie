<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Underline extends Mark
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'u';
    }

    public function data(): array
    {
        return [
            'type' => 'underline',
        ];
    }
}
