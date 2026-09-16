<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use Tests\Support\UploadTestHelper;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\types\input\FileUploadInputType;

it('preserves retained ids and new bytes in mixed graphql upload lists', function (bool $reverse): void {
    $payload = [['assetId' => 123], ['filename' => 'new.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode('New content')]];
    $normalized = FileUploadInputType::normalizeValue($reverse ? array_reverse($payload) : $payload);

    expect($normalized[0])->toBe(123)
        ->and(array_values($normalized['mutationData']))->toBe([['filename' => 'new.txt', 'type' => 'data', 'data' => 'New content']]);
})->with([false, true]);

it('retains existing assets and saves new files when updating a submission over graphql', function (bool $reverse, bool $generic): void {
    $contents = 'New upload content';
    $asset = UploadTestHelper::seedAsset('retained.txt', 'Retained content');
    $form = formie()->form()->settings(['disableCaptchas' => true])
        ->fileUploadField('attachments', ['required' => true, 'restrictFiles' => false])->create();
    $submission = formie()->submission($form)->with(['attachments' => [$asset->id]])->save();
    $payload = [
        ['assetId' => (int)$asset->id],
        ['filename' => 'added.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode($contents)],
    ];
    $payload = $reverse ? array_reverse($payload) : $payload;
    $mutation = $generic ? SubmissionMutation::createGenericSaveMutation() : SubmissionMutation::createSaveMutation($form);
    $resolveInfo = $this->createMock(ResolveInfo::class);
    $resolveInfo->fieldDefinition = FieldDefinition::create($mutation);
    $arguments = $generic
        ? ['id' => $submission->id, 'formHandle' => $form->handle, 'fields' => ['attachments' => $payload]]
        : ['id' => $submission->id, 'attachments' => $payload];
    $gql = Craft::$app->getGql();
    $previousSchema = null;
    try {
        $previousSchema = $gql->getActiveSchema();
    } catch (GqlException) {
        // The test runtime may not have an active schema yet.
    }
    $gql->setActiveSchema(new GqlSchema(['name' => 'Mixed uploads', 'scope' => ['formieSubmissions.all:save']]));
    try {
        $saved = ($mutation['resolve'])(null, $arguments, null, $resolveInfo);
    } finally {
        $gql->setActiveSchema($previousSchema);
    }

    $assets = $saved->getFieldValue('attachments')->all();
    expect($saved->id)->toBe($submission->id)
        ->and($assets)->toHaveCount(2)
        ->and(array_map(fn($item) => (int)$item->id, $assets))->toContain((int)$asset->id);
    $newAssets = array_values(array_filter($assets, fn($item) => $item->id != $asset->id));
    expect(file_get_contents($newAssets[0]->getCopyOfFile()))->toBe($contents);
})->with([false, true])->with([false, true]);
