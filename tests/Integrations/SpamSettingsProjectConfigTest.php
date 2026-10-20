<?php

declare(strict_types=1);

use craft\events\ConfigEvent;
use verbb\formie\helpers\Table;
use verbb\formie\services\Integrations;
use verbb\formie\services\SpamProtection;

beforeEach(function (): void {
    $this->spamSettingsTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function (): void {
    $this->spamSettingsTransaction->rollBack();
});

it('preserves all local spam settings when obsolete project config handlers are called', function (string $scope, bool $delete): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
    $spam = new SpamProtection();
    $localValues = array_merge($spam->getDefaultValues(), [
        'scope' => Integrations::SCOPE_SITE,
        'spamKeywords' => 'production-only-keyword',
        'spamLimit' => 73,
        'saveSpam' => false,
        'minimumSubmitTime' => 17,
        'enableBlockedEmailDomains' => true,
        'blockedEmailDomains' => 'blocked.example',
        'enableIpSubmissionThrottling' => true,
        'ipSubmissionThrottleMinutes' => 9,
    ]);
    expect($spam->saveValues($localValues))->toBeTrue();

    // Seed either ownership state without invoking a project-config event during setup.
    $id = $spam->getRow()['id'];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SPAM_SETTINGS, ['scope' => $scope], ['id' => $id])->execute();
    $spam = new SpamProtection();
    $before = $spam->getRow();
    $projectValues = array_merge($spam->getDefaultValues(), [
        'spamKeywords' => 'deployed-keyword',
        'spamLimit' => 24,
        'minimumSubmitTime' => 6,
    ]);

    if ($delete) {
        $spam->handleDeletedSettings(new ConfigEvent(['name' => 'remove', 'oldValue' => $projectValues]));
    } else {
        $spam->handleChangedSettings(new ConfigEvent(['name' => 'update', 'newValue' => $projectValues]));
    }

    // Reload to verify persisted state, not just the service's cached values.
    $reloaded = new SpamProtection();

    expect($reloaded->getRow())->toBe($before)
        ->and($spam->getSettingsValues())->toBe($reloaded->getSettingsValues());
})->with([Integrations::SCOPE_SITE, Integrations::SCOPE_PROJECT])->with([false, true]);

it('ignores obsolete project config even when no local record exists', function (): void {
    Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_SPAM_SETTINGS)->execute();
    $spam = new SpamProtection();
    $values = array_merge($spam->getDefaultValues(), ['spamKeywords' => 'initial-project-keyword']);

    $spam->handleChangedSettings(new ConfigEvent(['name' => 'add', 'newValue' => $values]));

    $reloaded = new SpamProtection();
    expect($reloaded->getRow())->toBeNull()
        ->and($reloaded->getSettingsValues())->toEqual($reloaded->getDefaultValues());
});
