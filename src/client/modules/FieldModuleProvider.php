<?php
namespace verbb\formie\client\modules;

use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;

class FieldModuleProvider implements BrowserModuleProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $surface = BrowserModule::SURFACE_SERVER_RENDERED): array
    {
        $modules = [];

        foreach ($this->_getFields($form->getFields()) as $field) {
            $context = new BrowserModuleContext([
                'form' => $form,
                'field' => $field,
                'surface' => $surface,
            ]);

            foreach ($field->browserModules($context) as $module) {
                $modules[] = $module->withProjectionDefaults('field', $context->getTargets());
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
