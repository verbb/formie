<?php

namespace verbb\formie\prosemirror\Marks;

class Link extends Mark
{
    public function matching()
    {
        return $this->mark->type === 'link';
    }

    public function tag()
    {
        $attrs = [];

        if (isset($this->mark->attrs->target)) {
            $attrs['target'] = $this->mark->attrs->target;
        }

        if (isset($this->mark->attrs->rel)) {
            $attrs['rel'] = $this->mark->attrs->rel;
        }

        $attrs['href'] = $this->mark->attrs->href;

        return [
            [
                'tag' => 'a',
                'attrs' => $attrs,
            ],
        ];
    }
}
