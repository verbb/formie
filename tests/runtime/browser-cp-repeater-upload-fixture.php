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

foreach (['top', 'group', 'first', 'second', 'replacement'] as $name) {
    $filename = 'cp-audit-' . $name . '.txt';
    if (!\craft\elements\Asset::find()->volumeId($volume->id)->filename($filename)->one()) {
        \Tests\Support\UploadTestHelper::seedAsset($filename, 'CP audit ' . $name . ' contents', $volume);
    }
}
