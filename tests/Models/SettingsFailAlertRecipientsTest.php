<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\models\Settings;

it('requires manual alert emails or a user group when fail alerts are enabled', function (): void {
    $settings = new Settings([
        'sendEmailAlerts' => true,
    ]);

    expect($settings->validate())->toBeFalse()
        ->and($settings->getErrors('alertEmails'))->not->toBeEmpty();
});

it('accepts a user group without manual alert emails when fail alerts are enabled', function (): void {
    $group = Craft::$app->getUserGroups()->getGroupByHandle('formieTestUsers');

    expect($group)->not->toBeNull();

    $settings = new Settings([
        'sendEmailAlerts' => true,
        'alertEmailsUserGroup' => $group->uid,
    ]);

    expect($settings->validate())->toBeTrue();
});

it('resolves fail alert recipients from a craft user group', function (): void {
    $group = Craft::$app->getUserGroups()->getGroupByHandle('formieTestUsers');

    expect($group)->not->toBeNull();

    $user = User::find()->status(null)->username('formie-seed-user')->one();

    expect($user)->not->toBeNull();

    Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);

    $settings = new Settings([
        'sendEmailAlerts' => true,
        'alertEmailsUserGroup' => $group->uid,
    ]);

    $recipients = $settings->getFailAlertRecipients();

    expect($recipients)->toHaveCount(1)
        ->and($recipients[0]['email'])->toBe('formie-seed-user@example.test')
        ->and($recipients[0]['name'])->toBe('Formie Seed');
});

it('deduplicates manual and user group fail alert recipients by email', function (): void {
    $group = Craft::$app->getUserGroups()->getGroupByHandle('formieTestUsers');

    expect($group)->not->toBeNull();

    $user = User::find()->status(null)->username('formie-seed-user')->one();

    expect($user)->not->toBeNull();

    Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);

    $settings = new Settings([
        'sendEmailAlerts' => true,
        'alertEmails' => [
            ['email' => 'formie-seed-user@example.test'],
        ],
        'alertEmailsUserGroup' => $group->uid,
    ]);

    expect($settings->getFailAlertRecipients())->toHaveCount(1);
});

it('normalizes legacy name and email alert rows to email-only rows', function (): void {
    $settings = new Settings([
        'sendEmailAlerts' => true,
        'alertEmails' => [
            ['Primary Name', 'admin@site.com'],
        ],
    ]);

    expect($settings->getAlertEmailRows())->toBe([
        ['email' => 'admin@site.com'],
    ])
        ->and($settings->getParsedAlertEmails())->toBe(['admin@site.com'])
        ->and($settings->validate())->toBeTrue();
});

it('migrates the legacy alertEmailsUserGroupUid setting key', function (): void {
    $settings = new Settings([
        'sendEmailAlerts' => true,
        'alertEmailsUserGroupUid' => 'group-uid-123',
    ]);

    expect($settings->alertEmailsUserGroup)->toBe('group-uid-123');
});
