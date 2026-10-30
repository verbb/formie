<?php

declare(strict_types=1);

use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\db\AssetQuery;
use craft\errors\GqlException;
use craft\fs\Local;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\models\Volume;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\client\FormsController;
use verbb\formie\fields\Entries;
use verbb\formie\fields\FileUpload;
use verbb\formie\Formie;
use yii\base\Event;

it('builds file upload definitions without preparing asset queries', function (string $displayType, ?int $maxFiles): void {
    $field = new FileUpload([
        'handle' => 'attachments',
        'label' => 'Attachments',
        'displayType' => $displayType,
        'maxFiles' => $maxFiles,
        'allowedKinds' => ['pdf'],
        'restrictFiles' => true,
        'minFileSize' => 1,
        'maxFileSize' => 5,
    ]);
    $queryCount = 0;
    $handler = static function () use (&$queryCount): void {
        $queryCount++;
    };
    Event::on(AssetQuery::class, AssetQuery::EVENT_BEFORE_PREPARE, $handler);

    try {
        $input = $field->getClientRenderedDefinition()['input'];
        expect($field->getClientRenderedDefinition()['input'])->toBe($input);
    } finally {
        Event::off(AssetQuery::class, AssetQuery::EVENT_BEFORE_PREPARE, $handler);
    }

    expect($queryCount)->toBe(0)
        ->and($input)->not->toHaveKey('options')
        ->and($input)->not->toHaveKey('useSearchable')
        ->and($input)->not->toHaveKey('searchableOptionLimit')
        ->and($input)->not->toHaveKey('layout')
        ->and($input)->toMatchArray([
            'fieldKind' => 'file',
            'displayType' => $displayType,
            'multiple' => $maxFiles !== 1,
            'maxFiles' => $maxFiles,
            'allowedKinds' => ['pdf'],
            'restrictFiles' => true,
            'minFileSize' => 1.0,
            'maxFileSize' => 5.0,
            'defaultValue' => [],
        ]);
})->with(['fileInput', 'uploadManager'])->with([null, 1, 3]);

it('never resolves existing options for file upload definitions', function (string $displayType): void {
    $field = new class(['handle' => 'attachments', 'displayType' => $displayType]) extends FileUpload {
        public function getFieldOptions(): array
        {
            throw new LogicException('File upload definitions must not resolve existing assets.');
        }
    };

    expect($field->getClientRenderedDefinition()['input'])->not->toHaveKey('options');
})->with(['fileInput', 'uploadManager']);

it('preserves selectable entry labels and IDs in client definitions', function (): void {
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    expect($entry)->not->toBeNull();
    $field = new Entries([
        'handle' => 'related',
        'labelSource' => 'slug',
        'sourceType' => 'elements',
        'sourceElements' => [['id' => $entry->id]],
    ]);

    expect($field->getClientRenderedDefinition()['input']['options'])->toBe([[
        'label' => $entry->slug,
        'value' => (string)$entry->id,
        'selected' => false,
        'disabled' => false,
    ]]);
});

