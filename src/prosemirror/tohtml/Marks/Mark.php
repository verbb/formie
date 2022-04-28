<?php

namespace verbb\formie\prosemirror\tohtml\Marks;

class Mark
{
    protected mixed $mark = null;
    protected ?string $markType = null;
    protected string|null|array $tagName = null;

    public function __construct($mark)
    {
        $this->mark = $mark;
    }

    public function matching(): bool
    {
        if (isset($this->mark->type)) {
            return $this->mark->type === $this->markType;
        }
        return false;
    }

    public function tag(): array|string|null
    {
        return $this->tagName;
    }
}
