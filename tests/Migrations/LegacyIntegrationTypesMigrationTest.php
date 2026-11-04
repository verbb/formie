<?php

use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\automations\WebRequest;
use verbb\formie\integrations\automations\Zapier;
use verbb\formie\integrations\helpdesk\Freshdesk;
use verbb\formie\integrations\helpdesk\Gorgias;
use verbb\formie\integrations\helpdesk\Zendesk;
use verbb\formie\integrations\messaging\Slack;
use verbb\formie\integrations\messaging\Telegram;
use verbb\formie\integrations\elements\CalendarEvent;
use verbb\formie\migrations\m261104_020000_legacy_integration_types;
use verbb\formie\services\Integrations;

function legacyIntegrationInsert(string $handle, string $type, array $settings, bool $enabled = true): string
{
    $uid = StringHelper::UUID();
    Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_INTEGRATIONS, [
        'name' => $handle, 'handle' => $handle, 'type' => $type, 'scope' => Integrations::SCOPE_PROJECT,
        'enabled' => $enabled ? '1' : '0', 'sortOrder' => 90, 'settings' => Json::encode($settings), 'cache' => Json::encode(['connection' => 'success']),
        'uid' => $uid,
    ])->execute();

    return $uid;
}

// Each case reads connections fresh, the way a new request does.
function legacyIntegrationFresh(callable $callback): mixed
{
    $original = Formie::$plugin->get('integrations');
    Formie::$plugin->set('integrations', new Integrations());

    try {
        return $callback(Formie::$plugin->getIntegrations());
    } finally {
        Formie::$plugin->set('integrations', $original);
    }
}

it('resolves connections saved under historical class names to their current integrations', function (): void {
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        $cases = [
            ['crmFreshdesk', 'verbb\\formie\\integrations\\crm\\Freshdesk', Freshdesk::class, ['apiKey' => 'key', 'apiDomain' => 'example.test']],
            ['miscGorgias', 'verbb\\formie\\integrations\\miscellaneous\\Gorgias', Gorgias::class, []],
            ['miscZendesk', 'verbb\\formie\\integrations\\miscellaneous\\Zendesk', Zendesk::class, []],
            ['miscSlack', 'verbb\\formie\\integrations\\miscellaneous\\Slack', Slack::class, []],
            ['miscTelegram', 'verbb\\formie\\integrations\\miscellaneous\\Telegram', Telegram::class, []],
            ['hookWebhook', 'verbb\\formie\\integrations\\webhooks\\Webhook', WebRequest::class, ['webhook' => 'https://hooks.example.test/connection']],
            ['autoWebhook', 'verbb\\formie\\integrations\\automations\\Webhook', WebRequest::class, ['webhook' => 'https://hooks.example.test/old', 'url' => 'https://hooks.example.test/kept']],
            ['hookZapier', 'verbb\\formie\\integrations\\webhooks\\Zapier', Zapier::class, ['webhook' => 'https://hooks.example.test/zap']],
        ];

        foreach ($cases as [$handle, $type, , $settings]) {
            legacyIntegrationInsert($handle, $type, $settings);
        }

        // Disabled stays disabled, and an unrelated connection is untouched.
        legacyIntegrationInsert('hookDisabled', 'verbb\\formie\\integrations\\webhooks\\Zapier', ['webhook' => 'https://hooks.example.test/off'], false);
        legacyIntegrationInsert('customModule', 'modules\\integrations\\Custom', ['anything' => 'stays']);

        expect((new m261104_020000_legacy_integration_types())->safeUp())->toBeTrue();

        legacyIntegrationFresh(function(Integrations $integrations) use ($cases): void {
            foreach ($cases as [$handle, , $class]) {
                expect($integrations->getIntegrationByHandle($handle))->toBeInstanceOf($class);
            }

            expect($integrations->getIntegrationByHandle('hookWebhook')->url)->toBe('https://hooks.example.test/connection')
                ->and($integrations->getIntegrationByHandle('autoWebhook')->url)->toBe('https://hooks.example.test/kept')
                ->and($integrations->getIntegrationByHandle('hookZapier')->webhook)->toBe('https://hooks.example.test/zap')
                ->and($integrations->getIntegrationByHandle('crmFreshdesk')->getEnabled())->toBeTrue()
                ->and($integrations->getIntegrationByHandle('hookDisabled')->getEnabled())->toBeFalse();
        });

        $row = (new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['handle' => 'crmFreshdesk'])->one();
        expect(Json::decode($row['settings']))->toBe(['apiKey' => 'key', 'apiDomain' => 'example.test'])
            ->and(Json::decode($row['cache']))->toBe(['connection' => 'success'])
            ->and((new Query())->select('type')->from(Table::FORMIE_INTEGRATIONS)->where(['handle' => 'customModule'])->scalar())->toBe('modules\\integrations\\Custom');

        // Running again changes nothing.
        $before = (new Query())->select(['handle', 'type', 'settings'])->from(Table::FORMIE_INTEGRATIONS)->orderBy(['id' => SORT_ASC])->all();
        expect((new m261104_020000_legacy_integration_types())->safeUp())->toBeTrue()
            ->and((new Query())->select(['handle', 'type', 'settings'])->from(Table::FORMIE_INTEGRATIONS)->orderBy(['id' => SORT_ASC])->all())->toBe($before);
    } finally {
        $transaction->rollBack();
    }
});

