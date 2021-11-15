<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\feedme\helpers\DataHelper;

use Cake\Utility\Hash;

class Name extends Field implements FieldInterface
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;

    
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public static $name = 'Name';

    /**
     * @var string
     */
    public static $class = 'verbb\formie\fields\formfields\Name';


    // Templates
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getMappingTemplate()
    {
        return 'formie/integrations/feedme/fields/name';
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function parseField()
    {
        $this->beforeParseField();

        if ($this->field->useMultipleFields) {
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
        } else {
            return $this->fetchValue();
        }
    }
}
