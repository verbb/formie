<?php
namespace verbb\formie\services;

use verbb\formie\cache\FieldTypeDefinitionCache;
use verbb\formie\base\FieldTypeDefinitionInterface;
use verbb\formie\fields as formiefields;

use Craft;
use craft\base\Component;
use craft\helpers\Json;

use yii\base\InvalidConfigException;

class FieldTypeDefinitions extends Component
{
    // Constants
    // =========================================================================

    private const GROUP_CLASSES = [
        'internal' => [
            formiefields\MissingField::class,
        ],
        'basic' => [
            formiefields\SingleLineText::class,
            formiefields\MultiLineText::class,
            formiefields\Name::class,
            formiefields\Email::class,
            formiefields\Phone::class,
            formiefields\Number::class,
        ],
        'option' => [
            formiefields\Radio::class,
            formiefields\Checkboxes::class,
            formiefields\Dropdown::class,
            formiefields\Agree::class,
        ],
        'advanced' => [
            formiefields\Date::class,
            formiefields\Address::class,
            formiefields\FileUpload::class,
            formiefields\Password::class,
            formiefields\Hidden::class,
            formiefields\Recipients::class,
            formiefields\Signature::class,
            formiefields\Calculations::class,
            formiefields\Payment::class,
        ],
        'dynamic' => [
            formiefields\Repeater::class,
            formiefields\Group::class,
            formiefields\Table::class,
        ],
        'cosmetic' => [
            formiefields\Heading::class,
            formiefields\Section::class,
            formiefields\Html::class,
            formiefields\Content::class,
            formiefields\Summary::class,
        ],
        'element' => [
            formiefields\Entries::class,
            formiefields\Categories::class,
            formiefields\Tags::class,
            formiefields\Users::class,
            formiefields\Products::class,
            formiefields\Variants::class,
        ],
        'custom' => [
            formiefields\CustomField::class,
        ],
    ];

    private ?FieldTypeDefinitionCache $_cache = null;


    // Public Methods
    // =========================================================================

    public function getDefinition(string $fieldClass): array
    {
        if (isset($this->_getCache()->definitionsByClass[$fieldClass])) {
            return $this->_getCache()->definitionsByClass[$fieldClass];
        }

        if (!is_subclass_of($fieldClass, FieldTypeDefinitionInterface::class)) {
            throw new InvalidConfigException("Field type \"{$fieldClass}\" must implement FieldTypeDefinitionInterface.");
        }

        $definition = $fieldClass::getFieldTypeDefinition();

        if (!is_array($definition)) {
            throw new InvalidConfigException("Field type \"{$fieldClass}\" returned an invalid field type definition.");
        }

        foreach (['icon', 'type', 'label'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $definition)) {
                throw new InvalidConfigException("Field type \"{$fieldClass}\" is missing required definition key \"{$requiredKey}\".");
            }
        }

        return $this->_getCache()->definitionsByClass[$fieldClass] = $definition;
    }

    public function getDefinitions(array $fieldClasses): array
    {
        $definitions = [];

        foreach ($fieldClasses as $fieldClass) {
            $definitions[$fieldClass] = $this->getDefinition($fieldClass);
        }

        return $definitions;
    }

    public function getGroupedDefinitions(array $fieldClasses): array
    {
        $cacheKey = Json::encode(array_values($fieldClasses));

        if (isset($this->_getCache()->groupedDefinitionsBySet[$cacheKey])) {
            return $this->_getCache()->groupedDefinitionsBySet[$cacheKey];
        }

        $definitionsByClass = $this->getDefinitions($fieldClasses);
        $remainingClassSet = array_fill_keys($fieldClasses, true);
        $groupedFieldDefinitions = [];

        $groupLabels = [
            'internal' => Craft::t('formie', 'Internal'),
            'basic' => Craft::t('formie', 'Basic Fields'),
            'option' => Craft::t('formie', 'Option Fields'),
            'advanced' => Craft::t('formie', 'Advanced Fields'),
            'dynamic' => Craft::t('formie', 'Dynamic Fields'),
            'cosmetic' => Craft::t('formie', 'Cosmetic Fields'),
            'element' => Craft::t('formie', 'Element Fields'),
            'custom' => Craft::t('formie', 'Custom Fields'),
        ];

        foreach (self::GROUP_CLASSES as $groupHandle => $groupClasses) {
            $groupDefinitions = [];

            foreach ($groupClasses as $groupClass) {
                if (!isset($remainingClassSet[$groupClass])) {
                    continue;
                }

                if (!isset($definitionsByClass[$groupClass])) {
                    continue;
                }

                $groupDefinitions[] = $definitionsByClass[$groupClass];
                unset($remainingClassSet[$groupClass]);
            }

            if ($groupDefinitions) {
                $groupedFieldDefinitions[] = [
                    'label' => $groupLabels[$groupHandle],
                    'handle' => $groupHandle,
                    'fields' => $groupDefinitions,
                ];
            }
        }

        if ($remainingClassSet) {
            $customDefinitions = [];

            foreach (array_keys($remainingClassSet) as $remainingClass) {
                if (isset($definitionsByClass[$remainingClass])) {
                    $customDefinitions[] = $definitionsByClass[$remainingClass];
                }
            }

            if ($customDefinitions) {
                $groupedFieldDefinitions[] = [
                    'label' => Craft::t('formie', 'Custom Fields'),
                    'handle' => 'custom',
                    'fields' => $customDefinitions,
                ];
            }
        }

        return $this->_getCache()->groupedDefinitionsBySet[$cacheKey] = $groupedFieldDefinitions;
    }

    private function _getCache(): FieldTypeDefinitionCache
    {
        if ($this->_cache === null) {
            $this->_cache = new FieldTypeDefinitionCache();
        }

        return $this->_cache;
    }
}
