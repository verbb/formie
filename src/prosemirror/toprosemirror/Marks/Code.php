<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Code extends Mark
{
    public function matching(): bool
    {
        if ($this->DOMNode->parentNode->nodeName === 'pre') {
            return false;
        }

        return $this->DOMNode->nodeName === 'code';
    }

    public function data(): array
    {
        return [
            'type' => 'code',
        ];
    }
}
