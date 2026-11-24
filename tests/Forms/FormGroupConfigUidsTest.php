<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\StringHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\ProjectConfigHelper;
use verbb\formie\migrations\m261125_000000_form_group_config_uids;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormGroupSettings;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\services\FormGroups;

function groupConfigUidsStatus(string $name): SubmissionStatus
{
    $status = new SubmissionStatus(['name' => $name, 'handle' => lcfirst(str_replace(' ', '', $name)) . bin2hex(random_bytes(3)), 'color' => 'orange']);
    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue();

    return Formie::$plugin->getSubmissionStatuses()->getStatusByUid($status->uid);
}

function groupConfigUidsSiteIds(): array
{
    $siteIds = Craft::$app->getSites()->getAllSiteIds();

    return Craft::$app->getIsMultiSite() ? [(int)$siteIds[0], (int)$siteIds[1]] : [(int)$siteIds[0]];
}

it('stores allowed statuses and enabled sites by UID in project config, and reads them back as local IDs', function (): void {
    $approved = groupConfigUidsStatus('Uid Approved');
    $rejected = groupConfigUidsStatus('Uid Rejected');
    $siteIds = groupConfigUidsSiteIds();
    $group = new FormGroup(['name' => 'Uid policy', 'handle' => 'uidPolicy' . bin2hex(random_bytes(5)), 'settings' => [
        'allowedStatusIds' => [(int)$approved->id],
        'sitePolicy' => ['enabledSiteIds' => $siteIds, 'propagation' => 'allEnabled'],
    ]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $stored = Craft::$app->getProjectConfig()->get(FormGroups::CONFIG_GROUPS_KEY . '.' . $group->uid . '.settings');
    $siteUids = array_map(fn(int $id) => Craft::$app->getSites()->getSiteById($id, true)->uid, $siteIds);
    expect($stored['allowedStatuses'])->toBe([$approved->uid])
        ->and($stored)->not->toHaveKey('allowedStatusIds')
        ->and($stored['sitePolicy']['enabledSites'])->toBe($siteUids)
        ->and($stored['sitePolicy'])->not->toHaveKey('enabledSiteIds')
        ->and(ProjectConfigHelper::rebuildProjectConfig()['formGroups'][$group->uid]['settings'])->toEqual($stored);

    $form = formie()->form(['groupId' => $group->id])->singleLineTextField('message')->create();
    $reloaded = Formie::$plugin->getFormGroups()->getGroupById($group->id);
    $policy = Formie::$plugin->getFormGroupPolicy();
    expect($reloaded->getSettingsModel()->allowedStatusIds)->toBe([(int)$approved->id])
        ->and($reloaded->getSettingsModel()->getSitePolicyModel()->enabledSiteIds)->toBe($siteIds)
        ->and($policy->isStatusAllowed($form, (int)$approved->id))->toBeTrue()
        ->and($policy->isStatusAllowed($form, (int)$rejected->id))->toBeFalse();
});

it('applies group config from another environment by status and site UID', function (): void {
    $approved = groupConfigUidsStatus('Uid Approved');
    $rejected = groupConfigUidsStatus('Uid Rejected');
    $siteIds = groupConfigUidsSiteIds();
    $chosenSiteId = end($siteIds);
    $chosenSite = Craft::$app->getSites()->getSiteById($chosenSiteId, true);

    // Another environment's YAML names the status and site by UID. Its IDs for them are unknown here.
    $groupUid = StringHelper::UUID();
    $handle = 'deployedPolicy' . bin2hex(random_bytes(5));
    Craft::$app->getProjectConfig()->set(FormGroups::CONFIG_GROUPS_KEY . '.' . $groupUid, [
        'name' => 'Deployed policy', 'handle' => $handle, 'sortOrder' => 99,
        'settings' => ['allowedStatuses' => [$approved->uid], 'sitePolicy' => ['enabledSites' => [$chosenSite->uid], 'propagation' => 'allEnabled']],
    ]);
    $group = Formie::$plugin->getFormGroups()->getGroupByUid($groupUid);
    expect($group)->not->toBeNull()
        ->and($group->getSettingsModel()->allowedStatusIds)->toBe([(int)$approved->id])
        ->and($group->getSettingsModel()->getSitePolicyModel()->enabledSiteIds)->toBe([(int)$chosenSiteId]);

    $form = formie()->form(['groupId' => $group->id, 'defaultStatusId' => $approved->id])->singleLineTextField('message')->create();
    $policy = Formie::$plugin->getFormGroupPolicy();
    expect($policy->isStatusAllowed($form, (int)$approved->id))->toBeTrue()
        ->and($policy->isStatusAllowed($form, (int)$rejected->id))->toBeFalse()
        ->and((int)$form->getDefaultStatus()->id)->toBe((int)$approved->id);

    if (Craft::$app->getIsMultiSite()) {
        $enabledSiteIds = array_map('intval', (new Query())->select('siteId')->from('{{%elements_sites}}')
            ->where(['elementId' => $form->id, 'enabled' => true])->column());
        expect($enabledSiteIds)->toBe([(int)$chosenSiteId]);
    }
});

it('keeps a group restricted when every status and site it allowed has been deleted', function (): void {
    $groupUid = StringHelper::UUID();
    $path = FormGroups::CONFIG_GROUPS_KEY . '.' . $groupUid;
    Craft::$app->getProjectConfig()->set($path, [
        'name' => 'Deleted references', 'handle' => 'deletedReferences' . bin2hex(random_bytes(5)), 'sortOrder' => 99,
        'settings' => ['allowedStatuses' => [StringHelper::UUID()], 'sitePolicy' => ['enabledSites' => [StringHelper::UUID()], 'propagation' => 'allEnabled']],
    ]);
    $group = Formie::$plugin->getFormGroups()->getGroupByUid($groupUid);
    $form = new Form(['groupId' => $group->id]);
    $policy = Formie::$plugin->getFormGroupPolicy();
    $anyStatus = Formie::$plugin->getSubmissionStatuses()->getAllStatuses()[0];

    // Matching nothing is what the stale IDs did, rather than every status and every site.
    expect($policy->getSubmissionStatusesForForm($form))->toBe([])
        ->and($policy->isStatusAllowed($form, (int)$anyStatus->id))->toBeFalse()
        ->and($group->getSettingsModel()->getSitePolicyModel()->enabledSiteIds)->toBe([FormGroupSettings::MISSING_ID]);

    if (Craft::$app->getIsMultiSite()) {
        expect(Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForGroup($group))->toBe([]);
    }

    // Saved without validation, as the field palette does, it stays restricted instead of being stripped.
    expect(Formie::$plugin->getFormGroups()->saveGroup($group, false))->toBeTrue();
    $stored = Craft::$app->getProjectConfig()->get($path . '.settings');
    expect($stored['allowedStatuses'])->toBe([FormGroupSettings::MISSING_UID])
        ->and($stored['sitePolicy']['enabledSites'])->toBe([FormGroupSettings::MISSING_UID])
        ->and($policy->getSubmissionStatusesForForm(new Form(['groupId' => $group->id])))->toBe([]);

    // IDs that no longer exist, as the migration can find, are kept as a restriction too.
    $converted = FormGroupSettings::toConfig(['allowedStatusIds' => [999999], 'sitePolicy' => ['enabledSiteIds' => [999999]]]);
    expect($converted['allowedStatuses'])->toBe([FormGroupSettings::MISSING_UID])
        ->and($converted['sitePolicy']['enabledSites'])->toBe([FormGroupSettings::MISSING_UID]);
});

it('keeps reading group config that stores status and site IDs', function (): void {
    $approved = groupConfigUidsStatus('Uid Approved');
    $siteIds = groupConfigUidsSiteIds();
    $groupUid = StringHelper::UUID();
    Craft::$app->getProjectConfig()->set(FormGroups::CONFIG_GROUPS_KEY . '.' . $groupUid, [
        'name' => 'Legacy policy', 'handle' => 'legacyPolicy' . bin2hex(random_bytes(5)), 'sortOrder' => 99,
        'settings' => ['allowedStatusIds' => [(int)$approved->id], 'sitePolicy' => ['enabledSiteIds' => $siteIds, 'propagation' => 'allEnabled']],
    ]);
    $group = Formie::$plugin->getFormGroups()->getGroupByUid($groupUid);

    expect($group->getSettingsModel()->allowedStatusIds)->toBe([(int)$approved->id])
        ->and($group->getSettingsModel()->getSitePolicyModel()->enabledSiteIds)->toBe($siteIds);
});

it('converts stored status and site IDs to UIDs, unless the config is already at this version', function (string $schema, bool $readOnly, bool $converts): void {
    $projectConfig = Craft::$app->getProjectConfig();
    $approved = groupConfigUidsStatus('Uid Approved');
    $siteIds = groupConfigUidsSiteIds();
    $groupUid = StringHelper::UUID();
    $path = FormGroups::CONFIG_GROUPS_KEY . '.' . $groupUid;
    $legacy = ['allowedStatusIds' => [(int)$approved->id], 'defaults' => ['defaultFormTemplate' => 'kept'], 'sitePolicy' => ['propagation' => 'allEnabled', 'enabledSiteIds' => $siteIds]];
    $projectConfig->set($path, ['name' => 'Migrated policy', 'handle' => 'migratedPolicy' . bin2hex(random_bytes(5)), 'sortOrder' => 99, 'settings' => $legacy]);
    $originalSchema = $projectConfig->get('plugins.formie.schemaVersion', true);
    $originalReadOnly = $projectConfig->readOnly;
    $projectConfig->set('plugins.formie.schemaVersion', $schema === 'current' ? Formie::$plugin->schemaVersion : $schema);

    try {
        $projectConfig->readOnly = $readOnly;

        foreach ([1, 2] as $run) {
            expect((new m261125_000000_form_group_config_uids())->safeUp())->toBeTrue();
        }

        $projectConfig->readOnly = $originalReadOnly;
        $siteUids = array_map(fn(int $id) => Craft::$app->getSites()->getSiteById($id, true)->uid, $siteIds);
        // Project config may reorder keys, so compare without key order.
        expect($projectConfig->get($path . '.settings'))->toEqual($converts
            ? ['defaults' => ['defaultFormTemplate' => 'kept'], 'sitePolicy' => ['propagation' => 'allEnabled', 'enabledSites' => $siteUids], 'allowedStatuses' => [$approved->uid]]
            : $legacy);

        $group = Formie::$plugin->getFormGroups()->getGroupByUid($groupUid);
        expect($group->getSettingsModel()->allowedStatusIds)->toBe([(int)$approved->id])
            ->and($group->getSettingsModel()->getSitePolicyModel()->enabledSiteIds)->toBe($siteIds);
    } finally {
        $projectConfig->readOnly = $originalReadOnly;
        $projectConfig->set('plugins.formie.schemaVersion', $originalSchema);
    }
})->with([
    'older config' => ['4.0.117', false, true],
    'config at this version' => ['current', false, false],
    'read-only config' => ['4.0.117', true, false],
]);
