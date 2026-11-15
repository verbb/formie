<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\base\Automation;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\events\ModifyAutomationPayloadEvent;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\integrations\automations\Zapier;
use verbb\formie\models\IntegrationRunContext;
use verbb\formie\services\Integrations;
use yii\base\Event;

// Written the way the Formie 3 guide showed: every old name below has to keep working.
class LegacyGuideWebhook extends \verbb\formie\base\Webhook
{
    public ?string $webhook = null;
    public array $sent = [];
    public ?string $url = null;

    public static function displayName(): string
    {
        return 'Example Webhook Integration';
    }

    public function getDescription(): string
    {
        return 'Send your form content to any URL you provide.';
    }

    public function sendPayload(Submission $submission): bool
    {
        $payload = $this->generatePayloadValues($submission);
        $this->url = $this->getWebhookUrl($this->webhook, $submission);
        $this->sent = $payload;

        return true;
    }
}

it('resolves the Formie 3 class names', function(): void {
    expect(is_a(\verbb\formie\base\Webhook::class, Automation::class, true))->toBeTrue()
        ->and(is_a(\verbb\formie\events\ModifyWebhookPayloadEvent::class, ModifyAutomationPayloadEvent::class, true))->toBeTrue();
});

it('registers and delivers a custom integration written against the old names', function(): void {
    $handler = static function(RegisterIntegrationsEvent $event): void {
        $event->webhooks[] = LegacyGuideWebhook::class;
    };
    $resetCaches = new ReflectionMethod(Formie::$plugin->getIntegrations(), '_resetIntegrationCaches');
    Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);
    $resetCaches->invoke(Formie::$plugin->getIntegrations());

    try {
        expect(Formie::$plugin->getIntegrations()->getIntegrationTypes(\verbb\formie\base\Integration::TYPE_AUTOMATION))->toContain(LegacyGuideWebhook::class);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);
        $resetCaches->invoke(Formie::$plugin->getIntegrations());
    }

    $form = formie()->form(['title' => 'Old webhook'])->singleLineTextField('answer')->create();
    $saved = formie()->submission($form)->with(['answer' => 'Sent'])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $integration = new LegacyGuideWebhook(['name' => 'Old', 'handle' => 'oldWebhook', 'webhook' => 'https://93.184.216.34/hook']);
    $integration->sent = [];

    $result = $integration->execute(IntegrationRunContext::forIntegration($integration, $submission));

    expect($result->status)->toBe(IntegrationStatus::Succeeded)
        ->and($integration->url)->toBe('https://93.184.216.34/hook')
        ->and($integration->sent['json'] ?? $integration->sent)->not->toBe([]);
});

it('still fires the old payload event alongside the new one', function(): void {
    $form = formie()->form(['title' => 'Old event'])->singleLineTextField('answer')->create();
    $saved = formie()->submission($form)->with(['answer' => 'Sent'])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $integration = new Zapier(['name' => 'Zapier', 'handle' => 'zapierEvents']);
    $fired = [];
    $old = static function(\verbb\formie\events\ModifyWebhookPayloadEvent $event) use (&$fired): void {
        $fired[] = 'old';
        $event->payload = ['modified' => true];
    };
    $new = static function(ModifyAutomationPayloadEvent $event) use (&$fired): void {
        $fired[] = 'new';
    };
    Event::on(Zapier::class, Zapier::EVENT_MODIFY_WEBHOOK_PAYLOAD, $old);
    Event::on(Zapier::class, Zapier::EVENT_MODIFY_AUTOMATION_PAYLOAD, $new);

    try {
        $payload = (new ReflectionMethod($integration, 'generatePayloadValues'))->invoke($integration, $submission);
    } finally {
        Event::off(Zapier::class, Zapier::EVENT_MODIFY_WEBHOOK_PAYLOAD, $old);
        Event::off(Zapier::class, Zapier::EVENT_MODIFY_AUTOMATION_PAYLOAD, $new);
    }

    expect($fired)->toBe(['new', 'old'])
        ->and($payload)->toBe(['modified' => true]);
});
