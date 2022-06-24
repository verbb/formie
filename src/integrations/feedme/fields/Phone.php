<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\feedme\helpers\DataHelper;

use Cake\Utility\Hash;

class Phone extends Field implements FieldInterface
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public static $name = 'Phone';

    /**
     * @var string
     */
    public static $class = 'verbb\formie\fields\formfields\Phone';


    // Templates
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getMappingTemplate()
    {
        return 'formie/integrations/feedme/fields/phone';
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function parseField()
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
