<?php

if ($path === '/browser-cp-repeater-upload') {
    $form = \verbb\formie\elements\Form::find()->handle('cpRepeaterUpload')->one();
    $assets = [];
    foreach (['top', 'group', 'first', 'second', 'replacement'] as $name) {
        $assets[$name] = \craft\elements\Asset::find()->filename('cp-audit-' . $name . '.txt')->one();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submission = \verbb\formie\Formie::$plugin->getFactories()->submission($form)->with(['visitorName' => 'Original visitor'])->save();
        $submission->setFieldValues([
            'visitorName' => 'Original visitor',
            'topDocument' => [$assets['top']->id],
            'details' => ['rowLabel' => 'Group original', 'document' => [$assets['group']->id]],
            'reviewers' => [['rowLabel' => 'Reviewer one', 'document' => [$assets['group']->id]]],
            'people' => [
                ['rowLabel' => 'Person one', 'document' => [$assets['first']->id]],
                ['rowLabel' => 'Person two', 'document' => [$assets['second']->id]],
            ],
        ]);
        if (!\Craft::$app->getElements()->saveElement($submission, false)) {
            throw new \RuntimeException('Cannot seed CP upload submission.');
        }
        $data = ['id' => $submission->id, 'editUrl' => $submission->getCpEditUrl(), 'assets' => array_map(fn($asset) => $asset->id, $assets)];
    } else {
        $submission = \verbb\formie\elements\Submission::find()->id((int)($_GET['id'] ?? 0))->formId($form->id)->status(null)->one();
        if (!$submission) {
            throw new \RuntimeException('Missing CP upload submission.');
        }
        $files = [];
        foreach (['topDocument', 'details.document', 'people.0.document', 'people.1.document', 'people.2.document', 'people.3.document', 'reviewers.0.document', 'reviewers.1.document'] as $fieldPath) {
            $value = $submission->getFieldValue($fieldPath);
            $files[$fieldPath] = $value instanceof \craft\elements\db\AssetQuery ? array_map(fn($asset) => [
                'id' => $asset->id, 'sha256' => hash('sha256', $asset->getContents()),
            ], $value->all()) : [];
        }
        $data = ['id' => $submission->id, 'name' => $submission->getFieldValue('visitorName'), 'files' => $files, 'people' => array_map(fn($row) => $row['rowLabel'], $submission->getFieldValue('people'))];
    }
    $app->getResponse()->format = \yii\web\Response::FORMAT_JSON;
    $app->getResponse()->data = $data;
    $app->getResponse()->send();
    exit;
}
