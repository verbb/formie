<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\models\Settings;

it('requires manual integration alert emails or a user group when integration fail alerts are enabled', function (): void {
    $settings = new Settings([
        'sendIntegrationAlerts' => true,
    ]);

    expect($settings->validate())->toBeFalse()
        ->and($settings->getErrors('integrationAlertEmails'))->not->toBeEmpty();
});

it('accepts a user group without manual integration alert emails when integration fail alerts are enabled', function (): void {
    $group = Craft::$app->getUserGroups()->getGroupByHandle('formieTestUsers');

    expect($group)->not->toBeNull();

    $settings = new Settings([
        'sendIntegrationAlerts' => true,
        'integrationAlertEmailsUserGroup' => $group->uid,
    ]);

    expect($settings->validate())->toBeTrue();
});

it('resolves integration fail alert recipients from a craft user group', function (): void {
    $group = Craft::$app->getUserGroups()->getGroupByHandle('formieTestUsers');

    expect($group)->not->toBeNull();

    $user = User::find()->status(null)->username('formie-seed-user')->one();

    expect($user)->not->toBeNull();

    Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);

    $settings = new Settings([
        'sendIntegrationAlerts' => true,
        'integrationAlertEmailsUserGroup' => $group->uid,
    ]);

    $recipients = $settings->getIntegrationFailAlertRecipients();

    expect($recipients)->toHaveCount(1)
        ->and($recipients[0]['email'])->toBe('formie-seed-user@example.test')
        ->and($recipients[0]['name'])->toBe('Formie Seed');
});

it('deduplicates manual and user group integration fail alert recipients by email', function (): void {
    $group = Craft::$app->getUserGroups()->getGroupByHandle('formieTestUsers');

    expect($group)->not->toBeNull();

    $user = User::find()->status(null)->username('formie-seed-user')->one();

    expect($user)->not->toBeNull();

    Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);

    $settings = new Settings([
        'sendIntegrationAlerts' => true,
        'integrationAlertEmails' => [
            ['email' => 'formie-seed-user@example.test'],
        ],
        'integrationAlertEmailsUserGroup' => $group->uid,
    ]);

    expect($settings->getIntegrationFailAlertRecipients())->toHaveCount(1);
});

it('migrates the legacy integrationAlertEmailsUserGroupUid setting key', function (): void {
    $settings = new Settings([
        'sendIntegrationAlerts' => true,
        'integrationAlertEmailsUserGroupUid' => 'group-uid-123',
    ]);

    expect($settings->integrationAlertEmailsUserGroup)->toBe('group-uid-123');
});
