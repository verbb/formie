<?php
namespace verbb\formie\storage;

interface StorageInterface
{
    // Public Methods
    // =========================================================================

    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, null|int $ttl = null): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function has(string $key): bool;

    public function getMultiple(iterable $keys, mixed $default = null): iterable;
    public function setMultiple(iterable $values, null|int $ttl = null): bool;
    public function deleteMultiple(iterable $keys): bool;
}
