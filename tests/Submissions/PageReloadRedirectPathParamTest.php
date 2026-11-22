<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\Formie;
use verbb\formie\helpers\SetPageReturnUrlHelper;

it('leaves the path param out of the page reload redirect on query-routed requests', function(): void {
    $form = formie()->form()->settings(['submitMethod' => 'page-reload', 'submitAction' => 'message', 'disableCaptchas' => true])
        ->singleLineTextField('name')
        ->create();

    // Apache's standard Craft rewrite sends /events/my-event?mode=x to index.php?p=events/my-event&mode=x.
    // Craft reads the path from $_GET when the request starts, so set it before the simulated app is built.
    $queryParams = ['p' => 'events/my-event', 'mode' => 'x', 'submissionId' => '123'];
    $originalGet = $_GET;
    $_GET = $queryParams;

    try {
        $location = WebRequestTestHelper::withWebRequestContext(function($request) use ($form): string {
            Craft::$app->getUser()->setIdentity(null);
            $general = Craft::$app->getConfig()->getGeneral();
            expect($general->pathParam)->toBe('p')
                ->and($request->getPathInfo())->toBe('events/my-event');
            $omitScriptName = $general->omitScriptNameInUrls;
            $general->omitScriptNameInUrls = true;

            $document = new DOMDocument();
            @$document->loadHTML('<?xml encoding="utf-8" ?>' . Formie::$plugin->getRendering()->renderForm($form));
            $body = [];

            foreach ((new DOMXPath($document))->query('//input[@type="hidden"]') as $input) {
                $body[$input->getAttribute('name')] = $input->getAttribute('value');
            }

            $request->setBodyParams(array_merge($body, [
                'formStartedAt' => (string)((int)(microtime(true) * 1000) - 60000),
                'formieHoneypot' => '',
                'fields' => ['name' => 'Guest'],
            ]));

            try {
                $response = (new SubmissionsController('submissions', Formie::$plugin))->runAction('submit');
            } finally {
                $general->omitScriptNameInUrls = $omitScriptName;
            }

            expect($response->statusCode)->toBe(302);

            return (string)$response->getHeaders()->get('Location');
        }, ['method' => 'POST', 'headers' => ['Accept' => 'text/html'], 'requestUri' => '/index.php?p=events/my-event&mode=x&submissionId=123', 'queryParams' => $queryParams]);
    } finally {
        $_GET = $originalGet;
    }

    parse_str((string)parse_url($location, PHP_URL_QUERY), $query);

    expect(parse_url($location, PHP_URL_PATH))->toBe('/events/my-event')
        ->and($query)->toBe(['mode' => 'x']);
});

it('leaves the path param out of page tab return URLs on query-routed requests', function(): void {
    $queryParams = ['p' => 'events/my-event', 'mode' => 'x'];
    $originalGet = $_GET;
    $_GET = $queryParams;

    try {
        $relative = WebRequestTestHelper::withWebRequestContext(function($request): string {
            expect($request->getPathInfo())->toBe('events/my-event');
            $token = SetPageReturnUrlHelper::createTokenFromCurrentRequest($request);

            return (string)Craft::$app->getSecurity()->validateData((string)$token);
        }, ['requestUri' => '/index.php?p=events/my-event&mode=x', 'queryParams' => $queryParams]);
    } finally {
        $_GET = $originalGet;
    }

    expect($relative)->toBe('/events/my-event?mode=x');
});
