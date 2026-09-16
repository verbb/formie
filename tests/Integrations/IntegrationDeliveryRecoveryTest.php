<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\services\Integrations;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use yii\base\Event;

it('stops a swallowed transport failure from being retried and permits an operator rerun', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $integration = new class(['name' => 'Recovery fixture', 'handle' => 'recoveryFixture']) extends \verbb\formie\base\Integration {
        public int $calls = 0;
        public static function displayName(): string { return 'Recovery fixture'; }
        public function fetchFormSettings(): \verbb\formie\models\IntegrationFormSettings { return new \verbb\formie\models\IntegrationFormSettings(); }
        public function sendPayload(\verbb\formie\elements\Submission $submission): bool {
            $this->calls++;
            try {
                $this->request('POST', 'https://example.test/records', ['json' => ['name' => 'Test']]);
                return true;
            } catch (Throwable) {
                return false;
            }
        }
    };
    $integration->setClient(new \GuzzleHttp\Client(['handler' => \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler([
        new \GuzzleHttp\Exception\ConnectException('Response lost', new \GuzzleHttp\Psr7\Request('POST', 'https://example.test')),
        new \GuzzleHttp\Psr7\Response(200, [], '{"id":"record-1"}'),
    ]))]));
    $handler = function (ModifyFormIntegrationsEvent $event) use ($form, $integration): void {
        if ($event->form->id === $form->id) $event->integrations[] = $integration;
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    try {
        $run = fn() => Formie::$plugin->getIntegrationExecutor()->runQueuedJob($submission, [$integration->handle], 'submit', ['triggerEvent' => 'submit'], false, 'persisted-job');
        expect($run)->toThrow(RuntimeException::class, 'Delivery outcome unknown');
        expect($run)->toThrow(RuntimeException::class, 'Delivery outcome unknown');
        expect($integration->calls)->toBe(1);
        expect(Formie::$plugin->getIntegrationTriggers()->dispatchManualIntegration($integration, $submission))->toBeTrue();
        expect($integration->calls)->toBe(2);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    }
});
