<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Assets as FeedMeAssets;
use verbb\formie\fields\formfields\FileUpload as FileUploadField;

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
