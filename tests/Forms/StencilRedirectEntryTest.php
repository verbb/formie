<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\FormsController;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils;

/**
 * An entry that exists in the primary site and a secondary one, so a stored site can't pass by chance.
 *
 * @return array{Entry, int}
 */
function stencilRedirectEntryFixture(): array
{
    $suffix = bin2hex(random_bytes(5));
    $sites = Craft::$app->getSites();
    $primary = $sites->getPrimarySite();
    $site = $sites->getSiteByHandle('translationOne') ?? $primary;

    $layout = new FieldLayout(['type' => Entry::class]);
    $tab = new FieldLayoutTab(['name' => 'Content']);
    $tab->setLayout($layout);
    $tab->setElements([new EntryTitleField()]);
    $layout->setTabs([$tab]);

    $type = new EntryType(['name' => 'Redirect ' . $suffix, 'handle' => 'redirectType' . $suffix, 'hasTitleField' => true]);
    $type->setFieldLayout($layout);
    expect(Craft::$app->getEntries()->saveEntryType($type))->toBeTrue();

    $section = new Section(['name' => 'Redirect ' . $suffix, 'handle' => 'redirectSection' . $suffix, 'type' => Section::TYPE_CHANNEL]);
    $section->setEntryTypes([$type]);
    $section->setSiteSettings(array_map(fn($siteId) => new Section_SiteSettings([
        'siteId' => $siteId,
        'enabledByDefault' => true,
        'hasUrls' => true,
        'uriFormat' => 'thanks/{slug}',
    ]), array_unique([$primary->id, $site->id])));
    expect(Craft::$app->getEntries()->saveSection($section))->toBeTrue();

    $entry = new Entry(['sectionId' => $section->id, 'typeId' => $type->id, 'title' => 'Thanks', 'slug' => 'thanks-' . $suffix]);
    expect(Craft::$app->getElements()->saveElement($entry))->toBeTrue();

    return [$entry, (int)$site->id];
}

function stencilRedirectStencil(string $scope, Entry $entry, int $siteId): Stencil
{
    $stencil = new Stencil([
        'name' => 'Redirect stencil',
        'handle' => 'redirectStencil' . bin2hex(random_bytes(5)),
        'scope' => $scope,
        'data' => new StencilData(['settings' => ['completionBehavior' => 'redirect', 'completionRedirectSource' => 'entry']]),
    ]);
    $stencil->redirectEntryId = $entry->id;
    $stencil->redirectEntrySiteId = $siteId;
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();

    return $stencil;
}

function stencilRedirectColumns(string $table, int $id): array
{
    $row = (new Query())->select(['redirectEntryId', 'redirectEntrySiteId'])->from($table)->where(['id' => $id])->one();

    return array_map(fn($value) => $value === null ? null : (int)$value, $row);
}

it('stores the stencil redirect entry with its chosen site', function (string $scope): void {
    [$entry, $siteId] = stencilRedirectEntryFixture();
    $stencil = stencilRedirectStencil($scope, $entry, $siteId);

    expect(stencilRedirectColumns(Table::FORMIE_STENCILS, $stencil->id))->toBe(['redirectEntryId' => $entry->id, 'redirectEntrySiteId' => $siteId]);

    if ($scope === Stencils::SCOPE_PROJECT) {
        $config = Craft::$app->getProjectConfig()->get(Stencils::CONFIG_STENCILS_KEY . '.' . $stencil->uid);

        expect($config['redirectEntry'] ?? null)->toBe($entry->uid)
            ->and($config['redirectEntrySite'] ?? null)->toBe(Craft::$app->getSites()->getSiteById($siteId)->uid);
    }

    $saved = Formie::$plugin->getStencils()->getStencilById($stencil->id);
    $saved->redirectEntryId = null;
    $saved->redirectEntrySiteId = null;
    expect(Formie::$plugin->getStencils()->saveStencil($saved))->toBeTrue()
        ->and(stencilRedirectColumns(Table::FORMIE_STENCILS, $stencil->id))->toBe(['redirectEntryId' => null, 'redirectEntrySiteId' => null]);
})->with([Stencils::SCOPE_SITE, Stencils::SCOPE_PROJECT]);

it('applies the stencil redirect entry to forms created from it', function (string $scope, string $source): void {
    [$entry, $siteId] = stencilRedirectEntryFixture();
    $stencil = stencilRedirectStencil($scope, $entry, $siteId);
    $settings = Formie::$plugin->getSettings();
    $defaultStencil = $settings->defaultFormStencil;
    $settings->defaultFormStencil = $source === 'default stencil' ? $stencil->handle : '';

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($stencil, $source, $entry, $siteId): void {
            Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
            $request->setIsCpRequest(true);
            $post = ['title' => 'From redirect stencil', 'handle' => 'fromRedirectStencil' . bin2hex(random_bytes(5))];

            if ($source === 'selected stencil') {
                $post['applyStencilId'] = $stencil->id;
            }

            $request->setBodyParams($post);
            $form = Formie::$plugin->getForms()->buildFormFromPost();
            expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

            $stored = Form::find()->id($form->id)->one();
            expect(stencilRedirectColumns(Table::FORMIE_FORMS, $form->id))->toBe(['redirectEntryId' => $entry->id, 'redirectEntrySiteId' => $siteId])
                ->and($stored->settings->completionBehavior)->toBe('redirect')
                ->and($stored->getRedirectEntry()?->id)->toBe($entry->id)
                ->and((int)$stored->getRedirectEntry()?->siteId)->toBe($siteId);
        }, ['method' => 'POST']);
    } finally {
        $settings->defaultFormStencil = $defaultStencil;
    }
})->with([Stencils::SCOPE_SITE, Stencils::SCOPE_PROJECT])->with(['selected stencil', 'default stencil']);

it('keeps the form redirect entry when saving a form as a new stencil', function (): void {
    [$entry, $siteId] = stencilRedirectEntryFixture();
    $form = formie()->form(['title' => 'Redirect source'])->singleLineTextField('email')->create();
    $handle = 'savedRedirectStencil' . bin2hex(random_bytes(5));
    $projectConfig = Craft::$app->getProjectConfig();
    $mutex = Craft::$app->getMutex();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $handle, $entry, $siteId, $projectConfig, $mutex): void {
        Craft::$app->set('projectConfig', $projectConfig);
        Craft::$app->set('mutex', $mutex);
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $request->setIsCpRequest(true);
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'id' => $form->id,
            'siteId' => $form->siteId,
            'title' => 'Saved redirect stencil',
            'handle' => $handle,
            'redirectEntry' => [['id' => $entry->id, 'siteId' => $siteId]],
            'settings' => ['completionBehavior' => 'redirect', 'completionRedirectSource' => 'entry'],
        ]);

        $response = (new FormsController('forms', Formie::$plugin))->runAction('save-as-stencil');
        expect($response->data['success'] ?? null)->toBeTrue();
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    $stencil = Formie::$plugin->getStencils()->getStencilByHandle($handle);
    expect($stencil)->not->toBeNull()
        ->and(stencilRedirectColumns(Table::FORMIE_STENCILS, $stencil->id))->toBe(['redirectEntryId' => $entry->id, 'redirectEntrySiteId' => $siteId]);
});
