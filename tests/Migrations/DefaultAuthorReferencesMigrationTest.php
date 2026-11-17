<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\migrations\m261117_000000_default_author_references;

// Formie 3 kept the default author as `["id"]`, which the builder's element select can't show.
function defaultAuthorMigrationValue(int $formId, string $handle): mixed
{
    $settings = Json::decode((string)(new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $formId])->scalar());

    return $settings['integrations'][$handle]['defaultAuthorId'] ?? 'missing';
}

it('stores Formie 3 default authors as the references the builder shows', function(): void {
    $integration = new Entry(['name' => 'Legacy author', 'handle' => 'legacyAuthor' . bin2hex(random_bytes(5)), 'enabled' => true]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
    $siteId = Craft::$app->getSites()->getPrimarySite()->id;
    $stored = [
        'list' => ['6109'],
        'scalar' => '42',
        'reference' => [['id' => 7, 'siteId' => 2]],
        'empty' => '',
        'emptyList' => [],
        'blankList' => [''],
    ];
    $forms = [];

    try {
        foreach ($stored as $key => $value) {
            $form = formie()->form()->create();
            $settings = Json::decode((string)(new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
            $settings['integrations'][$integration->handle] = ['enabled' => true, 'defaultAuthorId' => $value];
            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();
            $forms[$key] = $form;
        }

        expect((new m261117_000000_default_author_references())->safeUp())->toBeTrue();

        expect(defaultAuthorMigrationValue($forms['list']->id, $integration->handle))->toBe([['id' => 6109, 'siteId' => $siteId]])
            ->and(defaultAuthorMigrationValue($forms['scalar']->id, $integration->handle))->toBe([['id' => 42, 'siteId' => $siteId]])
            ->and(defaultAuthorMigrationValue($forms['reference']->id, $integration->handle))->toBe([['id' => 7, 'siteId' => 2]])
            ->and(defaultAuthorMigrationValue($forms['empty']->id, $integration->handle))->toBe('')
            ->and(defaultAuthorMigrationValue($forms['emptyList']->id, $integration->handle))->toBe([])
            ->and(defaultAuthorMigrationValue($forms['blankList']->id, $integration->handle))->toBe(['']);
    } finally {
        foreach ($forms as $form) {
            Craft::$app->getElements()->deleteElement($form, true);
        }

        Formie::$plugin->getIntegrations()->deleteIntegrationById($integration->id);
    }
});
