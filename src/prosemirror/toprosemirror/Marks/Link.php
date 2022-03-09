<?php

namespace verbb\formie\prosemirror\toprosemirror\Marks;

class Link extends Mark
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'a';
    }

    public function data(): array
    {
        $data = [
            'type' => 'link',
        ];

        $attrs = [];

        if ($target = $this->DOMNode->getAttribute('target')) {
            $attrs['target'] = $target;
        }

        if ($rel = $this->DOMNode->getAttribute('rel')) {
            $attrs['rel'] = $rel;
        }

        $attrs['href'] = $this->DOMNode->getAttribute('href');

        $data['attrs'] = $attrs;

        return $data;
    }
}
