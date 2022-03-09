<?php

namespace verbb\formie\prosemirror\tohtml\Marks;

class Strike extends Mark
{
    protected ?string $markType = 'strike';
    protected string|null|array $tagName = 'strike';
}
