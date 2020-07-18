<?php

namespace verbb\formie\prosemirror\Marks;

class Superscript extends Mark
{
    public function matching()
    {
        return $this->mark->type === 'superscript';
    }

    public function tag()
    {
        return 'sup';
    }
}
