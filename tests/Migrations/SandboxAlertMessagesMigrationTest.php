<?php

declare(strict_types=1);

use craft\db\Query;
use craft\db\Table;
use verbb\formie\migrations\m261102_000000_sandbox_alert_messages;

it('rewrites saved alert messages the Twig sandbox would block', function (): void {
    $db = Craft::$app->getDb();
    $rows = [
        'formie_failed_notification' => ['Failed on {{ siteName }}.', 'Response: {{ emailResponse | json_encode }}. Kept: {{ emailResponse | length }}'],
        'formie_failed_integration' => ['{{ integration.name }} failed', '{% if integrationResponse %}{{integrationResponse|json_encode}}{% endif %}'],
        'unrelated_message' => ['{{ integration.name }}', '{{ emailResponse | json_encode }}'],
    ];

    foreach ($rows as $key => [$subject, $body]) {
        $db->createCommand()->insert(Table::SYSTEMMESSAGES, ['language' => 'en-test', 'key' => $key, 'subject' => $subject, 'body' => $body])->execute();
    }

    $read = fn (string $key) => (new Query())->select(['subject', 'body'])->from(Table::SYSTEMMESSAGES)->where(['key' => $key, 'language' => 'en-test'])->one();

    try {
        $migration = new m261102_000000_sandbox_alert_messages(['compact' => true]);
        expect($migration->safeUp())->toBeTrue();

        expect($read('formie_failed_notification'))->toBe(['subject' => 'Failed on {{ siteName }}.', 'body' => 'Response: {{ emailResponseJson }}. Kept: {{ emailResponse | length }}']);
        expect($read('formie_failed_integration'))->toBe(['subject' => '{{ integrationName }} failed', 'body' => '{% if integrationResponse %}{{ integrationResponseJson }}{% endif %}']);
        expect($read('unrelated_message'))->toBe(['subject' => '{{ integration.name }}', 'body' => '{{ emailResponse | json_encode }}']);

        // A repeat run must leave rewritten messages alone.
        $migration->safeUp();
        expect($read('formie_failed_integration')['subject'])->toBe('{{ integrationName }} failed');
    } finally {
        $db->createCommand()->delete(Table::SYSTEMMESSAGES, ['language' => 'en-test'])->execute();
    }
});
