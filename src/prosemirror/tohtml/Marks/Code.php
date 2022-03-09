<?php

namespace verbb\formie\prosemirror\tohtml\Marks;

class Code extends Mark
{
    protected ?string $markType = 'code';
    protected string|null|array $tagName = 'code';
}
