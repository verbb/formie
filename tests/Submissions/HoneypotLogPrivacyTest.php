<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\models\HtmlSubmissionRequest;

/**
 * Submits a filled honeypot through the real HTML submission path and returns the response with the messages it logged.
 */
function submitFilledHoneypot(bool $logSubmissionValues): array
{
    $form = formie()->form()->create();
    $settings = Formie::$plugin->getSettings();
    $original = $settings->getAttributes();
    $logger = Craft::getLogger();
    // An automatic flush would empty the buffer this reads from.
    $flushInterval = $logger->flushInterval;

    try {
        $settings->enableHoneypot = true;
        $settings->honeypotFieldName = 'formieHoneypot';
        $settings->enableMinimumSubmitTime = false;
        $settings->enableFormSubmitExpiration = false;
        $settings->logSubmissionValues = $logSubmissionValues;
        $logger->flushInterval = PHP_INT_MAX;
        $messageOffset = count($logger->messages);

        $response = WebRequestTestHelper::withWebRequestContext(function() use ($form) {
            return runHtmlSubmission(new HtmlSubmissionRequest([
                'handle' => $form->handle,
                'siteId' => $form->siteId,
                'submitAction' => 'submit',
            ]))->response;
        }, ['method' => 'POST', 'bodyParams' => ['formieHoneypot' => 'patient@example.com']]);

        $messages = array_filter(array_column(array_slice($logger->messages, $messageOffset), 0), 'is_string');
    } finally {
        $logger->flushInterval = $flushInterval;
        $settings->setAttributes($original, false);
    }

    return [$response, implode("\n", $messages)];
}

it('leaves the honeypot value out of the rejection log when submitted values are opted out', function() {
    [$response, $log] = submitFilledHoneypot(false);

    expect($response->outcome->type)->toBe(SubmissionOutcomeType::REJECTED)
        ->and($response->submission->id)->toBeNull()
        ->and($log)->toContain('Honeypot input has a value.')
        ->and($log)->not->toContain('patient@example.com');
});

it('keeps the honeypot value in the rejection log by default', function() {
    [$response, $log] = submitFilledHoneypot(true);

    expect($response->outcome->type)->toBe(SubmissionOutcomeType::REJECTED)
        ->and($log)->toContain('Honeypot input has value: patient@example.com.');
});
