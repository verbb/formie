<?php

namespace verbb\formie\prosemirror\Marks;

class Underline extends Mark
{
    public function matching()
    {
        return $this->mark->type === 'underline';
    }

    public function tag()
    {
        return 'u';
    }
}
