<?php

use verbb\formie\Formie;
use verbb\formie\integrations\helpdesk\HelpScout;
use verbb\formie\records\FieldInstanceRecord;

it('shares the canonical status service with both legacy access paths', function (): void {
    $plugin = Formie::$plugin;
    expect($plugin->getStatuses())->toBe($plugin->getSubmissionStatuses())
        ->and($plugin->get('statuses'))->toBe($plugin->getSubmissionStatuses());
});

it('relates payment fields to form field instances', function (): void {
    expect((new \verbb\formie\records\Payment())->getField()->modelClass)->toBe(FieldInstanceRecord::class)
        ->and((new \verbb\formie\records\Subscription())->getField()->modelClass)->toBe(FieldInstanceRecord::class);
});

it('uses bundled help desk icons and respects third party icon ownership', function (): void {
    $builtin = new HelpScout();
    expect(is_file(dirname(__DIR__, 2) . '/src/web/assets/cp/dist/' . $builtin->getCpIconPath()))->toBeTrue();
    $custom = new class extends HelpScout {
        public function getIconUrl(): string { return 'https://example.test/custom.svg'; }
    };
    expect($custom->getCpIconUrl('/dist'))->toBe('https://example.test/custom.svg');
});
