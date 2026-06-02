<?php
namespace verbb\formie\storage;

abstract class AbstractStorage implements StorageInterface
{
    // Public Methods
    // =========================================================================

    public function has(string $key): bool
    {
        return $this->get($key, null) !== null;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get((string)$key, $default);
        }

        return $values;
    }

    public function setMultiple(iterable $values, null|int $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set((string)$key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (!$this->delete((string)$key)) {
                $success = false;
            }
        }

        return $success;
    }
}
