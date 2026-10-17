<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\controllers\server\SubmissionsController as ServerSubmissionsController;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\Formie;
use verbb\formie\models\HtmlSubmissionRequest;
use verbb\formie\models\Settings;
use verbb\formie\services\CompletionResolver;
use yii\base\Event;

it('returns completion feedback for simulated spam success without raising completion events', function (string $screening, string $behavior): void {
    WebRequestTestHelper::withWebRequestContext(function () use ($screening, $behavior): void {
        $settings = Formie::$plugin->getSettings();
        $original = $settings->getAttributes();
        $settings->setAttributes([
            'spamKeywords' => 'blocked-keyword',
            'spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS,
            'saveSpam' => $screening === 'saved',
            'enableHoneypot' => true,
            'honeypotFieldName' => 'formieHoneypot',
            'enableGlobalSubmissionThrottling' => false,
            'enableIpSubmissionThrottling' => false,
            'enableFormSubmitExpiration' => false,
            'enableSuspiciousTextDetection' => false,
        ], false);
        $completed = 0;
        $observe = function () use (&$completed): void { $completed++; };
        Event::on(CompletionResolver::class, CompletionResolver::EVENT_RESOLVE_COMPLETION, $observe);

        try {
            $form = formie()->form()->singleLineTextField('message')->settings([
                'disableCaptchas' => true,
                'completionBehavior' => $behavior,
                'redirectUrl' => '/thank-you',
                'successMessage' => 'Thank you for your enquiry.',
            ])->create();
            Craft::$app->getRequest()->setBodyParams([
                'fields' => ['message' => 'contains blocked-keyword content'],
                'formieHoneypot' => $screening === 'honeypot' ? 'automated-fill' : '',
            ]);
            $response = runHtmlSubmission(new HtmlSubmissionRequest(['handle' => $form->handle]))->response;

            expect($response->success)->toBeTrue()
                ->and($response->outcome->type)->toBe(SubmissionOutcomeType::REJECTED)
                ->and($response->outcome->data['completion']['behavior'] ?? null)->toBe($behavior)
                ->and($completed)->toBe(0);

            if ($screening === 'saved') {
                expect($response->submission->id)->not->toBeNull()
                    ->and($response->submission->isSpam)->toBeTrue();
            } else {
                expect($response->submission->id)->toBeNull();
            }

            foreach ([SubmissionsController::class, ServerSubmissionsController::class] as $controllerClass) {
                $controller = new $controllerClass('spam-completion', Craft::$app);
                $method = new ReflectionMethod($controllerClass, '_createSubmitJsonResponsePayload');
                $payload = $method->invoke($controller, $response, 'submit');
                expect($payload['completion']['behavior'])->toBe($behavior);

                if ($behavior === 'message') {
                    expect($payload['successMessage'])->toContain('Thank you for your enquiry.');
                } else {
                    expect($payload['redirect']['url'])->toBe('/thank-you');
                }
            }
        } finally {
            Event::off(CompletionResolver::class, CompletionResolver::EVENT_RESOLVE_COMPLETION, $observe);
            $settings->setAttributes($original, false);
        }
    }, ['method' => 'POST']);
})->with(['saved', 'discarded', 'honeypot'])->with(['message', 'redirect']);
