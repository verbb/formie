<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\gql\arguments\FormArguments;
use verbb\formie\gql\interfaces\FormInterface;
use verbb\formie\gql\types\FormType;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\helpers\Gql as GqlHelper;

class FormGenerator implements GeneratorInterface
{
    // Public Methods
    // =========================================================================

    public static function generateTypes($context = null): array
    {
        $gqlTypes = [];
        $typeName = Form::gqlTypeNameByContext(null);

        $forms = Formie::getInstance()->getForms()->getAllForms();

        foreach ($forms as $form) {
            $contentFields = $form->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $formFields = TypeManager::prepareFieldDefinitions(array_merge(FormInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            // Generate a type for each entry type
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new FormType([
                'name' => $typeName,
                'fields' => function() use ($formFields) {
                    return $formFields;
                }
            ]));
        }
        
        return $gqlTypes;
    }
}
