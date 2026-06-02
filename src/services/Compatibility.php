<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\compatibility\bootstrap\AliasBootstrap;
use verbb\formie\compatibility\events\PhpEventMap;

use craft\base\Component;

class Compatibility extends Component
{
    // Public Methods
    // =========================================================================

    public function bootstrap(): void
    {
        if (!$this->isCompatibilityModeEnabled()) {
            return;
        }

        AliasBootstrap::register();
        PhpEventMap::register();
    }

    public function isCompatibilityModeEnabled(): bool
    {
        return Formie::$plugin->getSettings()->compatibilityMode ?? true;
    }
}
