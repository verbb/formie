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

        // Empty legacy models omitted the nullable number but retained country metadata.
        if (is_array($value) && !array_key_exists('number', $value)
            && array_intersect(['countryCode', 'countryName', 'hasCountryCode'], array_keys($value))
            && !array_diff(array_keys($value), ['country', 'countryCode', 'countryName', 'hasCountryCode'])) {
            $value['number'] = '';
        }

        // Formie 3 encrypted the number inside its phone model, not the whole value.
        if (is_array($value) && array_key_exists('number', $value)) {
            $value['number'] = FieldStorageCodec::decode($value['number']);
        }

        return $value;
    }
}
