<?php

namespace verbb\formie\prosemirror\tohtml\Marks;

class Underline extends Mark
{
    protected ?string $markType = 'underline';
    protected string|null|array $tagName = 'u';
}
