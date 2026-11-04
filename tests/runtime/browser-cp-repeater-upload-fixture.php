<?php

$volume = \Tests\Support\UploadTestHelper::ensureUploadVolume();
$rows = [['fields' => [
    ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'rowLabel', 'label' => 'Row label'],
    ['type' => \verbb\formie\fields\FileUpload::class, 'handle' => 'document', 'label' => 'Nested document', 'restrictFiles' => false, 'maxFiles' => 2, 'uploadLocationSource' => 'volume:' . $volume->uid],
]]];

if (!\verbb\formie\elements\Form::find()->handle('cpRepeaterUpload')->one()) {
    \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'CP nested uploads', 'handle' => 'cpRepeaterUpload'])
        ->singleLineTextField('visitorName', ['label' => 'Visitor name', 'required' => true])
        ->fileUploadField('topDocument', ['label' => 'Top document', 'restrictFiles' => false, 'maxFiles' => 2, 'uploadLocationSource' => 'volume:' . $volume->uid])
        ->groupField('details', ['label' => 'Details', 'rows' => $rows])
        ->repeaterField('people', ['label' => 'People', 'addRowLabel' => 'Add person', 'rows' => $rows])
        ->repeaterField('reviewers', ['label' => 'Reviewers', 'addRowLabel' => 'Add reviewer', 'rows' => $rows])
        ->settings(['disableCaptchas' => true])->create();
}

$assetIds = [];

foreach (['top', 'group', 'first', 'second', 'replacement'] as $name) {
    $filename = 'cp-audit-' . $name . '.txt';
    $asset = \craft\elements\Asset::find()->volumeId($volume->id)->filename($filename)->one();
    $asset ??= \Tests\Support\UploadTestHelper::seedAsset($filename, 'CP audit ' . $name . ' contents', $volume);
    $assetIds[$name] = $asset->id;
}

// A fresh database can share retained upload files, so Craft may rename a seed.
file_put_contents(dirname(__DIR__, 2) . '/.cache/verbb-tests/cp-repeater-upload.json', json_encode($assetIds));
