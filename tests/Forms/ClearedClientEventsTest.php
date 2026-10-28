<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\ClientEventsHelper;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\models\FieldLayoutPageSettings;

it('migrates legacy-only page events while respecting explicit canonical lists', function (string $legacyKey): void {
    $config = [
        'enableClientEvents' => true,
        $legacyKey => [['label' => 'event', 'value' => 'legacyEvent'], ['label' => 'source', 'value' => 'old']],
    ];
    $settings = new FieldLayoutPageSettings($config);
    expect(ClientEventsHelper::normalizeStoredEvents($settings))->toBe([[
        'event' => 'legacyEvent', 'payload' => [['key' => 'source', 'value' => 'old']],
    ]]);

    foreach ([[], [['event' => 'newEvent', 'payload' => []]]] as $events) {
        $settings = new FieldLayoutPageSettings($config + ['clientEvents' => $events]);
        expect(ClientEventsHelper::normalizeStoredEvents($settings))->toBe($events);
    }

    // A runtime clear must not resurrect the rows retained for legacy readers either.
    $settings = new FieldLayoutPageSettings($config);
    $settings->clientEvents = [];
    expect(ClientEventsHelper::normalizeStoredEvents($settings))->toBe([]);
})->with(['clientEventFields', 'jsGtmEventOptions']);

it('persists clearing and replacing migrated page events without emitting legacy payloads', function (): void {
    $form = formie()->form()->singleLineTextField('example')->create();
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $pages[0]['settings']['enableClientEvents'] = true;
    $pages[0]['settings']['clientEventFields'] = [['label' => 'event', 'value' => 'legacyEvent']];
    $pages[0]['settings']['clientEvents'] = [['event' => 'legacyEvent', 'payload' => []]];

    foreach (['legacyEvent', null, null, 'replacementEvent', null] as $event) {
        $events = $event === null ? [] : [['event' => $event, 'payload' => []]];
        $pages[0]['settings']['clientEvents'] = $events;
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder(json_decode(json_encode($pages), true), $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $pages = $form->getFormLayout()->getFormBuilderConfig();
        $page = $form->getPages()[0];
        $settings = $page->getPageSettings();
        $submission = new Submission();
        $submission->setForm($form);

        expect($pages[0]['settings']['clientEvents'])->toBe($events)
            ->and(ClientEventsHelper::normalizeStoredEvents($settings))->toBe($events);
        $resolved = ClientEventsHelper::resolveForSubmittedPage($form, $submission, (int)$page->id, 'submit');
        expect(array_column($resolved, 'event'))->toBe($event === null ? [] : [$event]);

        $html = WebRequestTestHelper::withWebRequestContext(fn() => (string)Formie::$plugin->getRendering()->renderForm($form));
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $attributes = (new DOMXPath($document))->query('//*[@data-formie-client-event]/@data-formie-client-event');

        if ($event === null) {
            expect($attributes->length)->toBe(0);
        } else {
            expect($attributes->length)->toBe(1)
                ->and(json_decode($attributes->item(0)->nodeValue, true)['fields'][0])->toBe(['label' => 'event', 'value' => $event]);
        }

        $form->title .= ' edited';
    }

    // Clearing the page override still permits the form's configured default events.
    $form->settings->enableDefaultClientEvents = true;
    $form->settings->defaultClientEvents = [['event' => 'formDefaultEvent', 'payload' => []]];
    expect(array_column(ClientEventsHelper::resolveForSubmittedPage($form, $submission, (int)$page->id, 'submit'), 'event'))->toBe(['formDefaultEvent']);
    $form->getPages()[0]->getPageSettings()->enableClientEvents = false;
    expect(ClientEventsHelper::resolveForSubmittedPage($form, $submission, (int)$page->id, 'submit'))->toBe([]);
});
