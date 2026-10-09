<?php

declare(strict_types=1);

use craft\db\Query;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use verbb\formie\elements\Form;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\services\Integrations;
use yii\base\Event;

// Execute the published class so the walkthrough cannot drift from its working example.
$guide = file_get_contents(dirname(__DIR__, 2) . '/docs/guides/integrations/building-a-crm-integration-from-scratch.md');
preg_match('~```php \[modules/formieintegration/src/integrations/ExampleCrm.php\]\n<\?php\n(.*?)\n```~s', $guide, $example);
eval(str_replace('namespace modules\\formieintegration\\integrations;', 'namespace Tests\\DocumentedCrm;', $example[1]));

// Keep the published settings and delivery logic; replace only the HTTP transport.
class DocumentedCrmHttpFixture extends Tests\DocumentedCrm\ExampleCrm
{
    public static $handler;
    protected function createDeliveryHttpHandler(): callable { return self::$handler; }
    protected function defineClient(): Client { return new Client(['base_uri' => 'https://8.8.8.8/']); }
}

it('registers the documented CRM and delivers saved nested mappings through a queued job', function (): void {
    $register = function (RegisterIntegrationsEvent $event): void {
        $event->crm[] = DocumentedCrmHttpFixture::class;
    };
    Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $register);
    $service = new Integrations();
    $previousService = Formie::$plugin->getIntegrations();
    Formie::$plugin->set('integrations', $service);
    $history = [];
    $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"id":"contact-123"}')]));
    $stack->push(Middleware::history($history));
    DocumentedCrmHttpFixture::$handler = $stack;
    $connection = new DocumentedCrmHttpFixture([
        'name' => 'Documented CRM', 'handle' => 'documentedCrm' . uniqid(),
        'enabled' => true, 'apiKey' => 'test-api-key',
    ]);
    $queueIdsBefore = (new Query())->select('id')->from('{{%queue}}')->column();

    try {
        expect($service->getAllIntegrationTypes()['crm'])->toContain(DocumentedCrmHttpFixture::class);
        expect($service->saveIntegration($connection))->toBeTrue();
        $form = formie()->form()->groupField('contact', ['rows' => [['fields' => [[
            'type' => SingleLineText::class, 'handle' => 'email', 'label' => 'Email',
        ]]]]])->create();
        $mapping = ['email' => ['kind' => 'reference', 'value' => References::field($form->getFieldByHandle('contact')->getFieldByHandle('email')->reference)]];
        $form->settings->integrations = [$connection->handle => [
            'enabled' => true, 'mapToContact' => true, 'mapToDeal' => false,
            'contactFieldMapping' => $mapping, 'dealFieldMapping' => [],
        ]];
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getForms()->invalidateFormCaches();
        $reloaded = Form::find()->id($form->id)->status(null)->one();
        $saved = $reloaded->settings->integrations[$connection->handle];
        expect($saved['mapToContact'] ?? null)->toBeTrue()
            ->and($saved['contactFieldMapping'] ?? null)->toBe($mapping);
        expect($service->getIntegrationFormSettingsConfig($connection->handle, $reloaded)['defaultValues']['mapToContact'])->toBeTrue();
        $submission = formie()->submission($reloaded)->with(['contact' => ['email' => 'nested@example.com']])->save();
        $run = 'documented-crm-' . uniqid();
        Formie::$plugin->getIntegrationRunner()->queueSteps($submission, [$connection->handle], SubmissionOperation::SUBMIT, ['triggerEvent' => 'submit'], executionKey: $run);
        expect($history)->toBeEmpty();
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $queued = (new Query())->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch', 'executionUid' => $run])->one();
        expect($queued['status'])->toBe('pending');
        $queue = Craft::$app->getQueue();
        $message = $queue->serializer->serialize(new TriggerIntegration(['deliveryAttemptUid' => $queued['uid']]));
        $queueId = (new Query())->select('id')->from('{{%queue}}')->where(['job' => $message])->scalar();
        expect($queueId)->not->toBeFalse();
        expect($queue->executeJob((string)$queueId))->toBeTrue();
        expect((new Query())->from('{{%queue}}')->where(['id' => $queueId])->exists())->toBeFalse();
        expect($attempts->get($queued['uid'])['status'])->toBe('succeeded')
            ->and($history)->toHaveCount(1)
            ->and($history[0]['request']->getMethod())->toBe('POST')
            ->and($history[0]['request']->getUri()->getPath())->toBe('/contacts')
            ->and(json_decode((string)$history[0]['request']->getBody(), true))->toBe([
                'email' => 'nested@example.com', 'firstName' => null, 'lastName' => null,
            ]);
        $result = Formie::$plugin->getIntegrationDispatcher()->loadContext($submission, $run)->getResult($connection->handle);
        expect($result['status'])->toBe('succeeded');
    } finally {
        $newJobs = (new Query())->select(['id', 'job'])->from('{{%queue}}')->where(['not in', 'id', $queueIdsBefore])->all();
        foreach ($newJobs as $row) {
            $job = Craft::$app->getQueue()->serializer->unserialize($row['job']);
            if ($job instanceof TriggerIntegration && isset($queued) && $job->getDeliveryAttemptUid() === $queued['uid']) {
                Craft::$app->getQueue()->release((string)$row['id']);
            }
        }
        if ($connection->id) {
            $service->deleteIntegration($connection);
        }
        Event::off(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $register);
        Formie::$plugin->set('integrations', $previousService);
    }
});
