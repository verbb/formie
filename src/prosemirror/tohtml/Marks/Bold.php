<?php

namespace verbb\formie\prosemirror\tohtml\Marks;

class Bold extends Mark
{
    protected ?string $markType = 'bold';
    protected string|null|array $tagName = 'strong';
}
