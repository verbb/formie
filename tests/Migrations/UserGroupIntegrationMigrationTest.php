<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use verbb\formie\integrations\elements\User as UserIntegration;
use verbb\formie\migrations\m241128_000000_user_group_integrations;
use verbb\formie\migrations\m261124_300000_user_group_integration_uids;

beforeEach(function(): void {
    $this->transaction = Craft::$app->getDb()->beginTransaction();
    $this->handle = 'legacyUsers' . bin2hex(random_bytes(4));

    // Use the seeded group: saving a new one would write project config that rolling back can't undo.
    $this->group = Craft::$app->getUserGroups()->getGroupByHandle('formieTestUsers');
    expect($this->group)->not->toBeNull();

    Craft::$app->getDb()->createCommand()->insert('{{%formie_integrations}}', [
        'name' => 'Legacy users',
        'handle' => $this->handle,
        'scope' => 'site',
        'type' => UserIntegration::class,
        'sortOrder' => 999,
        'enabled' => true,
        'settings' => Json::encode([]),
        'uid' => StringHelper::UUID(),
    ])->execute();
});

afterEach(function(): void {
    $this->transaction->rollBack();
});

function legacyUserGroupForm(string $handle, ?array $binding): int
{
    $form = formie()->form()->singleLineTextField('email')->create();
    $settings = Json::decode((new Query())->select('settings')->from('{{%formie_forms}}')->where(['id' => $form->id])->scalar());

    if ($binding !== null) {
        $settings['integrations'][$handle] = $binding;
    }

    Craft::$app->getDb()->createCommand()->update('{{%formie_forms}}', ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();

    return $form->id;
}

function storedUserGroupBinding(int $formId, string $handle): ?array
{
    $settings = Json::decode((new Query())->select('settings')->from('{{%formie_forms}}')->where(['id' => $formId])->scalar());

    return $settings['integrations'][$handle] ?? null;
}

it('converts the user groups of every form with a legacy User integration', function(): void {
    $legacy = ['enabled' => true, 'groupIds' => [(string)$this->group->id]];
    $formIds = [
        legacyUserGroupForm($this->handle, $legacy),
        legacyUserGroupForm($this->handle, $legacy),
        legacyUserGroupForm($this->handle, $legacy),
    ];

    // The last form read has no integration, so the old migration converted nothing at all.
    legacyUserGroupForm($this->handle, null);

    expect((new m241128_000000_user_group_integrations())->safeUp())->toBeTrue();

    foreach ($formIds as $formId) {
        expect(storedUserGroupBinding($formId, $this->handle)['groupUids'] ?? null)->toBe([$this->group->uid]);
    }
});

it('repairs forms the earlier migration skipped and keeps groups chosen since', function(): void {
    $skipped = legacyUserGroupForm($this->handle, ['enabled' => true, 'groupIds' => [(string)$this->group->id]]);
    // A CP save of an empty checkbox list stores an empty string.
    $cleared = legacyUserGroupForm($this->handle, ['enabled' => true, 'groupIds' => [(string)$this->group->id], 'groupUids' => '']);
    $clearedLegacy = legacyUserGroupForm($this->handle, ['enabled' => true, 'groupIds' => '']);
    $converted = legacyUserGroupForm($this->handle, ['enabled' => true, 'groupIds' => ['999999'], 'groupUids' => [$this->group->uid]]);
    $unknown = legacyUserGroupForm($this->handle, ['enabled' => true, 'groupIds' => ['999999', 'not-an-id']]);

    // The repair reports each binding it changes; keep that out of the test output.
    $migration = new m261124_300000_user_group_integration_uids();
    ob_start();

    try {
        expect($migration->safeUp())->toBeTrue()
            ->and($migration->safeUp())->toBeTrue();
    } finally {
        $output = ob_get_clean();
    }

    expect($output)->toContain('User integration');

    expect(storedUserGroupBinding($skipped, $this->handle)['groupUids'])->toBe([$this->group->uid])
        ->and(storedUserGroupBinding($cleared, $this->handle)['groupUids'])->toBe('')
        ->and(array_key_exists('groupUids', storedUserGroupBinding($clearedLegacy, $this->handle)))->toBeFalse()
        ->and(storedUserGroupBinding($converted, $this->handle)['groupUids'])->toBe([$this->group->uid])
        ->and(array_key_exists('groupUids', storedUserGroupBinding($unknown, $this->handle)))->toBeFalse();
});
