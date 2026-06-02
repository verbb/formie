<?php
namespace verbb\formie\workflow\tasks;

class TaskResult
{
    // Static Methods
    // =========================================================================

    public static function continue(array $meta = []): self
    {
        return new self(true, false, $meta);
    }

    public static function halt(bool $success, array $meta = []): self
    {
        return new self($success, true, $meta);
    }


    // Properties
    // =========================================================================

    public bool $success;
    public bool $halt;
    public array $meta;


    // Public Methods
    // =========================================================================

    public function __construct(bool $success = true, bool $halt = false, array $meta = [])
    {
        $this->success = $success;
        $this->halt = $halt;
        $this->meta = $meta;
    }
}
