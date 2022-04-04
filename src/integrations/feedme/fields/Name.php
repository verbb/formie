<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\feedme\helpers\DataHelper;

use Cake\Utility\Hash;
use verbb\formie\fields\formfields\Name as NameField;

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
    public static string $class = NameField::class;
    /**
     * @var string
     */
    public static string $name = 'Name';


    // Templates
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/fields/name';
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function parseField(): mixed
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
        }

        return $this->fetchValue();
    }
}
