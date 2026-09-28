<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260928_030000_completion_vocabulary;

it('migrates Formie 3 completion settings and redirect columns idempotently', function (): void {
    $db = Craft::$app->getDb();
    $form = formie()->form(['title' => 'Completion vocabulary migration'])->singleLineTextField('name')->create();
    $stencilId = (new Query())->select('id')->from(Table::FORMIE_STENCILS)->scalar();

    $db->createCommand()->update(Table::FORMIE_FORMS, [
        'settings' => Json::encode([
            'submitAction' => 'url',
            'submitActionUrl' => '/legacy-thanks',
            'redirectUrl' => '',
            'submitActionTab' => 'new-tab',
            'submitActionMessage' => '<p>Legacy success.</p>',
            'integrations' => ['example' => ['submitActionUrl' => 'provider-owned-value']],
        ]),
    ], ['id' => $form->id])->execute();
    $db->createCommand()->update(Table::FORMIE_STENCILS, [
        'data' => Json::encode(['settings' => [
            'submitAction' => 'entry',
            'submitActionFormHide' => true,
        ]]),
    ], ['id' => $stencilId])->execute();

    foreach ([Table::FORMIE_FORMS, Table::FORMIE_STENCILS] as $table) {
        $db->createCommand()->renameColumn($table, 'redirectEntryId', 'submitActionEntryId')->execute();
        $db->createCommand()->renameColumn($table, 'redirectEntrySiteId', 'submitActionEntrySiteId')->execute();
    }

    $migration = new m260928_030000_completion_vocabulary();
    expect($migration->safeUp())->toBeTrue()
        ->and($migration->safeUp())->toBeTrue();

    foreach ([Table::FORMIE_FORMS, Table::FORMIE_STENCILS] as $table) {
        expect($db->columnExists($table, 'redirectEntryId'))->toBeTrue()
            ->and($db->columnExists($table, 'redirectEntrySiteId'))->toBeTrue()
            ->and($db->columnExists($table, 'submitActionEntryId'))->toBeFalse()
            ->and($db->columnExists($table, 'submitActionEntrySiteId'))->toBeFalse();
    }

    $storedSettings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
    $storedStencil = Json::decode((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $stencilId])->scalar());
    $reloaded = Form::find()->id($form->id)->status(null)->one();

    expect($storedSettings)->not->toHaveKeys(['submitAction', 'submitActionUrl', 'submitActionTab', 'submitActionMessage'])
        ->and($storedSettings['completionBehavior'])->toBe('redirect')
        ->and($storedSettings['completionRedirectSource'])->toBe('url')
        ->and($storedSettings['redirectUrl'])->toBe('/legacy-thanks')
        ->and($storedSettings['redirectTarget'])->toBe('new-tab')
        ->and($storedSettings['integrations']['example']['submitActionUrl'])->toBe('provider-owned-value')
        ->and($storedStencil['settings']['completionBehavior'])->toBe('redirect')
        ->and($storedStencil['settings']['completionRedirectSource'])->toBe('entry')
        ->and($storedStencil['settings']['hideFormAfterSubmit'])->toBeTrue()
        ->and($reloaded?->settings->redirectUrl)->toBe('/legacy-thanks');
});
