<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Variables;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\crm\Pardot;
use verbb\formie\services\SubmissionMetadata;

it('captures request metadata and pending form custom data during workflow capture', function (): void {
    $form = new Form();
    $form->id = 5001;
    $form->setSubmissionMetadata([
        'campaignId' => 'summer-sale',
    ]);

    $submission = new Submission();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission): void {
        $_COOKIE['hubspotutk'] = 'hubspot-cookie';
        $_COOKIE['visitor_id12345'] = 'pardot-cookie';

        Formie::$plugin->getSubmissionMetadata()->captureForSubmission($submission, $form);

        expect($submission->getMetadata('custom'))->toBe([
            'campaignId' => 'summer-sale',
        ]);

        expect($submission->getMetadata('request'))->toMatchArray([
            'referrer' => null,
            'ipAddress' => '127.0.0.1',
            'cookies' => [
                'hubspotutk' => 'hubspot-cookie',
                'visitor_id12345' => 'pardot-cookie',
            ],
        ]);
    }, [
        'method' => 'POST',
        'remoteAddr' => '127.0.0.1',
    ]);
});

it('hydrates integration context from persisted submission metadata', function (): void {
    $submission = new Submission();
    $submission->metadata = [
        'v' => SubmissionMetadata::VERSION,
        'request' => [
            'referrer' => 'https://example.test/contact',
            'ipAddress' => '203.0.113.10',
            'cookies' => [
                'hubspotutk' => 'persisted-hutk',
                'visitor_id999-hash' => 'persisted-pardot',
            ],
        ],
        'custom' => [],
    ];

    $context = Formie::$plugin->getSubmissionMetadata()->buildIntegrationContext($submission);

    expect($context)->toMatchArray([
        'referrer' => 'https://example.test/contact',
        'ipAddress' => '203.0.113.10',
        'hubspotutk' => 'persisted-hutk',
        'pardot_tracking' => [
            'visitor_id999-hash' => 'persisted-pardot',
        ],
    ]);
});

it('resolves metadata reference tokens from submission metadata', function (): void {
    $submission = new Submission();
    $submission->metadata = [
        'v' => SubmissionMetadata::VERSION,
        'request' => [
            'referrer' => 'https://example.test/landing',
        ],
        'custom' => [
            'campaignId' => 'winter-promo',
        ],
    ];

    expect(References::parseValue('{metadata:custom.campaignId}', $submission))->toBe('winter-promo');
    expect(References::parseValue('{metadata:request.referrer}', $submission))->toBe('https://example.test/landing');
    expect(Variables::getFieldAndValueForReference('{metadata:custom.campaignId}', $submission)['value'])->toBe('winter-promo');
});

it('prefers persisted metadata over live cookies when populating integration context', function (): void {
    $submission = new Submission();
    $submission->metadata = [
        'v' => SubmissionMetadata::VERSION,
        'request' => [
            'referrer' => 'https://example.test/page',
            'ipAddress' => '198.51.100.4',
            'cookies' => [
                'hubspotutk' => 'stored-hutk',
            ],
        ],
        'custom' => [],
    ];

    WebRequestTestHelper::withWebRequestContext(function () use ($submission): void {
        $_COOKIE['hubspotutk'] = 'live-hutk';

        $hubSpot = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubSpotTest']);
        $hubSpot->populateContext($submission);

        expect($hubSpot->context['hubspotutk'])->toBe('stored-hutk');
        expect($hubSpot->context['referrer'])->toBe('https://example.test/page');

        $pardot = new Pardot(['name' => 'Pardot', 'handle' => 'pardotTest']);
        $submission->metadata['request']['cookies']['visitor_id42'] = 'stored-pardot';
        $pardot->populateContext($submission);

        expect($pardot->context['pardot_tracking'])->toBe([
            'visitor_id42' => 'stored-pardot',
        ]);
    });
});
