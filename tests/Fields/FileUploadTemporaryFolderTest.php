<?php

declare(strict_types=1);

use craft\db\Query;
use craft\db\Table;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;

beforeEach(fn() => UploadTestHelper::ensureUploadVolume());

function fileUploadTemporaryFolderCount(): int
{
    // Craft keeps each visitor's Temporary Uploads folder under a top folder with no volume.
    $topFolderIds = (new Query())
        ->select(['id'])
        ->from(Table::VOLUMEFOLDERS)
        ->where(['volumeId' => null, 'parentId' => null])
        ->column();

    if (!$topFolderIds) {
        return 0;
    }

    return (int)(new Query())
        ->from(Table::VOLUMEFOLDERS)
        ->where(['parentId' => $topFolderIds])
        ->count();
}

it('renders a file upload field for a guest without creating a temporary uploads folder', function (): void {
    $form = formie()
        ->form(['title' => 'Guest File Upload ' . uniqid()])
        ->fileUploadField('attachments')
        ->create();

    expect($form->getFieldByHandle('attachments')->uploadLocationSource)->not->toBe('');

    $before = fileUploadTemporaryFolderCount();

    $html = WebRequestTestHelper::withWebRequestContext(function() use ($form): string {
        expect(Craft::$app->getUser()->getIsGuest())->toBeTrue();

        return (string)Formie::$plugin->getRendering()->renderForm($form);
    });

    expect($html)->toContain('type="file"')
        ->and(fileUploadTemporaryFolderCount())->toBe($before);
});
