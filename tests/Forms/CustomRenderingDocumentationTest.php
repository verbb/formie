<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\Formie;
use verbb\formie\models\HtmlSubmissionRequest;
use verbb\formie\models\Settings;

it('renders and validates the documented custom form', function (bool $completeExample, bool $guardsEnabled, bool $manualFields, string $value, bool $omitGuards): void {
    WebRequestTestHelper::withWebRequestContext(function () use ($completeExample, $guardsEnabled, $manualFields, $value, $omitGuards): void {
        $settings = Formie::$plugin->getSettings();
        $original = $settings->getAttributes();
        $settings->setAttributes([
            'enableHoneypot' => $guardsEnabled,
            'honeypotFieldName' => 'customHoneypot',
            'enableMinimumSubmitTime' => $guardsEnabled,
            'enableFormSubmitExpiration' => false,
            'minimumSubmitTime' => 3,
            'enableGlobalSubmissionThrottling' => false,
            'enableIpSubmissionThrottling' => false,
            'spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS,
        ], false);

        try {
            $form = formie()->form()
                ->singleLineTextField('message', ['required' => true])
                ->settings(['disableCaptchas' => true])
                ->create();
            $docs = file_get_contents(dirname(__DIR__, 2) . '/docs/theming/custom-rendering.md');
            preg_match_all('/```twig\n(.*?)```/s', $docs, $matches);
            $examples = array_values(array_filter($matches[1], static fn(string $example): bool => str_contains($example, "forms.handle('contactUs')") && str_contains($example, 'data-submit-action')));
            expect($examples)->toHaveCount(2);
            $template = str_replace("{% set form = craft.formie.forms.handle('contactUs').one() %}", '', $examples[$completeExample ? 1 : 0]);
            if (!$completeExample && $guardsEnabled) {
                $guardExample = array_values(array_filter($matches[1], static fn(string $example): bool => str_starts_with(trim($example), '{% set guardSettings')))[0];
                $template = str_replace('</form>', $guardExample . '</form>', $template);
            }

            if ($manualFields) {
                $fieldExample = array_values(array_filter($matches[1], static fn(string $example): bool => str_starts_with(trim($example), "{% set field = form.getFieldByHandle('message')")))[0];
                $template = preg_replace('/{% for field in form.getFields\(\) %}.*?{% endfor %}/s', $fieldExample, $template);
            }

            $view = Craft::$app->getView();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
            $html = $view->renderString($template, ['form' => $form]);
            $document = new DOMDocument();
            @$document->loadHTML($html);
            $xpath = new DOMXPath($document);

            expect($xpath->query('//form[@data-formie-form]')->length)->toBe(1)
                ->and($xpath->query('//*[@data-formie-field-handle="message" and @data-formie-validation]')->length)->toBe(1)
                ->and($xpath->query('//input[@name="fields[message]"]')->length)->toBe(1)
                ->and($xpath->query('//input[@name="customHoneypot"]')->length)->toBe($guardsEnabled ? 1 : 0)
                ->and($xpath->query('//input[@name="formStartedAt"]')->length)->toBe($guardsEnabled ? 1 : 0)
                ->and($xpath->query('//input[@name="expectedVersion" or @name="pageId"]')->length)->toBe(0);

            $rules = json_decode($xpath->query('//*[@data-formie-field-handle="message"]')->item(0)->getAttribute('data-formie-validation'), true, flags: JSON_THROW_ON_ERROR);
            expect(array_column($rules, 'type'))->toContain('required');

            $body = [];

            foreach ($xpath->query('//form//input[@name]') as $input) {
                $body[$input->getAttribute('name')] = $input->getAttribute('value');
            }

            unset($body['fields[message]']);
            $body['fields'] = ['message' => $value];
            // The browser fills the timer when mounting the form.
            if ($guardsEnabled) {
                $body['formStartedAt'] = (string)((int)(microtime(true) * 1000) - 10000);
            }

            if ($omitGuards) {
                unset($body['customHoneypot'], $body['formStartedAt']);
            }

            Craft::$app->getRequest()->setBodyParams($body);
            // Exercise the public request service directly: the test helper adds missing guard inputs.
            $response = Formie::$plugin->getSubmissionRequests()->executeHtml(new HtmlSubmissionRequest([
                'handle' => $body['handle'],
                'renderId' => $body['renderId'],
                'requestToken' => $body['requestToken'],
                'expectedVersion' => isset($body['expectedVersion']) ? (int)$body['expectedVersion'] : null,
                'pageId' => isset($body['pageId']) ? (int)$body['pageId'] : null,
                'submitAction' => $body['submitAction'],
            ]), SubmissionAuthorityType::VISITOR)->response;

            if ($omitGuards && $guardsEnabled) {
                expect($response->success)->toBeTrue()
                    ->and($response->outcome->type)->toBe(SubmissionOutcomeType::REJECTED)
                    ->and($response->submission->id)->toBeNull();
            } elseif ($value === '') {
                expect($response->success)->toBeFalse()
                    ->and($response->submission->getErrors())->toHaveKey('message')
                    ->and($response->submission->id)->toBeNull();
            } else {
                expect($response->success)->toBeTrue()
                    ->and($response->submission->isSpam)->toBeFalse()
                    ->and($response->submission->id)->not->toBeNull();
            }
        } finally {
            $settings->setAttributes($original, false);
        }
    }, ['method' => 'POST']);
})->with([false, true])->with([false, true])->with([false, true])->with([
    'required value missing' => ['', false],
    'valid value' => ['A message', false],
    'missing spam inputs' => ['', true],
]);
