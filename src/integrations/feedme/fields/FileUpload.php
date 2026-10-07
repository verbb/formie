<?php
namespace verbb\formie\integrations\feedme\fields;

use verbb\formie\fields\FileUpload as FileUploadField;

use craft\feedme\fields\Assets as FeedMeAssets;

class FileUpload extends FeedMeAssets
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static string $class = FileUploadField::class;
    public static string $name = 'FileUpload';
}
