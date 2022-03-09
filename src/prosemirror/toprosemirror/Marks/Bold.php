<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Bold extends Mark
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'strong' || $this->DOMNode->nodeName === 'b';
    }

    public function data(): array
    {
        return [
            'type' => 'bold',
        ];
    }
}
