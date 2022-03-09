<?php

namespace verbb\formie\prosemirror\tohtml\Marks;

class Superscript extends Mark
{
    protected ?string $markType = 'superscript';
    protected string|null|array $tagName = 'sup';
}
