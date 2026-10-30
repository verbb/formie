<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SubmissionRedirectRulesHelper;
use verbb\formie\models\Payment;
use verbb\formie\services\CompletionResolver;

it('honors editor configured destinations including reference supplied external URLs', function (string $template, string $value, ?string $expected): void {
    $form = formie()->form()->singleLineTextField('destination')->create();
    $token = '{field:' . $form->getFieldByHandle('destination')->reference . '}';
    $template = str_replace('{value}', $token, $template);
    $form->settings->setAttributes(['completionBehavior' => 'redirect', 'redirectUrl' => $template], false);
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('destination', $value);

    expect((new CompletionResolver())->resolve($form, $submission, false)->url)->toBe($expected)
        ->and(SubmissionRedirectRulesHelper::resolveRuleUrl(['redirectUrl' => $template], $form, $submission))->toBe($expected ?? '')
        ->and(Formie::$plugin->getPayments()->resolvePaymentSuccessRedirectUrl(new Payment(), $submission, $form, $template))->toBe($expected ?? '');
})->with([
    'editor external URL' => ['https://buy.stripe.com/example', '', 'https://buy.stripe.com/example'],
    'external query value' => ['https://buy.stripe.com/example?ref={value}', 'https://visitor.example', 'https://buy.stripe.com/example?ref=https%3A%2F%2Fvisitor.example'],
    'external path value' => ['https://buy.stripe.com/{value}', '//visitor.example', 'https://buy.stripe.com/%2F%2Fvisitor.example'],
    'whole external reference' => ['{value}', 'https://visitor.example/thanks', 'https://visitor.example/thanks'],
    'whole relative reference' => ['{value}', '/thanks?ok=1', '/thanks?ok=1'],
    'visitor hostname' => ['https://{value}/thanks', 'visitor.example', 'https://visitor.example/thanks'],
    'visitor subdomain' => ['https://{value}.example/thanks', 'visitor', 'https://visitor.example/thanks'],
    'visitor port' => ['https://buy.stripe.com:{value}/thanks', '8443', 'https://buy.stripe.com:8443/thanks'],
    'visitor protocol relative URL' => ['{value}', '//visitor.example', null],
    'encoded protocol relative URL' => ['{value}', '/%2fvisitor.example', null],
    'backslashes' => ['{value}', '/\\visitor.example', null],
    'encoded scheme' => ['{value}', 'https%3a%2f%2fvisitor.example', null],
    'credentials' => ['{value}', 'https://safe.example@visitor.example', null],
    'unsafe literal' => ['javascript:alert(1)', '', null],
    'header injection' => ['https://buy.stripe.com/%0d%0aLocation:evil', '', null],
    'missing field' => ['{field:missing}', '', null],
]);

it('accepts a site URL reference and expands the site URL prefix', function (): void {
    $form = formie()->form()->singleLineTextField('destination')->create();
    $submission = new Submission();
    $submission->setForm($form);
    $base = rtrim(Craft::$app->getSites()->getSiteById($submission->siteId)->getBaseUrl(), '/');
    $form->setRedirectUrl('{field:' . $form->getFieldByHandle('destination')->reference . '}');
    $submission->setFieldValue('destination', $base . '/thanks');
    expect((new CompletionResolver())->resolve($form, $submission, false)->url)->toBe($base . '/thanks');

    $form->setRedirectUrl('{site:url}thanks');
    expect((new CompletionResolver())->resolve($form, $submission, false)->url)->toBe($base . '/thanks');
});

it('allows server completion events to select an external destination without configuration', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = new Submission();
    $submission->setForm($form);
    $resolver = new CompletionResolver();
    $resolver->on(CompletionResolver::EVENT_RESOLVE_COMPLETION, function ($event): void {
        $event->redirectUrl = 'https://buy.stripe.com/server-choice';
    });

    expect($resolver->resolve($form, $submission)->url)->toBe('https://buy.stripe.com/server-choice');
});
