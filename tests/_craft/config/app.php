<?php

use Craft;
use craft\helpers\App;

return [
    'id' => App::env('CRAFT_APP_ID') ?: 'CraftCMS-FormieTests',

    'components' => [
        'projectConfig' => function() {
            $config = craft\helpers\App::projectConfigConfig();
            $config['writeYamlAutomatically'] = false;
            return Craft::createObject($config);
        },
    ]
];
