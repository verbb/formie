<?php

namespace verbb\formie\prosemirror\Marks;

class Italic extends Mark
{
    public function matching()
    {
        return $this->mark->type === 'italic';
    }

    public function tag()
    {
        return 'em';
    }
}
