<?php
namespace verbb\formie\services;

use verbb\formie\storage\DbStorage;
use verbb\formie\storage\StorageInterface;

use yii\base\Component;

class StorageManager extends Component
{
    // Properties
    // =========================================================================

    private ?StorageInterface $_adapter = null;


    // Public Methods
    // =========================================================================

    public function getAdapter(): StorageInterface
    {
        if ($this->_adapter) {
            return $this->_adapter;
        }

        // Keep a dedicated storage seam for draft/resume state, but support only the
        // canonical database-backed adapter until alternative engines are production-ready.
        $this->_adapter = new DbStorage();

        return $this->_adapter;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getAdapter()->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int $ttl = null): bool
    {
        return $this->getAdapter()->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->getAdapter()->delete($key);
    }
}