it('omits unrelated assets from public client bootstrap responses', function (string $transport, string $displayType): void {
    $uploadVolume = UploadTestHelper::ensureUploadVolume();
    $handle = 'clientDefinition' . bin2hex(random_bytes(5));
    $path = dirname(CRAFT_BASE_PATH) . '/' . $handle;
    $filesystem = new Local(['name' => $handle, 'handle' => $handle, 'path' => $path, 'hasUrls' => false]);
    $otherVolume = new Volume(['name' => $handle, 'handle' => $handle, 'fsHandle' => $handle]);
    $asset = null;
    $form = null;

    try {
        FileHelper::createDirectory($path);
        if (!Craft::$app->getFs()->saveFilesystem($filesystem)) {
            throw new RuntimeException(json_encode($filesystem->getErrors()));
        }

        if (!Craft::$app->getVolumes()->saveVolume($otherVolume)) {
            throw new RuntimeException(json_encode($otherVolume->getErrors()));
        }
        $otherVolume = Craft::$app->getVolumes()->getVolumeByHandle($handle);
        $asset = UploadTestHelper::seedAsset('unrelated-client-definition-' . uniqid() . '.txt', 'unrelated asset', $otherVolume);
        expect((int)$asset->volumeId)->not->toBe((int)$uploadVolume->id);
        $settings = [
            'displayType' => $displayType,
            'uploadLocationSource' => 'volume:' . $uploadVolume->uid,
            'maxFiles' => 3,
        ];
        $rows = [['fields' => [array_merge($settings, [
            'type' => FileUpload::class,
            'handle' => 'nestedAttachments',
            'label' => 'Nested Attachments',
        ])]]];
        $form = formie()->form()
            ->fileUploadField('attachments', $settings)
            ->groupField('details', ['rows' => $rows])
            ->repeaterField('people', ['rows' => $rows])
            ->create();

        WebRequestTestHelper::withWebRequestContext(function ($request) use ($transport, $displayType, $form, $asset): void {
            expect(Craft::$app->getUser()->getIsGuest())->toBeTrue();
            // Prove the fixture is visible to the vulnerable default asset query.
            expect(Asset::find()->id($asset->id)->one())->not->toBeNull();
            $gql = Craft::$app->getGql();
            $previousSchema = null;

            try {
                $previousSchema = $gql->getActiveSchema();
            } catch (GqlException) {
            }

            $schema = new GqlSchema([
                'name' => 'File Upload Client Definition',
                'uid' => StringHelper::UUID(),
                // Form read access must not imply asset access; REST needs neither scope.
                'scope' => $transport === 'graphql' ? ['formieForms.' . $form->uid . ':read'] : [],
            ]);
            $gql->setActiveSchema($schema);
            $queryCount = 0;
            $handler = static function () use (&$queryCount): void {
                $queryCount++;
            };
            Event::on(FileUpload::class, FileUpload::EVENT_MODIFY_ELEMENT_QUERY, $handler);

            try {
                if ($transport === 'graphql') {
                    $response = $gql->executeQuery($schema, 'query($handle: String!) {
                        formieClientForm(handle: $handle) { definition }
                    }', ['handle' => $form->handle]);
                    expect($response)->not->toHaveKey('errors');
                    $definition = $response['data']['formieClientForm']['definition'];
                } else {
                    $request->setBodyParams(['handle' => $form->handle]);
                    $controller = new FormsController('client/forms', Formie::$plugin);
                    $response = $controller->runAction('load');
                    expect($response->getStatusCode())->toBe(200);
                    $definition = $response->data['definition'];
                }
            } finally {
                Event::off(FileUpload::class, FileUpload::EVENT_MODIFY_ELEMENT_QUERY, $handler);
                $gql->setActiveSchema($previousSchema);
                $gql->flushCaches();
            }

            $inputs = [];
            $collect = static function (array $node) use (&$collect, &$inputs): void {
                if (($node['input']['fieldKind'] ?? null) === 'file') {
                    $inputs[] = $node['input'];
                }

                foreach ($node as $value) {
                    if (is_array($value)) {
                        $collect($value);
                    }
                }
            };
            $collect($definition);

            expect($inputs)->toHaveCount(3)
                ->and(json_encode($definition))->not->toContain($asset->title)
                ->and($queryCount)->toBe(0);

            foreach ($inputs as $input) {
                expect($input)->not->toHaveKey('options')
                    ->and($input['displayType'])->toBe($displayType)
                    ->and($input['maxFiles'])->toBe(3)
                    ->and($input['multiple'])->toBeTrue();
            }
        }, ['method' => 'POST', 'requestUri' => '/actions/formie/client/forms/load']);
    } finally {
        if ($form) {
            Craft::$app->getElements()->deleteElement($form, true);
        }

        if ($asset) {
            Craft::$app->getElements()->deleteElement($asset, true);
        }

        if ($otherVolume?->id) {
            Craft::$app->getVolumes()->deleteVolume($otherVolume);
        }

        Craft::$app->getFs()->removeFilesystem($filesystem);
        FileHelper::removeDirectory($path);
        UploadTestHelper::resetAssetFolderCaches();
    }
})->with(['graphql', 'rest'])->with(['fileInput', 'uploadManager']);
