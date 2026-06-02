<?php
namespace verbb\formie\client\modules;

use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

class FieldModuleManifest implements ModuleManifestProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $renderTarget = ClientModule::RENDER_TARGET_FRONTEND): array
    {
        $modules = [];

        foreach ($this->getFields($form->getFields()) as $field) {
            foreach ($field->clientModules()->toModules(new ClientModuleContext([
                'form' => $form,
                'field' => $field,
                'renderTarget' => $renderTarget,
            ])) as $module) {
                $modules[] = $module;
            }
        }

        return $modules;
    }


    // Private Methods
    // =========================================================================

    private function getFields(array $fields): array
    {
        $flattenedFields = [];

        foreach ($fields as $field) {
            $flattenedFields[] = $field;

            if ($field instanceof ParentFieldInterface) {
                foreach ($this->getFields($field->getFields()) as $nestedField) {
                    $flattenedFields[] = $nestedField;
                }
            }
        }

        return $flattenedFields;
    }
}
