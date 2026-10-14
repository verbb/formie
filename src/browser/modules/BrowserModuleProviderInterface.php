<?php
namespace verbb\formie\browser\modules;

use verbb\formie\elements\Form;
use verbb\formie\models\BrowserModule;

interface BrowserModuleProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $surface = BrowserModule::SURFACE_SERVER_RENDERED): array;
}
