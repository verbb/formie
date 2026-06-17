<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormGroupSettings;
use verbb\formie\models\Status;

it('returns all statuses when no group or form policy is set', function (): void {
    $form = formie()
        ->form(['title' => 'Policy Form'])
        ->singleLineTextField('fullName')
        ->create();

    $allCount = count(Formie::$plugin->getStatuses()->getAllStatuses());
    $policy = Formie::$plugin->getFormGroupPolicy();

    expect($policy->getResolvedAllowedStatusIds($form))->toBeNull()
        ->and($policy->getStatusesForForm($form))->toHaveCount($allCount);
});

it('restricts statuses from a form group policy', function (): void {
    $statuses = Formie::$plugin->getStatuses()->getAllStatuses();
    expect($statuses)->not->toBeEmpty();

    $allowed = array_slice($statuses, 0, 1);
    $group = new FormGroup([
        'name' => 'Policy Group',
        'handle' => 'policyGroup' . uniqid(),
        'settings' => [
            'allowedStatusIds' => [(int)$allowed[0]->id],
        ],
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $form = formie()
        ->form([
            'title' => 'Grouped Policy Form',
            'groupId' => $group->id,
        ])
        ->singleLineTextField('fullName')
        ->create();

    $policy = Formie::$plugin->getFormGroupPolicy();

    expect($policy->getResolvedAllowedStatusIds($form))->toBe([(int)$allowed[0]->id])
        ->and($policy->getStatusesForForm($form))->toHaveCount(1)
        ->and($policy->isStatusAllowed($form, (int)$allowed[0]->id))->toBeTrue();
});

it('ignores legacy form-level allowed status ids in favour of group policy', function (): void {
    $statuses = Formie::$plugin->getStatuses()->getAllStatuses();

    if (count($statuses) < 2) {
        $extra = new Status([
            'name' => 'Policy Test Status',
            'handle' => 'policyTest' . uniqid(),
            'color' => 'orange',
            'sortOrder' => 99,
        ]);
        Formie::$plugin->getStatuses()->saveStatus($extra);
        $statuses = Formie::$plugin->getStatuses()->getAllStatuses();
    }

    expect(count($statuses))->toBeGreaterThanOrEqual(2);

    $group = new FormGroup([
        'name' => 'Legacy Override Group',
        'handle' => 'legacyOverrideGroup' . uniqid(),
        'settings' => [
            'allowedStatusIds' => [(int)$statuses[0]->id],
        ],
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $form = formie()
        ->form([
            'title' => 'Legacy Override Policy Form',
            'groupId' => $group->id,
        ])
        ->singleLineTextField('fullName')
        ->create();

    $form->getSettings()->allowedStatusIds = [(int)$statuses[1]->id];
    Craft::$app->getElements()->saveElement($form);

    $policy = Formie::$plugin->getFormGroupPolicy();

    expect($policy->getResolvedAllowedStatusIds($form))->toBe([(int)$statuses[0]->id])
        ->and($policy->isStatusAllowed($form, (int)$statuses[0]->id))->toBeTrue()
        ->and($policy->isStatusAllowed($form, (int)$statuses[1]->id))->toBeFalse();
});

it('merges group form defaults over global defaults for new forms', function (): void {
    $group = new FormGroup([
        'name' => 'Defaults Group',
        'handle' => 'defaultsGroup' . uniqid(),
        'settings' => [
            'defaults' => [
                'formDefaults' => [
                    'collectIp' => true,
                    'dataRetention' => 'days',
                ],
            ],
        ],
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $merged = Formie::$plugin->getFormGroupPolicy()->getMergedFormDefaults($group);

    expect($merged['collectIp'])->toBeTrue()
        ->and($merged['dataRetention'])->toBe('days');
});

it('normalizes empty allowed status selections to unrestricted policy', function (): void {
    $settings = FormGroupSettings::fromArray([
        'allowedStatusIds' => [],
    ]);

    expect($settings->allowedStatusIds)->toBeNull();
});

it('persists a custom field palette when enabled in the settings payload', function (): void {
    $group = new FormGroup([
        'name' => 'Palette Group',
        'handle' => 'paletteGroup' . uniqid(),
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $payload = Formie::$plugin->getFormGroupDefaults()->getEditorValues($group);
    $payload['useCustomFieldPalette'] = true;
    $payload['fieldPalette'] = [
        'groups' => [
            [
                'uid' => 'group-uid-1',
                'handle' => 'basic',
                'name' => 'Basic',
                'fields' => [
                    [
                        'fieldClass' => 'verbb\\formie\\fields\\SingleLineText',
                        'enabled' => true,
                        'label' => null,
                    ],
                ],
            ],
        ],
        'unassigned' => [],
    ];

    expect(Formie::$plugin->getFormGroupDefaults()->applyPayload($group, $payload))->toBeTrue()
        ->and(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $reloaded = Formie::$plugin->getFormGroups()->getGroupById($group->id);

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->getSettingsModel()->usesCustomFieldPalette())->toBeTrue()
        ->and($reloaded->getSettingsModel()->fieldPalette['groups'][0]['handle'] ?? null)->toBe('basic');

    $editorValues = Formie::$plugin->getFormGroupDefaults()->getEditorValues($reloaded);

    expect($editorValues['fieldPalette']['groups'][0]['fields'][0]['defaultLabel'] ?? null)
        ->not->toBeEmpty();
});

it('adds inherit global default options to form group defaults schema fields', function (): void {
    $group = new FormGroup([
        'name' => 'Schema Group',
        'handle' => 'schemaGroup' . uniqid(),
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $config = Formie::$plugin->getFormGroupDefaults()->getEditorConfig($group);
    $schema = $config['formDefaultsSchema'] ?? [];

    $findField = static function(array $nodes, string $name) use (&$findField): ?array {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            if (($node['name'] ?? null) === $name) {
                return $node;
            }

            foreach (['schema', 'children'] as $key) {
                if (!isset($node[$key]) || !is_array($node[$key])) {
                    continue;
                }

                $found = $findField($node[$key], $name);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    };

    foreach (['submitMethod', 'dataRetention', 'fileUploadsAction'] as $fieldName) {
        $field = $findField($schema, $fieldName);

        expect($field)->not->toBeNull()
            ->and($field['options'][0]['value'] ?? null)->toBe('')
            ->and($field['options'][0]['label'] ?? null)->toBe('Inherit global default');
    }

    $collectIp = $findField($schema, 'collectIp');

    expect($collectIp)->not->toBeNull()
        ->and($collectIp['$field'] ?? null)->toBe('select')
        ->and($collectIp['options'][0]['value'] ?? null)->toBe('')
        ->and($collectIp['options'][0]['label'] ?? null)->toBe('Inherit global default');
});
