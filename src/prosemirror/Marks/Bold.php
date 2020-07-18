<?php

namespace verbb\formie\prosemirror\Marks;

class Bold extends Mark
{
    public function matching()
    {
        return $this->mark->type === 'bold';
    }

    public function tag()
    {
        return 'strong';
    }
}
