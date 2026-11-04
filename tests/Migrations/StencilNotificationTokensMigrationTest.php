<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;
use verbb\formie\elements\Form;
use verbb\formie\fields\Email;
use verbb\formie\fields\Group;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261104_050000_bare_notification_tokens;
use verbb\formie\migrations\m261105_060000_stencil_notification_tokens;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;

beforeEach(function (): void {
    Craft::$app->getProjectConfig()->flush();
    $this->stencilTokensTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function (): void {
    $this->stencilTokensTransaction->rollBack();
    Craft::$app->getInfo()->configVersion = (new Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
    Craft::$app->getProjectConfig()->reset();
    (new ReflectionProperty(Formie::$plugin->getStencils(), '_stencils'))->setValue(Formie::$plugin->getStencils(), null);
    Formie::$plugin->getForms()->invalidateFormCaches();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
});

function legacyNotificationStencil(string $scope): Stencil
{
    $stencil = new Stencil(['name' => 'Legacy tokens', 'handle' => 'legacyTokens' . uniqid(), 'scope' => $scope]);
    // Formie 3 layouts had handles but no persistent field references.
    $stencil->data = new StencilData([
        'pages' => [['label' => 'Details', 'rows' => [['fields' => [
            ['type' => Email::class, 'settings' => ['label' => 'Email', 'handle' => 'email']],
            ['type' => SingleLineText::class, 'settings' => ['label' => 'Full name', 'handle' => 'fullName']],
            ['type' => Name::class, 'settings' => ['label' => 'Person', 'handle' => 'person', 'useMultipleFields' => true, 'rows' => (new Name())->getSubFields()]],
            ['type' => Group::class, 'settings' => ['label' => 'Details', 'handle' => 'details', 'rows' => [['fields' => [
                ['type' => SingleLineText::class, 'settings' => ['label' => 'City', 'handle' => 'city']],
            ]]]]],
        ]]]]],
        'notifications' => [[
            'name' => 'Legacy', 'handle' => 'legacy', 'enabled' => false,
            'to' => '{email}', 'cc' => '{email}', 'bcc' => '{email}', 'from' => '{email}', 'sender' => '{email}', 'replyTo' => '{email}',
            'replyToName' => '{fullName}', 'fromName' => '{person.firstName}',
            'subject' => 'Hello {fullName} in {details.city}',
            'content' => '[{"type":"paragraph","content":[{"type":"text","text":"Saved {fullName} in {details.city}"}]}]',
        ]],
    ]);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
    Craft::$app->getProjectConfig()->flush();

    return $stencil;
}

function repairedStencilRow(Stencil $stencil): array
{
    return (new Query())->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->one();
}

function materializeNotificationStencil(Stencil $stencil): Form
{
    $row = repairedStencilRow($stencil);
    $loaded = new Stencil(['name' => $row['name'], 'handle' => $row['handle'], 'scope' => $row['scope'], 'data' => new StencilData(Json::decode($row['data']))]);
    $form = new Form(['title' => 'Materialized tokens', 'handle' => 'materializedTokens' . uniqid()]);
    $loaded->applyStencilToForm($form, true);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    return $form;
}

it('repairs legacy stencil tokens and keeps both materializations portable', function (string $scope): void {
    $stencil = legacyNotificationStencil($scope);
    $before = Json::decode(repairedStencilRow($stencil)['data']);
    (new m261105_060000_stencil_notification_tokens())->safeUp();
    $row = repairedStencilRow($stencil);
    $after = Json::decode($row['data']);

    expect($after['notifications'][0]['to'])->toBe('{field:email}')
        ->and($after['notifications'][0]['cc'])->toBe('{field:email}')
        ->and($after['notifications'][0]['bcc'])->toBe('{field:email}')
        ->and($after['notifications'][0]['from'])->toBe('{field:email}')
        ->and($after['notifications'][0]['sender'])->toBe('{field:email}')
        ->and($after['notifications'][0]['replyTo'])->toBe('{field:email}')
        ->and($after['notifications'][0]['replyToName'])->toBe('{field:fullName}')
        ->and($after['notifications'][0]['fromName'])->toBe('{field:person:firstName}')
        ->and($after['pages'])->toBe($before['pages'])
        ->and($after['settings'])->toBe($before['settings'])
        ->and($row['scope'])->toBe($scope);

    $config = Craft::$app->getProjectConfig()->get('formie.stencils.' . $row['uid']);
    if ($scope === 'project') {
        expect(ProjectConfig::unpackAssociativeArrays($config['data'])['notifications'][0]['to'])->toBe('{field:email}');
    } else {
        expect($config)->toBeNull();
    }

    $references = [];
    for ($i = 0; $i < 2; $i++) {
        $form = materializeNotificationStencil($stencil);
        $references[] = $form->getFieldByHandle('email')->reference;
        $submission = formie()->submission($form)->with(['email' => 'ada@example.test', 'fullName' => 'Ada Example', 'person' => ['firstName' => 'Ada', 'lastName' => 'Example'], 'details' => ['city' => 'Geelong']])->save();
        $result = Formie::$plugin->getEmails()->renderEmail($form->getNotifications()[0], $submission);
        expect($result['error'] ?? null)->toBeNull()->and($result['success'] ?? null)->toBeTrue();
        $email = $result['email']->getSymfonyEmail();
        expect(array_map(fn($address) => $address->getAddress(), $email->getTo()))->toBe(['ada@example.test'])
            ->and($email->getSubject())->toBe('Hello Ada Example in Geelong')
            ->and($email->getFrom()[0]->getName())->toBe('Ada')
            ->and((string)$email->getHtmlBody())->toContain('Saved Ada Example in Geelong');
    }
    expect($references[0])->not->toBe($references[1]);
})->with(['site', 'project']);

it('repairs forms created after the previous notification migration already ran', function (): void {
    (new m261104_050000_bare_notification_tokens())->safeUp();
    $stencil = legacyNotificationStencil('site');
    $form = materializeNotificationStencil($stencil);
    $notification = $form->getNotifications()[0];
    expect($notification->to)->toBe('{email}');
    (new m261105_060000_stencil_notification_tokens())->safeUp();
    $stored = (new Query())->select('to')->from(Table::FORMIE_NOTIFICATIONS)->where(['id' => $notification->id])->scalar();
    expect($stored)->toBe('{field:' . $form->getFieldByHandle('email')->reference . '}');
});

it('preserves unknown and non-notification data and is idempotent', function (string $scope): void {
    $stencil = legacyNotificationStencil($scope);
    $row = repairedStencilRow($stencil);
    $data = Json::decode($row['data']);
    $preserved = '{unknown} {email|upper} {{ email }} {details.missing} {form:name} {field:email}';
    $data['notifications'][0]['subject'] = '{title} {id} {uid} ' . $preserved;
    $data['notifications'][0]['name'] = '{email}';
    $data['notifications'][0]['conditions'] = ['conditionRule' => 'show', 'conditionMatch' => 'all', 'conditionRules' => [['field' => '{email}', 'condition' => '=', 'value' => 'fixture']]];
    if ($scope === 'project') {
        Craft::$app->getProjectConfig()->set('formie.stencils.' . $row['uid'] . '.data', ProjectConfig::packAssociativeArrays($data));
        Craft::$app->getProjectConfig()->flush();
    }
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_STENCILS, ['data' => Json::encode($data)], ['id' => $row['id']])->execute();
    $migration = new m261105_060000_stencil_notification_tokens();
    $migration->safeUp();
    $first = repairedStencilRow($stencil);
    $firstData = Json::decode($first['data']);
    expect($firstData['notifications'][0]['subject'])->toBe('{submission:title} {submission:id} {submission:uid} ' . $preserved)
        ->and($firstData['notifications'][0]['name'])->toBe('{email}')
        ->and($firstData['notifications'][0]['conditions'])->toBe($data['notifications'][0]['conditions']);
    $config = Craft::$app->getProjectConfig()->get('formie.stencils.' . $row['uid']);
    $migration->safeUp();
    expect(repairedStencilRow($stencil))->toBe($first)
        ->and(Craft::$app->getProjectConfig()->get('formie.stencils.' . $row['uid']))->toBe($config);
})->with(['site', 'project']);


it('leaves malformed stencil payloads unchanged without blocking the update', function (array $replacement): void {
    $stencil = legacyNotificationStencil('site');
    $row = repairedStencilRow($stencil);
    $data = array_replace(Json::decode($row['data']), $replacement);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_STENCILS, ['data' => Json::encode($data)], ['id' => $row['id']])->execute();
    expect((new m261105_060000_stencil_notification_tokens())->safeUp())->toBeTrue();
    expect(Json::decode(repairedStencilRow($stencil)['data']))->toBe($data);
})->with([
    'no notifications' => [['notifications' => null]],
    'invalid notifications' => [['notifications' => 'not an array']],
    'unreadable layout' => [['pages' => [['label' => 'Invalid', 'rows' => 'not an array']]]],
]);

it('repairs known fields without requiring an unavailable optional field class', function (): void {
    $stencil = legacyNotificationStencil('site');
    $row = repairedStencilRow($stencil);
    $data = Json::decode($row['data']);
    $data['pages'][0]['rows'][0]['fields'][] = ['type' => 'audit\\unavailable\\OptionalField', 'settings' => ['label' => 'Optional', 'handle' => 'optional', 'opaqueSetting' => 'kept']];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_STENCILS, ['data' => Json::encode($data)], ['id' => $row['id']])->execute();
    expect((new m261105_060000_stencil_notification_tokens())->safeUp())->toBeTrue();
    $after = Json::decode(repairedStencilRow($stencil)['data']);
    expect($after['notifications'][0]['to'])->toBe('{field:email}')->and($after['pages'])->toBe($data['pages']);
});
