<?php
namespace verbb\formie\client\modules;

use verbb\formie\elements\Form;
use verbb\formie\models\BrowserModuleEntry;

interface BrowserModuleProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $surface = BrowserModuleEntry::SURFACE_SERVER_RENDERED): array;
}
