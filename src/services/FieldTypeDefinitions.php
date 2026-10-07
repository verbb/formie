<?php
namespace verbb\formie\services;

use verbb\formie\base\Field;
use verbb\formie\cache\FieldTypeDefinitionCache;
use verbb\formie\fields\Address;
use verbb\formie\fields\Agree;
use verbb\formie\fields\Calculations;
use verbb\formie\fields\Categories;
use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Content;
use verbb\formie\fields\CustomField;
use verbb\formie\fields\Date;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Email;
use verbb\formie\fields\Entries;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Group;
use verbb\formie\fields\Heading;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Html;
use verbb\formie\fields\MissingField;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\Name;
use verbb\formie\fields\Note;
use verbb\formie\fields\Number;
use verbb\formie\fields\Password;
use verbb\formie\fields\Payment;
use verbb\formie\fields\Phone;
use verbb\formie\fields\Products;
use verbb\formie\fields\Quiz;
use verbb\formie\fields\Radio;
use verbb\formie\fields\Recipients;
use verbb\formie\fields\Repeater;
use verbb\formie\fields\Section;
use verbb\formie\fields\Signature;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Summary;
use verbb\formie\fields\Survey;
use verbb\formie\fields\Table;
use verbb\formie\fields\Tags;
use verbb\formie\fields\Users;
use verbb\formie\fields\Variants;

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
            MissingField::class,
        ],
        'basic' => [
            SingleLineText::class,
            MultiLineText::class,
            Name::class,
            Email::class,
            Phone::class,
            Number::class,
        ],
        'option' => [
            Radio::class,
            Checkboxes::class,
            Dropdown::class,
            Agree::class,
            Quiz::class,
            Survey::class,
        ],
        'advanced' => [
            Date::class,
            Address::class,
            FileUpload::class,
            Password::class,
            Hidden::class,
            Recipients::class,
            Signature::class,
            Calculations::class,
            Payment::class,
        ],
        'dynamic' => [
            Repeater::class,
            Group::class,
            Table::class,
        ],
        'cosmetic' => [
            Heading::class,
            Section::class,
            Html::class,
            Content::class,
            Note::class,
            Summary::class,
        ],
        'element' => [
            Entries::class,
            Categories::class,
            Tags::class,
            Users::class,
            Products::class,
            Variants::class,
        ],
        'custom' => [
            CustomField::class,
        ],
    ];


    // Properties
    // =========================================================================

    private ?FieldTypeDefinitionCache $_cache = null;


    // Public Methods
    // =========================================================================

    public function getDefinition(string $fieldClass): array
    {
        if (isset($this->_getCache()->definitionsByClass[$fieldClass])) {
            return $this->_getCache()->definitionsByClass[$fieldClass];
        }

        if (!is_subclass_of($fieldClass, Field::class)) {
            throw new InvalidConfigException("Field type \"{$fieldClass}\" must extend the Formie base Field class.");
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


    // Private Methods
    // =========================================================================

    private function _getCache(): FieldTypeDefinitionCache
    {
        if ($this->_cache === null) {
            $this->_cache = new FieldTypeDefinitionCache();
        }

        return $this->_cache;
    }
}