it('selects historical form bindings for delivery and keeps their own web request URL', function (): void {
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        legacyIntegrationInsert('legacyHook', 'verbb\\formie\\integrations\\webhooks\\Webhook', ['webhook' => 'https://hooks.example.test/connection']);
        legacyIntegrationInsert('legacySlack', 'verbb\\formie\\integrations\\miscellaneous\\Slack', []);

        $form = formie()->form()->singleLineTextField('message')->create();
        $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
        $settings['integrations'] = [
            'legacyHook' => ['enabled' => true, 'webhook' => 'https://hooks.example.test/this-form'],
            'legacySlack' => ['enabled' => true],
        ];
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();

        expect((new m261104_020000_legacy_integration_types())->safeUp())->toBeTrue();
        Formie::$plugin->getForms()->invalidateFormCaches();

        legacyIntegrationFresh(function() use ($form): void {
            $reloaded = Form::find()->id($form->id)->status(null)->one();
            $saved = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
            expect($saved['integrations']['legacyHook'])->not->toHaveKey('webhook')
                ->and(Formie::$plugin->getIntegrationRunner()->resolveEnabledHandles($reloaded))->toContain('legacyHook', 'legacySlack');

            $bindings = Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($reloaded);
            $hook = array_values(array_filter($bindings, fn($binding) => $binding->integration->handle === 'legacyHook'))[0];
            expect($hook->createRuntime()->url)->toBe('https://hooks.example.test/this-form');
        });
    } finally {
        $transaction->rollBack();
        Formie::$plugin->getForms()->invalidateFormCaches();
    }
});

it('loads every connection after removing the Calendar site setting that no longer exists', function (): void {
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        legacyIntegrationInsert('legacyCalendar', CalendarEvent::class, ['siteId' => null, 'calendarId' => 3]);
        legacyIntegrationInsert('otherHook', WebRequest::class, ['url' => 'https://hooks.example.test/unrelated']);

        // One stale setting stops every integration on the site from loading.
        legacyIntegrationFresh(fn(Integrations $integrations) => expect(fn() => $integrations->getAllIntegrations())->toThrow(yii\base\UnknownPropertyException::class));

        expect((new m261104_020000_legacy_integration_types())->safeUp())->toBeTrue();

        legacyIntegrationFresh(function(Integrations $integrations): void {
            expect($integrations->getIntegrationByHandle('legacyCalendar'))->toBeInstanceOf(CalendarEvent::class)
                ->and($integrations->getIntegrationByHandle('legacyCalendar')->calendarId)->toBe(3)
                ->and($integrations->getIntegrationByHandle('otherHook')->url)->toBe('https://hooks.example.test/unrelated');
        });
    } finally {
        $transaction->rollBack();
    }
});

it('updates project config without clearing the saved connection state', function (): void {
    $projectConfig = Craft::$app->getProjectConfig();
    $original = $projectConfig->get('formie.integrations');
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        $projectConfig->muteEvents = true;
        $uid = legacyIntegrationInsert('configHook', 'verbb\\formie\\integrations\\webhooks\\Webhook', ['webhook' => 'https://hooks.example.test/config']);
        $projectConfig->set("formie.integrations.{$uid}", [
            'name' => 'configHook', 'handle' => 'configHook', 'type' => 'verbb\\formie\\integrations\\webhooks\\Webhook', 'enabled' => true, 'sortOrder' => 90,
            'settings' => ['webhook' => 'https://hooks.example.test/config'],
        ]);
        $projectConfig->muteEvents = false;

        expect((new m261104_020000_legacy_integration_types())->safeUp())->toBeTrue();

        $config = $projectConfig->get("formie.integrations.{$uid}");
        $row = (new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['uid' => $uid])->one();

        expect($config['type'])->toBe(WebRequest::class)
            ->and($config['settings']['url'])->toBe('https://hooks.example.test/config')
            ->and($config['settings'])->not->toHaveKey('webhook')
            ->and(Json::decode($row['cache']))->toBe(['connection' => 'success']);
    } finally {
        $projectConfig->muteEvents = true;
        $projectConfig->set('formie.integrations', $original);
        $projectConfig->muteEvents = false;
        $transaction->rollBack();
        // The rollback undoes the version bump in the database but not in memory.
        Craft::$app->getInfo()->configVersion = (new Query())->select(['configVersion'])->from(Table::INFO)->scalar();
        $projectConfig->reset();
    }
});
