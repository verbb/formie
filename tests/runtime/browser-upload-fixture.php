<?php

$uploadVolume = \Tests\Support\UploadTestHelper::ensureUploadVolume();

foreach (['Advanced' => 'uploadManager', 'Simple' => 'fileInput'] as $uploadSuffix => $uploadDisplayType) {
    $uploadForm = \verbb\formie\Formie::$plugin->getFactories()->form([
        'title' => 'Upload reset ' . $uploadSuffix,
        'handle' => 'uploadReset' . $uploadSuffix,
    ])->multiPage(2)->onPage(1)
        ->singleLineTextField('visitorName', ['label' => 'Visitor name', 'required' => true])
        ->fileUploadField('attachment', [
            'label' => 'Attachment',
            'required' => true,
            'displayType' => $uploadDisplayType,
            'restrictFiles' => true,
            'allowedKinds' => ['text'],
            'maxFiles' => '1',
            'uploadLocationSource' => 'folder:' . $uploadVolume->uid,
        ])->onPage(2)
        ->singleLineTextField('confirmation', ['label' => 'Confirmation', 'required' => true])
        ->settings(['disableCaptchas' => true, 'submitMethod' => 'ajax', 'validationOnSubmit' => false, 'validationOnFocus' => false])
        ->create();
    $uploadForm->getPages()[1]->getPageSettings()->showBackButton = true;

    if (!Craft::$app->getElements()->saveElement($uploadForm)) {
        throw new RuntimeException('Cannot save upload reset browser fixture.');
    }
}
