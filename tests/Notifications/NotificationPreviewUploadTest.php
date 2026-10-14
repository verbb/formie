<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use craft\base\FsInterface;
use craft\elements\Asset;
use craft\models\Volume;
use verbb\formie\fields\FileUpload;
use verbb\formie\Formie;
use verbb\formie\models\EmailPreviewAsset;
use verbb\formie\services\Emails;

it('keeps sample upload query projections and links without a stored asset', function (): void {
    $asset = UploadTestHelper::seedAsset('private-preview-control.txt', 'Private preview control', UploadTestHelper::ensureUploadVolume());
    $field = new FileUpload(['handle' => 'upload']);
    $query = $field->getValueForEmailPreview(\Faker\Factory::create());

    expect($field->normalizeValue($query, null))->toBe($query)
        ->and($query->count())->toBe(1)
        ->and($query->one())->toBeInstanceOf(EmailPreviewAsset::class)
        ->and($query->one()->id)->toBeNull()
        ->and($query->one()->beforeSave(true))->toBeFalse()
        ->and($field->getValueAsData($query, null)[0]['filename'])->toBe('sample-upload.txt')
        ->and($field->getValueAsString($query, null))->toContain('sample-upload.txt')
        ->and($field->getValueForExport($query, null))->toContain('sample-upload.txt');

    foreach (['url', 'cpUrl'] as $mode) {
        $field->emailFieldSummaryValue = $mode;
        expect((string)$field->getValueForSummary($query, null))->toContain('href=', 'sample-upload.txt')
            ->not->toContain($asset->filename);
    }

    $sample = $query->one();
    expect($sample->getUrl())->toMatch('/^https?:\/\//')
        ->and($sample->getCpEditUrl())->toBe($sample->getUrl());
    $publishedPath = Craft::$app->getAssetManager()->getPublishedPath(dirname(__DIR__, 2) . '/src/resources/email-preview/sample-upload.txt');
    expect(file_get_contents($publishedPath))->toBe($sample->getPreviewContents());

    $query->clearCachedResult();
    expect($query->all())->toBe([]);
});

it('preserves real attachment bytes and enforces size limits for sample attachments', function (): void {
    $asset = UploadTestHelper::seedAsset('real-upload-control.txt', 'Real upload content', UploadTestHelper::ensureUploadVolume());
    $method = new ReflectionMethod(Emails::class, '_attachAssetsToEmail');
    $message = Craft::$app->getMailer()->compose();
    $sample = new EmailPreviewAsset();
    $method->invoke(Formie::$plugin->getEmails(), [$asset, $sample], $message);
    $attachments = $message->getSymfonyEmail()->getAttachments();

    expect($attachments)->toHaveCount(2)
        ->and($attachments[0]->getFilename())->toBe($asset->filename)
        ->and($attachments[0]->getBody())->toBe('Real upload content')
        ->and($attachments[1]->getBody())->toBe($sample->getPreviewContents());

    $settings = Formie::$plugin->getSettings();
    $originalLimit = $settings->maxEmailAttachmentSizeMb;

    try {
        $settings->maxEmailAttachmentSizeMb = 1;
        foreach ([0, 2 * 1024 * 1024] as $size) {
            $sample->size = $size;
            $message = Craft::$app->getMailer()->compose();
            $method->invoke(Formie::$plugin->getEmails(), [$sample], $message);
            expect($message->getSymfonyEmail()->getAttachments())->toBe([]);
        }
    } finally {
        $settings->maxEmailAttachmentSizeMb = $originalLimit;
    }
});

it('skips unreadable remote attachments without failing the notification', function(): void {
    $filesystem = test()->createMock(FsInterface::class);
    $volume = test()->createMock(Volume::class);
    $volume->method('getFs')->willReturn($filesystem);

    $asset = new class ($volume) extends Asset {
        public function __construct(private readonly Volume $testVolume)
        {
            parent::__construct();
        }

        public function getVolume(): Volume
        {
            return $this->testVolume;
        }

        public function getCopyOfFile(): string
        {
            throw new RuntimeException('Remote object is unavailable.');
        }
    };
    $asset->filename = 'unreadable.txt';
    $asset->size = 128;

    $method = new ReflectionMethod(Emails::class, '_attachAssetsToEmail');
    $message = Craft::$app->getMailer()->compose();
    $method->invoke(Formie::$plugin->getEmails(), [$asset], $message);

    expect($message->getSymfonyEmail()->getAttachments())->toBe([]);
});
