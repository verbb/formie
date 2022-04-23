<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\elements\Form;
// use verbb\formie\models\Name as NameModel;

use Craft;
use craft\base\Field;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class CaptchaInputType extends InputObjectType
{
    /**
     * Create the type for a name field.
     *
     * @param $context
     * @return bool|mixed
     */
    public static function getType()
    {
        $typeName = 'FormieCaptchaInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $inputType = GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() {
                return [
                    'name' => [
                        'name' => 'name',
                        'type' => Type::string(),
                    ],
                    'value' => [
                        'name' => 'value',
                        'type' => Type::string(),
                    ],
                ];
            },
        ]));

        return $inputType;
    }
}
