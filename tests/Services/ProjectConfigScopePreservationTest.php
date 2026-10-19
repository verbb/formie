<?php

declare(strict_types=1);

use craft\events\ConfigEvent;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\records\Integration as IntegrationRecord;
use verbb\formie\records\Stencil as StencilRecord;
use verbb\formie\services\Integrations;
use verbb\formie\services\Stencils;

function scopePreservationFixture(string $kind): array
{
    $uid = StringHelper::UUID();
    $config = ['name' => 'Local configuration', 'handle' => 'scopeTest' . bin2hex(random_bytes(6))];

    if ($kind === 'integration') {
        $record = new IntegrationRecord();
        $service = new Integrations();
        $config += ['type' => Mailchimp::class, 'enabled' => false, 'sortOrder' => 1, 'settings' => ['apiKey' => 'local-api-key']];
        $methods = ['handleChangedIntegration', 'handleDeletedIntegration'];
        $events = [Integrations::EVENT_AFTER_SAVE_INTEGRATION, Integrations::EVENT_BEFORE_APPLY_INTEGRATION_DELETE, Integrations::EVENT_AFTER_DELETE_INTEGRATION];
    } else {
        $record = new StencilRecord();
        $service = new Stencils();
        $config += ['data' => ['pages' => [], 'notifications' => [], 'settings' => ['submitActionMessage' => 'Local confirmation']]];
        $methods = ['handleChangedStencil', 'handleDeletedStencil'];
        $events = [Stencils::EVENT_AFTER_SAVE_STENCIL, Stencils::EVENT_BEFORE_APPLY_STENCIL_DELETE, Stencils::EVENT_AFTER_DELETE_STENCIL];
    }

    $record->setAttributes($config + ['uid' => $uid], false);

    return [$record, $service, $config, $methods, $events];
}

beforeEach(function (): void {
    $this->scopePreservationTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function (): void {
    $this->scopePreservationTransaction->rollBack();
});

it('preserves instance-owned records and applies project-owned changes', function (string $kind, string $scope, string $operation): void {
    [$record, $service, $config, $methods, $events] = scopePreservationFixture($kind);
    $record->scope = $scope;
    expect($record->save(false))->toBeTrue();

    if ($operation === 'update-trashed') {
        expect($record->softDelete())->toBe(1);
    }

    $recordClass = get_class($record);
    $before = $recordClass::findWithTrashed()->where(['id' => $record->id])->one()->getAttributes();
    $observedEvents = [];

    foreach ($events as $name) {
        $service->on($name, function () use (&$observedEvents, $name): void {
            $observedEvents[] = $name;
        });
    }

    $config['name'] = 'Deployed configuration';

    if ($kind === 'integration') {
        $config['settings']['apiKey'] = 'deployed-api-key';
    } else {
        $config['data']['settings']['submitActionMessage'] = 'Deployed confirmation';
    }

    $delete = $operation === 'remove';
    $method = $methods[$delete ? 1 : 0];
    $service->$method(new ConfigEvent([
        'name' => $delete ? 'remove' : 'update',
        'tokenMatches' => [$record->uid],
        'newValue' => $delete ? null : $config,
    ]));

    $saved = $recordClass::findWithTrashed()->where(['id' => $record->id])->one();
    expect($saved)->not->toBeNull();

    if ($scope === Integrations::SCOPE_SITE) {
        expect($saved->getAttributes())->toBe($before)
            ->and($observedEvents)->toBe([]);
    } elseif ($delete) {
        expect($saved->dateDeleted)->not->toBeNull()
            ->and($observedEvents)->toBe([$events[1], $events[2]]);
    } else {
        expect($saved->scope)->toBe(Integrations::SCOPE_PROJECT)
            ->and($saved->dateDeleted)->toBeNull()
            ->and($saved->name)->toBe('Deployed configuration')
            ->and($saved->uid)->toBe($record->uid)
            ->and($observedEvents)->toBe([$events[0]]);

        if ($kind === 'integration') {
            expect(Json::decodeIfJson($saved->settings)['apiKey'])->toBe('deployed-api-key');
        } else {
            expect(Json::decodeIfJson($saved->data)['settings']['submitActionMessage'])->toBe('Deployed confirmation');
        }
    }
})->with(['integration', 'stencil'])->with([Integrations::SCOPE_SITE, Integrations::SCOPE_PROJECT])->with(['update', 'remove', 'update-trashed']);

it('creates project-owned records when a config UID has no existing record', function (string $kind): void {
    [$record, $service, $config, $methods] = scopePreservationFixture($kind);
    $method = $methods[0];
    $service->$method(new ConfigEvent(['name' => 'add', 'tokenMatches' => [$record->uid], 'newValue' => $config]));

    $recordClass = get_class($record);
    $saved = $recordClass::find()->where(['uid' => $record->uid])->one();
    expect($saved)->not->toBeNull()
        ->and($saved->scope)->toBe(Integrations::SCOPE_PROJECT)
        ->and($saved->name)->toBe($config['name']);
})->with(['integration', 'stencil']);
