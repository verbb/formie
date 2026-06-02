<?php
namespace verbb\formie\client\modules;

use verbb\formie\elements\Form;
use verbb\formie\models\ClientModule;

interface ModuleManifestProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $renderTarget = ClientModule::RENDER_TARGET_FRONTEND): array;
}
