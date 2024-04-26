<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\gql\types\generators\PageGenerator;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutPageSettings;

use Craft;
use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class PageInterface extends BaseInterfaceType
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return PageGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all pages.',
            'resolveType' => function(FieldLayoutPage $value) {
                return GqlEntityRegistry::getEntity(PageGenerator::getName());
            },
        ]));

        PageGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'PageInterface';
    }

    public static function getFieldDefinitions(): array
    {
        $fields = array_merge(parent::getFieldDefinitions(), [
            'label' => [
                'name' => 'label',
                'type' => Type::string(),
                'description' => 'The page’s label.',
            ],
            'rows' => [
                'name' => 'rows',
                'type' => Type::listOf(RowInterface::getType()),
                'description' => 'The page’s rows.',
                'args' => [
                    'includeDisabled' => [
                        'name' => 'includeDisabled',
                        'description' => 'Whether to include fields with visibility "disabled".',
                        'type' => Type::boolean(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $includeDisabled = $arguments['includeDisabled'] ?? false;

                    return $source->getRows($includeDisabled);
                },
            ],
            'pageFields' => [
                'name' => 'pageFields',
                'type' => Type::listOf(FieldInterface::getType()),
                'description' => 'The page’s fields.',
                'args' => [
                    'includeDisabled' => [
                        'name' => 'includeDisabled',
                        'description' => 'Whether to include fields with visibility "disabled".',
                        'type' => Type::boolean(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $includeDisabled = $arguments['includeDisabled'] ?? false;

                    return $source->getFields($includeDisabled);
                },
            ],
            'settings' => [
                'name' => 'settings',
                'type' => PageSettingsInterface::getType(),
                'description' => 'The page’s settings, including buttons.',
                'resolve' => function($source, $arguments) {
                    // Ensure we cast it correctly if we need to
                    if (!($source->settings instanceof FieldLayoutPageSettings)) {
                        return new FieldLayoutPageSettings($source->settings);
                    }

                    return $source->settings;
                },
            ],
        ]);

        return Craft::$app->getGql()->prepareFieldDefinitions($fields, self::getName());
    }
}
