<?php

declare(strict_types=1);

use craft\helpers\UrlHelper;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;

it('renders browser endpoints using Craft action routing', function (string $trigger, ?string $pathParam, string $displayType): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['title' => 'Browser endpoints'])
        ->singleLineTextField('name')
        ->fileUploadField('attachments', ['displayType' => $displayType])
        ->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $trigger, $pathParam, $displayType): void {
        $general = Craft::$app->getConfig()->getGeneral();
        $originalTrigger = $general->actionTrigger;
        $originalPathParam = $general->pathParam;
        $general->actionTrigger = $trigger;
        $general->pathParam = $pathParam;

        try {
            $payload = Formie::$plugin->getServerRenderPayloadBuilder()->buildServerRenderPayload($form, ['renderCss' => false, 'renderJs' => false]);
            $document = new DOMDocument();
            @$document->loadHTML('<?xml encoding="utf-8" ?>' . $payload['html']);
            $element = $document->getElementsByTagName('form')->item(0);
            expect($element->getAttribute('data-formie-refresh-tokens-endpoint'))->toBe(UrlHelper::actionUrl('formie/server/forms/refresh-tokens'))
                ->and($element->getAttribute('data-formie-clear-submission-endpoint'))->toBe(UrlHelper::actionUrl('formie/server/submissions/clear-submission'))
                ->and($element->getAttribute('data-formie-submit-endpoint'))->toBe(UrlHelper::actionUrl('formie/server/submissions/submit'));

            $xpath = new DOMXPath($document);
            $attributes = ['hydrate'];

            if ($displayType === 'uploadManager') {
                $attributes = ['upload', 'delete', 'hydrate'];
            }

            foreach ($attributes as $action) {
                $name = "data-formie-file-upload-{$action}-endpoint";
                $nodes = $xpath->query("//*[@{$name}]");
                expect($nodes->length)->toBeGreaterThan(0);

                foreach ($nodes as $node) {
                    expect($node->getAttribute($name))->toBe(UrlHelper::actionUrl("formie/file-upload/{$action}"));
                }
            }
        } finally {
            $general->actionTrigger = $originalTrigger;
            $general->pathParam = $originalPathParam;
        }
    });
})->with([
    'default' => ['actions', null],
    'custom' => ['custom-actions', null],
    'query routing' => ['custom-actions', 'p'],
])->with(['fileInput', 'uploadManager']);
