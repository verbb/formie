<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\services\Integrations;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use yii\base\Event;

it('stops a swallowed transport failure from being retried and requires reconciliation before an operator rerun', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $integration = new class(['name' => 'Recovery fixture', 'handle' => 'recoveryFixture']) extends \verbb\formie\base\Integration {
        public static int $calls = 0;
        public static ?\GuzzleHttp\Client $mockClient = null;
        protected function createDeliveryHttpHandler(): callable { return self::$mockClient->getConfig('handler'); }
        public function getClient() { return self::$mockClient; }
        public static function displayName(): string { return 'Recovery fixture'; }
        public function fetchConfig(): \verbb\formie\models\IntegrationConfig { return new \verbb\formie\models\IntegrationConfig(); }
        public function sendPayload(\verbb\formie\elements\Submission $submission): bool {
            self::$calls++;
            try {
                $this->request('POST', 'https://example.com/records', ['json' => ['name' => 'Test']]);
                return true;
            } catch (Throwable) {
                return false;
            }
        }
    };
    $integration::$mockClient = new \GuzzleHttp\Client(['base_uri' => 'https://example.com/', 'handler' => \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler([
        new \GuzzleHttp\Exception\ConnectException('Response lost', new \GuzzleHttp\Psr7\Request('POST', 'https://example.com')),
        new \GuzzleHttp\Psr7\Response(200, [], '{"id":"record-1"}'),
    ]))]);
    $handler = function (ModifyFormIntegrationsEvent $event) use ($form, $integration): void {
        if ($event->form->id === $form->id) $event->integrations[] = $integration;
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    try {
        $run = fn() => Formie::$plugin->getIntegrationRunner()->runSteps($submission, [$integration->handle], ['triggerEvent' => 'submit'], null, 'persisted-job');
        expect($run()->results()[0]['result']->status->value)->toBe('unknown');
        expect($run()->results()[0]['result']->status->value)->toBe('unknown');
        expect($integration::$calls)->toBe(1);
        expect(Formie::$plugin->getIntegrationTriggers()->dispatchManualIntegration($integration, $submission)->status->value)->toBe('unknown');
        expect($integration::$calls)->toBe(1);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    }
});
