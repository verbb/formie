<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Italic extends Mark
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'em' || $this->DOMNode->nodeName === 'i';
    }

    public function data(): array
    {
        return [
            'type' => 'italic',
        ];
    }
}
