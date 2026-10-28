<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormSettings;

it('imports legacy redirect targets and reloads propagated forms', function (array $target, string $expected): void {
    $export = [
        'title' => 'Legacy redirect target',
        'handle' => 'legacyRedirectTarget',
        'settings' => Json::encode(array_merge([
            'submitMethod' => 'ajax',
            'submitAction' => 'message',
        ], $target)),
        'pages' => [['label' => 'Page 1', 'settings' => [], 'rows' => []]],
        'notifications' => [],
        'exportVersion' => 'v3',
    ];

    $imported = ImportExportHelper::importFormFromJson($export, 'create');

    expect($imported->getErrors())->toBe([])
        ->and($imported->id)->toBeGreaterThan(0);

    $stored = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $imported->id])->scalar());
    expect($stored['redirectTarget'])->toBe($expected)
        ->and($stored)->not->toHaveKey('submitActionTab');

    foreach (Craft::$app->getSites()->getAllSites() as $site) {
        $reloaded = Form::find()->id($imported->id)->siteId($site->id)->status(null)->one();
        expect($reloaded)->not->toBeNull()
            ->and($reloaded->settings->redirectTarget)->toBe($expected)
            ->and($reloaded->settings->completionBehavior)->toBe('message');
    }

    $roundTrip = ImportExportHelper::importFormFromJson(ImportExportHelper::generateFormExport($imported), 'update');
    expect($roundTrip->id)->toBe($imported->id)
        ->and($roundTrip->settings->redirectTarget)->toBe($expected);
})->with([
    'null legacy target' => [['submitActionTab' => null], 'same-tab'],
    'missing target' => [[], 'same-tab'],
    'same tab' => [['submitActionTab' => 'same-tab'], 'same-tab'],
    'new tab' => [['submitActionTab' => 'new-tab'], 'new-tab'],
    'null canonical target' => [['redirectTarget' => null], 'same-tab'],
    'canonical takes precedence' => [['redirectTarget' => 'new-tab', 'submitActionTab' => null], 'new-tab'],
]);

it('defaults explicit null targets without resetting omitted completion settings', function (array $values, string $expected): void {
    $settings = new FormSettings([
        'completionBehavior' => 'redirect',
        'completionRedirectSource' => 'entry',
        'redirectTarget' => 'new-tab',
    ]);
    $settings->setAttributes($values, false);

    expect($settings->redirectTarget)->toBe($expected)
        ->and($settings->completionBehavior)->toBe('redirect')
        ->and($settings->completionRedirectSource)->toBe('entry');

    $reloaded = new FormSettings($settings->toArray());
    expect($reloaded->redirectTarget)->toBe($expected);
})->with([
    'legacy null' => [['submitActionTab' => null], 'same-tab'],
    'canonical null' => [['redirectTarget' => null], 'same-tab'],
    'omitted target' => [['displayFormTitle' => true], 'new-tab'],
    'canonical precedence' => [['redirectTarget' => 'new-tab', 'submitActionTab' => null], 'new-tab'],
]);

it('continues rejecting invalid non-null redirect targets', function (string $target): void {
    expect(fn() => new FormSettings(['submitActionTab' => $target]))->toThrow(ValueError::class);

    $settings = new FormSettings();
    $settings->setAttributes(['submitActionTab' => $target], false);
    expect(fn() => new FormSettings($settings->toArray()))->toThrow(ValueError::class);
})->with(['', 'invalid']);
