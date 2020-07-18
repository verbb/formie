<?php

namespace verbb\formie\prosemirror\Marks;

class Code extends Mark
{
    public function matching()
    {
        return $this->mark->type === 'code';
    }

    public function tag()
    {
        return 'code';
    }
}
