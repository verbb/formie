<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\fields\Html;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261124_100000_legacy_html_field_twig;

function legacyHtmlFieldId(string $handle, array $settings): int
{
    Db::insert(Table::FORMIE_FIELDS, [
        'type' => Html::class,
        'label' => $handle,
        'handle' => $handle,
        'settings' => Json::encode($settings),
    ]);

    return (int)(new Query())->select('id')->from(Table::FORMIE_FIELDS)->where(['handle' => $handle])->orderBy(['id' => SORT_DESC])->scalar();
}

function legacyHtmlFieldSettings(int $id): array
{
    return Json::decode((string)(new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $id])->scalar());
}

// Sites upgraded from Formie 3 have "Allow Twig in HTML Fields" on, which a fresh install doesn't.
function withHtmlFieldTwigSetting(bool $enabled, callable $callback): void
{
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->allowHtmlFieldTwig;
    $settings->allowHtmlFieldTwig = $enabled;

    try {
        $callback();
    } finally {
        $settings->allowHtmlFieldTwig = $previous;
    }
}

it('turns on allow twig for upgraded html fields that use twig', function (): void {
    withHtmlFieldTwigSetting(true, function (): void {
    $ids = [
        'twig' => legacyHtmlFieldId('legacyTwigHtml', ['htmlContent' => '<p>{{ form.title }}</p>', 'purifyContent' => true]),
        'tag' => legacyHtmlFieldId('legacyTagHtml', ['htmlContent' => '{% if form %}<p>Hi</p>{% endif %}']),
        'plain' => legacyHtmlFieldId('legacyPlainHtml', ['htmlContent' => '<p>No Twig here, just {braces}.</p>']),
        'override' => legacyHtmlFieldId('legacyOverrideHtml', ['htmlContent' => '<p>Plain in the primary site</p>']),
        'explicitOff' => legacyHtmlFieldId('currentHtmlOff', ['htmlContent' => '<p>{{ form.title }}</p>', 'allowTwig' => false]),
        'explicitOn' => legacyHtmlFieldId('currentHtmlOn', ['htmlContent' => '<p>Plain</p>', 'allowTwig' => true]),
    ];
    // Save the translation the way the form builder does, so the stored shape is real.
    $multiSite = Craft::$app->getIsMultiSite();

    if ($multiSite) {
        $site = array_values(array_filter(Craft::$app->getSites()->getAllSites(), fn($site) => !$site->primary))[0];
        Formie::$plugin->getFieldSiteOverrides()->saveOverride($ids['override'], $site->id, ['htmlContent' => '<p>{{ siteName }}</p>']);
    }

    try {
        $migration = new m261124_100000_legacy_html_field_twig();
        expect($migration->safeUp())->toBeTrue();

        expect(legacyHtmlFieldSettings($ids['twig']))->toMatchArray(['allowTwig' => true, 'htmlContent' => '<p>{{ form.title }}</p>', 'purifyContent' => true])
            ->and(legacyHtmlFieldSettings($ids['tag'])['allowTwig'] ?? null)->toBeTrue()
            ->and(legacyHtmlFieldSettings($ids['override'])['allowTwig'] ?? null)->toBe($multiSite ? true : null)
            ->and(legacyHtmlFieldSettings($ids['plain']))->not->toHaveKey('allowTwig')
            ->and(legacyHtmlFieldSettings($ids['explicitOff'])['allowTwig'])->toBeFalse()
            ->and(legacyHtmlFieldSettings($ids['explicitOn'])['allowTwig'])->toBeTrue();

        // A repeat run leaves the result alone.
        expect($migration->safeUp())->toBeTrue()
            ->and(legacyHtmlFieldSettings($ids['plain']))->not->toHaveKey('allowTwig');
    } finally {
        Db::delete(Table::FORMIE_FIELD_SITE_OVERRIDES, ['fieldId' => array_values($ids)]);
        Db::delete(Table::FORMIE_FIELDS, ['id' => array_values($ids)]);
    }
    });
});

it('turns on allow twig for upgraded html fields in stencils, including nested fields', function (): void {
    withHtmlFieldTwigSetting(true, function (): void {
    $handle = 'legacyHtmlTwigStencil' . random_int(1000, 9999);
    $data = [
        'pages' => [[
            'label' => 'Page 1',
            'rows' => [[
                'fields' => [
                    ['type' => Html::class, 'settings' => ['handle' => 'notice', 'htmlContent' => '<p>{{ currentUser.name ?? "Guest" }}</p>']],
                    ['type' => Html::class, 'settings' => ['handle' => 'plain', 'htmlContent' => '<p>Plain</p>']],
                    ['type' => Html::class, 'settings' => ['handle' => 'off', 'htmlContent' => '{{ form.title }}', 'allowTwig' => false]],
                    ['type' => 'verbb\\formie\\fields\\Group', 'settings' => ['handle' => 'group', 'rows' => [[
                        'fields' => [['type' => Html::class, 'settings' => ['handle' => 'nested', 'htmlContent' => '{% set x = 1 %}{{ x }}']]],
                    ]]]],
                ],
            ]],
        ]],
    ];
    Db::insert(Table::FORMIE_STENCILS, ['name' => 'Legacy HTML Twig', 'handle' => $handle, 'data' => Json::encode($data)]);

    try {
        expect((new m261124_100000_legacy_html_field_twig())->safeUp())->toBeTrue();

        $stored = Json::decode((string)(new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['handle' => $handle])->scalar());
        $fields = $stored['pages'][0]['rows'][0]['fields'];

        expect($fields[0]['settings']['allowTwig'] ?? null)->toBeTrue()
            ->and($fields[1]['settings'])->not->toHaveKey('allowTwig')
            ->and($fields[2]['settings']['allowTwig'])->toBeFalse()
            ->and($fields[3]['settings']['rows'][0]['fields'][0]['settings']['allowTwig'] ?? null)->toBeTrue()
            ->and($fields[3]['settings'])->not->toHaveKey('allowTwig');
    } finally {
        Db::delete(Table::FORMIE_STENCILS, ['handle' => $handle]);
    }
    });
});

it('leaves upgraded html fields alone when twig in html fields is turned off', function (): void {
    // With the setting off, a field with Allow Twig on renders nothing and stops its form from saving.
    withHtmlFieldTwigSetting(false, function (): void {
    $id = legacyHtmlFieldId('legacyTwigHtmlPolicyOff', ['htmlContent' => '<p>{{ form.title }}</p>']);
    $handle = 'legacyHtmlTwigPolicyOff' . random_int(1000, 9999);
    Db::insert(Table::FORMIE_STENCILS, ['name' => 'Legacy HTML Twig policy off', 'handle' => $handle, 'data' => Json::encode([
        'pages' => [['label' => 'Page 1', 'rows' => [['fields' => [
            ['type' => Html::class, 'settings' => ['handle' => 'notice', 'htmlContent' => '<p>{{ form.title }}</p>']],
        ]]]]],
    ])]);

    try {
        expect((new m261124_100000_legacy_html_field_twig())->safeUp())->toBeTrue()
            ->and(legacyHtmlFieldSettings($id))->not->toHaveKey('allowTwig');

        $stored = Json::decode((string)(new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['handle' => $handle])->scalar());
        expect($stored['pages'][0]['rows'][0]['fields'][0]['settings'])->not->toHaveKey('allowTwig');
    } finally {
        Db::delete(Table::FORMIE_STENCILS, ['handle' => $handle]);
        Db::delete(Table::FORMIE_FIELDS, ['id' => $id]);
    }
    });
});
