<?php

declare(strict_types=1);

use craft\elements\User;
use craft\helpers\Db;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\crm\Agile;

beforeEach(function(): void {
    $this->agileTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function(): void {
    $this->agileTransaction->rollBack();
    Craft::$app->getInfo()->configVersion = (new \craft\db\Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
    Craft::$app->getProjectConfig()->reset();
    Formie::$plugin->getForms()->invalidateFormCaches();
});

function requestAgileTaskSettings(bool $mapToTask): array
{
    $form = formie()->form(['title' => 'Agile Tasks ' . uniqid()])->singleLineTextField('message')->create();
    $integration = new Agile([
        'name' => 'Agile',
        'handle' => 'agile' . bin2hex(random_bytes(4)),
        'enabled' => true,
        'apiKey' => 'test-key',
        'apiEmail' => 'agile@example.test',
        'apiDomain' => 'example',
    ]);

    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();

    // The settings endpoint only asks a connected provider for its fields.
    Db::update(Table::FORMIE_INTEGRATIONS, ['cache' => json_encode(['connection' => Integration::CONNECT_SUCCESS])], ['id' => $integration->id]);

    return WebRequestTestHelper::withWebRequestContext(function($request) use ($form, $integration, $mapToTask): array {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'formId' => $form->id,
            'integration' => $integration->handle,
            'dataKey' => 'task',
            'settings' => ['enabled' => true, 'mapToTask' => $mapToTask, 'mapToContact' => false, 'mapToDeal' => false],
        ]);

        return (new IntegrationsController('integrations', Formie::$plugin))->runAction('form-settings')->data;
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
}

it('loads Agile task fields when only tasks are mapped', function (): void {
    $data = requestAgileTaskSettings(true);

    expect(array_column($data['task'] ?? [], 'handle'))->toBe(['type', 'priority_type', 'due', 'is_complete', 'subject', 'progress', 'status']);
});

it('loads no Agile task fields when tasks are not mapped', function (): void {
    expect(requestAgileTaskSettings(false)['task'] ?? [])->toBe([]);
});
