<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\feedme\helpers\DataHelper;

use Cake\Utility\Hash;
use verbb\formie\fields\Group as GroupField;

class Group extends Field implements FieldInterface
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = GroupField::class;
    public static string $name = 'Group';


    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/group';
    }


    // Public Methods
    // =========================================================================

    public function parseField(): mixed
    {
        $this->beforeParseField();

        $preppedData = [];

        $fields = Hash::get($this->fieldInfo, 'fields');

        if (!$fields) {
            return null;
        }

        foreach ($fields as $subFieldHandle => $subFieldInfo) {
            $preppedData[$subFieldHandle] = DataHelper::fetchValue($this->feedData, $subFieldInfo);
        }

        // Protect against sending an empty array
        if (!$preppedData) {
            return null;
        }

        return $preppedData;
    }
}
