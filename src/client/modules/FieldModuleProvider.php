<?php
namespace verbb\formie\client\modules;

use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\BrowserModuleContext;

class FieldModuleProvider implements BrowserModuleProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $surface = BrowserModuleEntry::SURFACE_SERVER_RENDERED): array
    {
        $modules = [];

        foreach ($this->_getFields($form->getFields()) as $field) {
            foreach ($field->browserModules()->toModules(new BrowserModuleContext([
                'form' => $form,
                'field' => $field,
                'surface' => $surface,
            ])) as $module) {
                $modules[] = $module;
            }
        }

        return $modules;
    }


    // Private Methods
    // =========================================================================

    private function _getFields(array $fields): array
    {
        $flattenedFields = [];

        foreach ($fields as $field) {
            $flattenedFields[] = $field;

            if ($field instanceof ParentFieldInterface) {
                foreach ($this->_getFields($field->getFields()) as $nestedField) {
                    $flattenedFields[] = $nestedField;
                }
            }
        }

        return $flattenedFields;
    }
}
