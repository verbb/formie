<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\StringHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormGroup;

beforeEach(function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Requires the default multisite runtime.');
    }
});

it('creates a blank form on the selected site and saves its first field', function (int $siteIndex, bool $grouped): void {
    $siteId = (int)Craft::$app->getSites()->getAllSiteIds()[$siteIndex];
    $handle = 'creationSite' . bin2hex(random_bytes(6));
    $group = null;

    if ($grouped) {
        $group = new FormGroup(['name' => 'Creation Site', 'handle' => $handle]);
        expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    }

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($siteId, $handle, $group): void {
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setIsCpRequest(true);
        $post = [
            $request->csrfParam => $request->getCsrfToken(),
            'title' => 'Creation Site',
            'handle' => $handle,
            'groupId' => $group?->id,
            'siteId' => $siteId,
        ];
        $request->setBodyParams($post);
        $response = (new FormsController('forms', Formie::$plugin))->runAction('save');
        expect($response?->data['success'] ?? false)->toBeTrue();

        $form = Form::find()->handle($handle)->siteId($siteId)->one();
        expect($form)->not->toBeNull();
        $reference = StringHelper::UUID();
        $pages = $form->getFormLayout()->getFormBuilderConfig();
        $pages[0]['rows'] = [['fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'message',
            'label' => 'Message',
            'reference' => $reference,
        ]]]];

        // The builder includes temporary field references before definitions exist.
        $request->setBodyParams(array_merge($post, [
            'id' => $form->id,
            'pages' => $pages,
            'translations' => ['fieldOverrides' => [$reference => ['label' => 'Message']]],
        ]));
        $response = (new FormsController('forms', Formie::$plugin))->runAction('save');
        expect($response?->data)->not->toHaveKey('errors');

        Formie::$plugin->getForms()->invalidateFormCaches();
        $stored = Form::find()->id($form->id)->siteId($siteId)->one();
        expect($stored?->getFieldByHandle('message')?->label)->toBe('Message')
            ->and((int)$stored->sourceSiteId)->toBe($siteId)
            ->and((int)(new Query())->select('sourceSiteId')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar())->toBe($siteId);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
})->with(['primary' => 0, 'secondary' => 1, 'other secondary' => 2])->with(['ungrouped' => false, 'grouped' => true]);

it('uses the requested site when validating a created-site-only form', function (string $selection): void {
    $site = Craft::$app->getSites()->getAllSites()[1];
    $group = new FormGroup([
        'name' => 'Created Site Only',
        'handle' => 'createdSiteOnly' . bin2hex(random_bytes(6)),
        'settings' => ['sitePolicy' => ['propagation' => 'createdSiteOnly']],
    ]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($site, $group, $selection): void {
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setIsCpRequest(true);
        Cp::reset();

        try {
            if ($selection === 'body') {
                $request->setBodyParams(['siteId' => $site->id]);
            } elseif ($selection === 'query') {
                $request->setQueryParams(['siteId' => $site->id]);
            } elseif ($selection === 'handle') {
                $request->setQueryParams(['site' => $site->handle]);
            } else {
                Craft::$app->getSites()->setCurrentSite($site);
            }

            $form = new Form(['groupId' => $group->id]);
            expect((int)$form->siteId)->toBe((int)Craft::$app->getSites()->getPrimarySite()->id);
            $propagation = Formie::$plugin->getFormSitePropagation();
            expect($propagation->validateFormSiteAvailability($form))->toBeNull()
                ->and($form->sourceSiteId)->toBe((int)$site->id)
                ->and($propagation->resolveSiteIdsForForm($form))->toBe([(int)$site->id])
                ->and($propagation->resolveCreationSiteIdForForm($form))->toBe((int)$site->id);
        } finally {
            Cp::reset();
        }
    }, ['method' => 'POST']);
})->with(['body', 'query', 'handle', 'current site']);

it('retains explicit source and element sites outside web requests', function (): void {
    $sites = Craft::$app->getSites()->getAllSiteIds();
    $service = Formie::$plugin->getFormSiteOverrides();

    expect($service->resolveSourceSiteIdForNewForm(new Form(['siteId' => $sites[1]])))->toBe((int)$sites[1])
        ->and($service->resolveSourceSiteIdForNewForm(new Form(['siteId' => $sites[1], 'sourceSiteId' => $sites[2]])))->toBe((int)$sites[2]);
});
