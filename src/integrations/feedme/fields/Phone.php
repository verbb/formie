<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\feedme\helpers\DataHelper;

use Cake\Utility\Hash;
use verbb\formie\fields\Phone as PhoneField;

class Phone extends Field implements FieldInterface
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = PhoneField::class;
    public static string $name = 'Phone';


    // Templates
    // =========================================================================

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/phone';
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
