<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Underline extends Mark
{
    public function matching()
    {
        return $this->DOMNode->nodeName === 'u';
    }

    public function data()
    {
        return [
            'type' => 'underline',
        ];
    }
}
