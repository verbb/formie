<?php

use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Entries;
use verbb\formie\Formie;
use verbb\formie\models\HtmlSubmissionRequest;
use verbb\formie\models\Notification;

beforeEach(function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Requires the multisite test runtime.');
    }
});

it('renders, submits and reloads an entry available only on the source site', function (): void {
    $sites = Craft::$app->getSites();
    $sourceSite = $sites->getPrimarySite();
    $subsite = $sites->getAllSites()[1];
    $dealer = Entry::find()->slug('formie-seed-entry')->siteId($sourceSite->id)->one();
    expect($dealer)->not->toBeNull()
        ->and(Entry::find()->id($dealer->id)->siteId($subsite->id)->status(null)->exists())->toBeFalse();

    // The seeded entry type has no title field.
    Db::update(Table::ELEMENTS_SITES, ['title' => 'Global Motors'], ['elementId' => $dealer->id, 'siteId' => $sourceSite->id]);

    $form = formie()->form(['siteId' => $subsite->id])->settings(['disableCaptchas' => true])
        ->entriesField('dealer', [
            'sourceSiteUid' => $sourceSite->uid,
            'sources' => ['section:' . $dealer->getSection()->uid],
            'required' => true,
            'defaultValue' => [['id' => $dealer->id]],
        ])->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $sourceSite, $subsite, $dealer): void {
        Craft::$app->getSites()->setCurrentSite($subsite);
        $form = Form::find()->id($form->id)->siteId($subsite->id)->one();
        $field = $form->getFieldByHandle('dealer');
        $currentSiteField = clone $field;
        $currentSiteField->sourceSiteUid = '';
        expect($currentSiteField->getElementsQuery()->id($dealer->id)->exists())->toBeFalse();

        $setting = array_column($field->defineFormBuilderGeneralSchema(), null, 'name')['sourceSiteUid'];
        expect($field->sourceSiteUid)->toBe($sourceSite->uid)
            ->and(array_column($setting['options'], 'value'))->toContain('', $sourceSite->uid)
            ->and($field->getDefaultValueQuery()->one()?->siteId)->toBe($sourceSite->id);

        $document = new DOMDocument();
        @$document->loadHTML((string)Formie::$plugin->getRendering()->renderField($form, 'dealer'));
        $selected = (new DOMXPath($document))->query('//select/option[@selected]')->item(0);
        expect($selected?->getAttribute('value'))->toBe((string)$dealer->id)
            ->and(trim($selected->textContent))->toBe('Global Motors');

        $result = runHtmlSubmission(new HtmlSubmissionRequest(['handle' => $form->handle, 'siteId' => $subsite->id, 'submitAction' => 'submit']));
        expect($result->response->success)->toBeTrue();
        $saved = Submission::find()->formId($form->id)->siteId($subsite->id)->one();
        expect($saved)->not->toBeNull();
        $answer = $saved->getFieldValue('dealer');
        expect($answer->one()?->id)->toBe($dealer->id)
            ->and($answer->one()?->siteId)->toBe($sourceSite->id)
            ->and($field->getValueAsString($answer, $saved))->toBe('Global Motors');

        $email = Formie::$plugin->getEmails()->renderEmail(new Notification([
            'name' => 'Dealership enquiry', 'handle' => 'dealership', 'to' => 'staff@example.test', 'from' => 'sender@example.test',
            'subject' => "Enquiry for {field:$field->reference:title}",
            'content' => '<p>New enquiry</p>',
        ]), $saved);
        expect($email['email']->getSymfonyEmail()->getSubject())->toBe('Enquiry for Global Motors');
    }, ['method' => 'POST', 'bodyParams' => ['fields' => ['dealer' => [(string)$dealer->id]]]]);
});

it('rejects entries outside the configured site and handles removed source sites', function (): void {
    $sites = Craft::$app->getSites();
    $primary = $sites->getPrimarySite();
    $id = Entry::find()->siteId($primary->id)->slug('formie-seed-entry')->one()->id;
    $field = new Entries(['handle' => 'dealer']);
    expect($field->getElementsQuery()->siteId)->toBe($sites->getCurrentSite()->id);

    foreach ([$sites->getAllSites()[1]->uid, StringHelper::UUID()] as $uid) {
        $field->sourceSiteUid = $uid;
        $submission = new Submission(['siteId' => $primary->id]);
        $answer = $field->normalizeValueFromRequest([$id], $submission);
        $field->validateRequestSelection($submission);
        expect($answer->ids())->toBe([])
            ->and($submission->hasErrors())->toBeTrue()
            ->and($field->getElementsQuery()->id($id)->exists())->toBeFalse();
    }
});
