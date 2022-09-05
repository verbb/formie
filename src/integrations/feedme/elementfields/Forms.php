<?php
namespace verbb\formie\integrations\feedme\elementfields;

use verbb\formie\elements\Form as FormElement;
use verbb\formie\fields\Forms as FormsField;

use Craft;
use craft\base\Element as BaseElement;
use craft\helpers\Db;
use craft\helpers\Json;

use craft\feedme\Plugin;
use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;

use Cake\Utility\Hash;

class Forms extends Field implements FieldInterface
{
    // Properties
    // =========================================================================

    public static string $name = 'Forms';
    public static string $class = FormsField::class;
    public static string $elementType = FormElement::class;


    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/elementfields/forms';
    }


    // Public Methods
    // =========================================================================

    public function parseField(): mixed
    {
        $value = $this->fetchArrayValue();

        $match = Hash::get($this->fieldInfo, 'options.match', 'title');
        $node = Hash::get($this->fieldInfo, 'node');

        $foundElements = [];

        if (!$value) {
            return $foundElements;
        }

        foreach ($value as $dataValue) {
            // Prevent empty or blank values (string or array), which match all elements
            if (empty($dataValue)) {
                continue;
            }

            // If we're using the default value - skip, we've already got an id array
            if ($node === 'usedefault') {
                $foundElements = $value;
                break;
            }

            // Because we can match on element attributes and custom fields, AND we're directly using SQL
            // queries in our `where` below, we need to check if we need a prefix for custom fields accessing
            // the content table.
            $columnName = $match;

            if (Craft::$app->getFields()->getFieldByHandle($match)) {
                $columnName = Craft::$app->getFields()->oldFieldColumnPrefix . $match;
            }

            $query = FormElement::find();

            $criteria['status'] = null;
            $criteria['where'] = ['=', $columnName, $dataValue];

            Craft::configure($query, $criteria);

            Plugin::info('Search for existing Formie form with query `{i}`', ['i' => Json::encode($criteria)]);

            $ids = $query->ids();

            $foundElements = array_merge($foundElements, $ids);

            Plugin::info('Found `{i}` existing Formie forms: `{j}`', ['i' => count($foundElements), 'j' => Json::encode($foundElements)]);
        }

        $foundElements = array_unique($foundElements);

        // Protect against sending an empty array - removing any existing elements
        if (!$foundElements) {
            return null;
        }

        return $foundElements;
    }
}
