<?php

foreach (['default' => 'top-form', 'none' => ''] as $mode => $position) {
    $form = \verbb\formie\elements\Form::find()->handle('nativeSave' . ucfirst($mode))->one();
    $form ??= \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Native Save ' . $mode, 'handle' => 'nativeSave' . ucfirst($mode)])
        ->settings(['disableCaptchas' => true, 'submitMethod' => 'page-reload', 'successMessagePosition' => $position, 'successMessage' => 'Submission complete.', 'automaticSubmissionState' => false])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('visitorName', ['label' => 'Visitor name', 'required' => true])
        ->onPage(2)->singleLineTextField('confirmation', ['label' => 'Confirmation', 'required' => true])->create();
    $pages = $form->getPages();
    $pages[0]->getPageSettings()->submitButtonLabel = 'Next';
    $pages[1]->getPageSettings()->showSaveButton = true;
    $pages[1]->getPageSettings()->showBackButton = true;
    if (!\Craft::$app->getElements()->saveElement($form)) {
        throw new RuntimeException('Cannot save the native Save fixture.');
    }
}
if (!\verbb\formie\elements\Form::find()->handle('nativeSaveUnrelated')->one()) {
    \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Unrelated form', 'handle' => 'nativeSaveUnrelated'])
        ->settings(['disableCaptchas' => true, 'submitMethod' => 'page-reload'])
        ->singleLineTextField('unrelated', ['label' => 'Unrelated field'])->create();
}
