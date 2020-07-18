<?php
namespace verbb\formie\web\assets\frontend;

use Craft;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\View;

class FrontEndBaseAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->sourcePath = '@verbb/formie/web/assets/frontend/dist';

        $this->js = [
            'js/formie-base-form.js',
        ];

        parent::init();
    }

    public function registerAssetFiles($view)
    {
        $strings = $this->getStrings([
            'File {filename} must be smaller than {filesize} MB.',
            'Choose up to {files} files.',

            // Error messages
            'This field is required.',
            'Please select a value.',
            'Please select a value.',
            'Please select at least one value.',
            'Please fill out this field.',
            'Please enter a valid email address.',
            'Please enter a URL.',
            'Please enter a number',
            'Please match the following format: #rrggbb',
            'Please use the YYYY-MM-DD format',
            'Please use the 24-hour time format. Ex. 23:00',
            'Please use the YYYY-MM format',
            'Please match the requested format.',
            'Please select a value that is no more than {max}.',
            'Please select a value that is no less than {min}.',
            'Please shorten this text to no more than {maxLength} characters. You are currently using {length} characters.',
            'Please lengthen this text to {minLength} characters or more. You are currently using {length} characters.',

            'Unable to parse response `{e}`.',
        ]);

        // Add locale definition JS variables
        $js = 'window.Formie = {}; window.Formie.translations = ' . Json::encode($strings) . ';';
        $view->registerJs($js, View::POS_BEGIN);

        parent::registerAssetFiles($view);
    }


    // Private Methods
    // =========================================================================

    // TODO extract to a helper like `registerTranslations`. Maybe incluude JS rego?
    private function getStrings($array)
    {
        $strings = [];

        foreach ($array as $item) {
            $strings[$item] = Craft::t('formie', $item);
        }

        return $strings;
    }
}
