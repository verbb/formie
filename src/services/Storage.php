<?php
namespace verbb\formie\services;

use verbb\formie\base\StorageInterface;
use verbb\formie\storage\MemoryStorage;
use verbb\formie\storage\QueryStringStorage;
use verbb\formie\storage\SessionStorage;

use Craft;
use craft\helpers\Session;

use yii\base\Component;

class Storage extends Component
{
    // Properties
    // =========================================================================

    private array $_stores;
    

    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->_stores = [
            'memory' => new MemoryStorage(),
            'query' => new QueryStringStorage(),
            'session' => new SessionStorage(),
        ];

        parent::init();
    }

    public function getStorage(string $engine = 'session'): StorageInterface
    {
        return $this->_stores[$engine] ?? $this->_stores['session'];
    }
}
