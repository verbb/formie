<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Superscript extends Mark
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'sup';
    }

    public function data(): array
    {
        return [
            'type' => 'superscript',
        ];
    }
}
