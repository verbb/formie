<?php
namespace verbb\formie\integrations\feedme\fields;

use craft\feedme\fields\Assets as FeedMeAssets;

class FileUpload extends FeedMeAssets
{
    // Traits
    // =========================================================================

    use BaseFieldTrait;


    // Properties
    // =========================================================================

    public static $name = 'FileUpload';
    public static $class = 'verbb\formie\fields\formfields\FileUpload';

}
