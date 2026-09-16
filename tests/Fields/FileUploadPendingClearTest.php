<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\gql\types\input\FileUploadInputType;

it('discards staged data uploads when a submission field is cleared', function (mixed $clear): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('upload', ['restrictFiles' => false])->create();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'Cleared upload';
    $submission->setFieldValue('upload', FileUploadInputType::normalizeValue([
        ['filename' => 'pending.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode('Pending')],
    ]));
    $field = $form->getFieldByHandle('upload');
    expect($field->isValueEmpty($submission->getFieldValue('upload'), $submission))->toBeFalse();

    // Mirrors clearing a value after condition evaluation has normalized its upload.
    $submission->setFieldValue('upload', $clear);
    expect($field->isValueEmpty($submission->getFieldValue('upload'), $submission))->toBeTrue();
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->isSpam(null)->one();
    expect($saved->getFieldValue('upload')->ids())->toBe([]);
})->with(['null' => [null], 'empty list' => [[]]]);

it('preserves pending files during partial-page payload merging', function (bool $nested): void {
    UploadTestHelper::ensureUploadVolume();
    \verbb\formie\Formie::$plugin->getSettings()->setOnlyCurrentPagePayload = true;
    $factory = formie()->form();
    if ($nested) {
        $factory->groupField('group', ['rows' => [['fields' => [[
            'type' => \verbb\formie\fields\FileUpload::class,
            'handle' => 'upload', 'label' => 'Upload', 'restrictFiles' => false,
        ]]]]]);
    } else {
        $factory->fileUploadField('upload', ['restrictFiles' => false]);
    }
    $form = $factory->create();
    $payload = [['filename' => 'partial.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode('Partial content')]];
    $submission = formie()->submission($form)->with($nested ? ['group' => ['upload' => $payload]] : ['upload' => $payload])->save();
    $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->isSpam(null)->one();
    expect($saved->getFieldValue($nested ? 'group.upload' : 'upload')->all())->toHaveCount(1);
})->with([false, true]);


it('keeps data uploads isolated across rebuilt nested fields and repeater rows', function (bool $partial): void {
    UploadTestHelper::ensureUploadVolume();
    \verbb\formie\Formie::$plugin->getSettings()->setOnlyCurrentPagePayload = $partial;
    $rows = [['fields' => [
        ['type' => \verbb\formie\fields\FileUpload::class, 'handle' => 'first', 'label' => 'First', 'restrictFiles' => false],
        ['type' => \verbb\formie\fields\FileUpload::class, 'handle' => 'second', 'label' => 'Second', 'restrictFiles' => false],
    ]]];
    $form = formie()->form()->groupField('group', ['rows' => $rows])->repeaterField('items', ['rows' => $rows])->create();
    $file = fn(string $text) => [['filename' => $text . '.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode($text)]];
    $submission = formie()->submission($form)->with([
        'group' => ['first' => $file('group-first'), 'second' => $file('group-second')],
        'items' => [
            ['first' => $file('row-one-first'), 'second' => $file('row-one-second')],
            ['first' => $file('row-two-first'), 'second' => $file('row-two-second')],
        ],
    ])->save();
    $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->isSpam(null)->one();
    foreach ([
        'group.first' => 'group-first', 'group.second' => 'group-second',
        'items.0.first' => 'row-one-first', 'items.0.second' => 'row-one-second',
        'items.1.first' => 'row-two-first', 'items.1.second' => 'row-two-second',
    ] as $path => $expected) {
        $assets = $saved->getFieldValue($path)->all();
        expect($assets)->toHaveCount(1);
        expect(file_get_contents($assets[0]->getCopyOfFile()))->toBe($expected);
    }
})->with([false, true]);
