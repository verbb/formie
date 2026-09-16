<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\base\{Integration, IntegrationInterface};
use verbb\formie\console\controllers\SubmissionsController;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\models\{IntegrationResponse, Notification};
use verbb\formie\services\{Integrations, IntegrationTriggers, Notifications};

function deliveryCommandController(): SubmissionsController
{
    return new class('submissions', Formie::$plugin) extends SubmissionsController {
        public string $out = '';
        public string $err = '';
        public function stdout($string): int { $this->out .= $string; return strlen($string); }
        public function stderr($string): int { $this->err .= $string; return strlen($string); }
    };
}

it('returns a failed notification exit code while continuing the selected batch', function (array $results, int $expectedExit): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $ids = [];
    foreach ($results as $result) { $ids[] = formie()->submission($form)->save()->id; }
    $notification = new Notification(['formId' => $form->id, 'name' => 'Command delivery', 'handle' => 'commandDelivery', 'subject' => 'Delivery', 'to' => 'nobody@example.test']);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();
    $original = Formie::$plugin->getNotifications();
    $service = new class extends Notifications {
        public array $results = [];
        public array $attempts = [];
        public function sendNotificationEmail(Notification $notification, Submission $submission, $queueJob = null, ?string $deliveryKey = null): array|bool {
            $this->attempts[] = $submission->id;
            return array_shift($this->results);
        }
    };
    $service->results = $results;
    Formie::$plugin->set('notifications', $service);
    try {
        $cli = deliveryCommandController();
        $cli->submissionId = implode(',', $ids);
        $cli->notificationId = $notification->id;
        expect($cli->actionSendNotification())->toBe($expectedExit);
        expect($service->attempts)->toEqualCanonicalizing($ids);
        $successes = count(array_filter($results, fn($value) => $value === true || (is_array($value) && ($value['success'] ?? false))));
        expect(substr_count($cli->out, 'Sent notification'))->toBe($successes);
        expect(substr_count($cli->err, 'Unable to send notification'))->toBe(count($results) - $successes);
    } finally { Formie::$plugin->set('notifications', $original); }
})->with([
    'success forms' => [[true, ['success' => true]], 0],
    'false failure then success' => [[false, true], 1],
    'array failure then success' => [[['success' => false], true], 1],
    'error then success' => [[['error' => 'Unavailable'], true], 1],
]);

it('reports integration delivery outcomes without announcing failed work', function (array $results, int $expectedExit): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $ids = [];
    foreach ($results as $result) { $ids[] = formie()->submission($form)->save()->id; }
    $originalRegistry = Formie::$plugin->getIntegrations();
    $originalTriggers = Formie::$plugin->getIntegrationTriggers();
    $registry = new class extends Integrations {
        public function getIntegrationByHandle(string $handle): ?IntegrationInterface { return new Mailchimp(['handle' => $handle]); }
    };
    $triggers = new class extends IntegrationTriggers {
        public array $results = [];
        public array $attempts = [];
        public function dispatchManualIntegration(Integration $integration, Submission $submission): bool|IntegrationResponse {
            $this->attempts[] = $submission->id;
            $result = array_shift($this->results);
            return is_array($result) ? new IntegrationResponse($result['success']) : $result;
        }
    };
    $triggers->results = $results;
    Formie::$plugin->set('integrations', $registry);
    Formie::$plugin->set('integrationTriggers', $triggers);
    try {
        $cli = deliveryCommandController();
        $cli->submissionId = implode(',', $ids);
        $cli->integration = 'commandFixture';
        expect($cli->actionRunIntegration())->toBe($expectedExit);
        expect($triggers->attempts)->toEqualCanonicalizing($ids);
        $successes = count(array_filter($results, fn($value) => $value === true || (is_array($value) && ($value['success'] ?? false))));
        expect(substr_count($cli->out, 'Triggered integration'))->toBe($successes);
        expect(substr_count($cli->err, 'Unable to trigger integration'))->toBe(count($results) - $successes);
    } finally {
        Formie::$plugin->set('integrations', $originalRegistry);
        Formie::$plugin->set('integrationTriggers', $originalTriggers);
    }
})->with([
    'boolean success' => [[true], 0],
    'response success' => [[['success' => true]], 0],
    'false then success' => [[false, true], 1],
    'failed response then success' => [[['success' => false], true], 1],
    'unknown response then success' => [[['success' => null], true], 1],
]);
