<?php

use craft\base\FsInterface;
use craft\fs\Local;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FileUploadController;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\UploadAccess;
use yii\web\NotFoundHttpException;

it('serves upload bytes through a purpose-bound private preview response', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
        $asset = UploadTestHelper::seedAsset('private-preview.txt', 'private preview bytes');
        $uploads = Formie::$plugin->getFileUploads();
        $uploads->trackSubmissionAsset($asset, (int)$form->id, null, $form->getFieldByHandle('document')->uid, $form, 'document');
        $token = UploadAccess::issueToken((int)$asset->id, (int)$form->id, $form->getFieldByHandle('document')->uid);
        $url = UploadAccess::viewUrl($token);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        expect($url)->toContain('formie/file-upload/view')->and($query['token'])->toBe($token);
        $request->setQueryParams($query);
        $response = (new FileUploadController('file-upload', Craft::$app))->actionView();
        [$stream] = $response->stream;
        try {
            rewind($stream);
            expect(stream_get_contents($stream))->toBe('private preview bytes')
                ->and($response->headers->get('Cache-Control'))->toBe('private, no-store, max-age=0')
                ->and($response->headers->get('Referrer-Policy'))->toBe('no-referrer')
                ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
                ->and($response->headers->get('Content-Disposition'))->toStartWith('attachment;');
        } finally {
            fclose($stream);
            $response->stream = null;
        }
    });
});

it('requires a currently valid view capability to preview a file', function (string $purpose, bool $expired): void {
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($purpose, $expired): void {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document')->create();
        $asset = UploadTestHelper::seedAsset('preview-capability.txt', 'preview');
        Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $form->getFieldByHandle('document')->uid, $form, 'document');
        $token = UploadAccess::issueToken((int)$asset->id, (int)$form->id, $form->getFieldByHandle('document')->uid, $purpose);
        if ($expired) {
            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['expiresAt' => time() - 1], ['assetId' => $asset->id])->execute();
        }
        $request->setQueryParams(['token' => $token]);
        expect(fn() => (new FileUploadController('file-upload', Craft::$app))->actionView())->toThrow(NotFoundHttpException::class);
    });
})->with([['attach', false], ['delete', false], ['view', true]]);

it('refuses to stage files in a temporary filesystem that exposes public URLs', function (): void {
    $assets = Craft::$app->getAssets();
    Craft::$app->set('assets', new class extends \craft\services\Assets {
        public function getTempAssetUploadFs(): FsInterface
        {
            return new Local(['hasUrls' => true, 'url' => 'https://assets.example.test/', 'path' => '/unused']);
        }
    });
    try {
        expect(fn() => Formie::$plugin->getFileUploads()->getStagingFolder())
            ->toThrow(RuntimeException::class, 'private temporary asset filesystem');
    } finally {
        Craft::$app->set('assets', $assets);
    }
});

it('returns a capability URL when hydrating a staged upload', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document')->create();
        $asset = UploadTestHelper::seedAsset('preview-hydration.txt', 'preview');
        $field = $form->getFieldByHandle('document');
        Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $field->uid, $form, 'document');
        $token = UploadAccess::issueToken((int)$asset->id, (int)$form->id, $field->uid);
        $request->setBodyParams(['handle' => $form->handle, 'fieldHandle' => 'document', 'assetIds' => [$asset->id], 'uploadTokens' => [$asset->id => $token]]);
        $response = (new FileUploadController('file-upload', Craft::$app))->actionHydrate();
        expect($response->data['assets'][0]['url'])->toBe(UploadAccess::viewUrl($token));
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
});
