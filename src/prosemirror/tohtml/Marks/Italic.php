<?php

namespace verbb\formie\prosemirror\tohtml\Marks;

class Italic extends Mark
{
    protected ?string $markType = 'italic';
    protected string|null|array $tagName = 'em';
}
