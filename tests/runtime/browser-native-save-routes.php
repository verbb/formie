<?php

if ($path === '/browser-native-save') {
    $app->getUser()->setIdentity(null);
    $app->getView()->setTemplatesPath(__DIR__ . '/templates');
    \verbb\formie\Formie::$plugin->getBrowserAssets()->withPublishedBrowserAssets(fn() => $app->run());
    exit;
}

if ($path === '/browser-native-save-records' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $formIds = \verbb\formie\elements\Form::find()->handle(['nativeSaveDefault', 'nativeSaveNone'])->ids();
    $rows = \verbb\formie\elements\Submission::find()->formId($formIds)->status(null)->isIncomplete(null)->isSpam(null)->all();
    $app->getResponse()->format = \yii\web\Response::FORMAT_JSON;
    $app->getResponse()->data = array_map(fn($row) => [
        'id' => $row->id,
        'isIncomplete' => $row->isIncomplete,
        'name' => $row->getFieldValue('visitorName'),
        'confirmation' => $row->getFieldValue('confirmation'),
    ], $rows);
    $app->getResponse()->send();
    exit;
}
