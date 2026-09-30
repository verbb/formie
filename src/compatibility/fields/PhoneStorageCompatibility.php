<?php
namespace verbb\formie\compatibility\fields;

use verbb\formie\content\FieldStorageCodec;

use craft\helpers\Json;

trait PhoneStorageCompatibility
{
    // Public Methods
    // =========================================================================

    public function decodeValueFromStorage(mixed $value): mixed
    {
        $value = Json::decodeIfJson(parent::decodeValueFromStorage($value));

        // Formie 3 encrypted the number inside its phone model, not the whole value.
        if (is_array($value) && array_key_exists('number', $value)) {
            $value['number'] = FieldStorageCodec::decode($value['number']);
        }

        return $value;
    }
}
