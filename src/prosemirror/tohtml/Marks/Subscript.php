<?php

namespace verbb\formie\prosemirror\tohtml\Marks;

class Subscript extends Mark
{
    protected ?string $markType = 'subscript';
    protected string|null|array $tagName = 'sub';
}
