<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Italic extends Mark
{
    public function matching()
    {
        return $this->DOMNode->nodeName === 'em' || $this->DOMNode->nodeName === 'i';
    }

    public function data()
    {
        return [
            'type' => 'italic',
        ];
    }
}
