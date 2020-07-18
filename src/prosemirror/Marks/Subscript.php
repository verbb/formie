<?php

namespace verbb\formie\prosemirror\Marks;

class Subscript extends Mark
{
    public function matching()
    {
        return $this->mark->type === 'subscript';
    }

    public function tag()
    {
        return 'sub';
    }
}
