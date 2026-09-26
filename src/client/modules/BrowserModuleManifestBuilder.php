<?php
namespace verbb\formie\client\modules;

use verbb\formie\elements\Form;
use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\BrowserModuleManifest;

use yii\base\Component;

class BrowserModuleManifestBuilder extends Component
{
    // Public Methods
    // =========================================================================

    public function buildCanonical(Form $form, string $surface = BrowserModuleEntry::SURFACE_SERVER_RENDERED): array
    {
        $entries = [];
        $occurrences = [];

        // Public products receive exactly the same inventory. Surface applicability
        // is data, never a second framework-specific discovery path.
        $contextSurface = $surface === BrowserModuleEntry::SURFACE_CP_EDIT ? $surface : BrowserModuleEntry::SURFACE_SERVER_RENDERED;
        foreach ([new ConditionsModuleProvider(), new FieldModuleProvider(), new CaptchaModuleProvider()] as $provider) {
            foreach ($provider->build($form, $contextSurface) as $module) {
                $entry = $module instanceof BrowserModuleEntry ? clone $module : new BrowserModuleEntry($module);
                if ($surface === BrowserModuleEntry::SURFACE_CP_EDIT && !$entry->supportsSurface($surface)) {
                    continue;
                }
                $identity = $entry->moduleId . ':' . hash('sha256', json_encode($entry->targets));
                $occurrence = $occurrences[$identity] ?? 0;
                $occurrences[$identity] = $occurrence + 1;
                // Config edits do not change identity; repeated declarations survive.
                $entry->key = $entry->key ?: $identity . ':' . $occurrence;
                $entries[] = $entry->toArray();
            }
        }

        return (new BrowserModuleManifest($entries))->toArray();
    }
}
