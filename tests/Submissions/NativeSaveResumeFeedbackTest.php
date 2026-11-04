<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;

function nativeSaveDocument(string $html): DOMXPath
{
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    return new DOMXPath($document);
}

it('keeps a portable resume link visible after native Save independently of completion notice position', function(string $position): void {
    $form = formie()->form()->settings(['submitMethod' => 'page-reload', 'successMessagePosition' => $position, 'disableCaptchas' => true])
        ->multiPage(2)->onPage(1)->singleLineTextField('message', ['required' => true])
        ->onPage(2)->singleLineTextField('confirmation', ['required' => true])->create();
    $form->setPageSettings(0, ['showSaveButton' => true]);
    $other = formie()->form()->singleLineTextField('unrelated')->create();

    $saved = WebRequestTestHelper::withWebRequestContext(function($request) use ($form, $other): array {
        Craft::$app->getUser()->setIdentity(null);
        $request->getHeaders()->set('Referer', \craft\helpers\UrlHelper::siteUrl('native-save?mode=normal'));
        $initial = nativeSaveDocument((string)Formie::$plugin->getRendering()->renderForm($form));
        $body = [];
        foreach ($initial->query('//input[@type="hidden"]') as $input) {
            $body[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        $request->setBodyParams(array_merge($body, ['submitAction' => 'save', 'formStartedAt' => (string)((int)(microtime(true) * 1000) - 60000), 'formieHoneypot' => '', 'fields' => ['message' => 'Saved for another browser']]));
        $response = (new SubmissionsController('submissions', Formie::$plugin))->runAction('submit');
        expect($response->statusCode)->toBe(302);
        $location = $response->getHeaders()->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        expect($query['resumeToken'] ?? null)->not->toBeEmpty();
        $submission = Submission::find()->formId($form->id)->isIncomplete(true)->status(null)->one();
        expect($submission)->not->toBeNull()
            ->and($submission->getFieldValue('message'))->toBe('Saved for another browser');

        $request->setBodyParams([]);
        $request->setQueryParams($query);
        $unrelated = (string)Formie::$plugin->getRendering()->renderForm($other);
        expect($unrelated)->not->toContain($query['resumeToken'])->not->toContain('Your progress has been saved.');
        $sameFormOtherInstance = Form::find()->id($form->id)->one();
        $sameFormOtherInstance->setDraftContext('another-render');
        expect((string)Formie::$plugin->getRendering()->renderForm($sameFormOtherInstance))->not->toContain('Your progress has been saved.');

        $resumed = Form::find()->id($form->id)->one();
        // A real redirected request starts its render sequence again. Preserve that slot in this simulated request.
        $resumed->setRenderId($body['renderId']);
        $html = (string)Formie::$plugin->getRendering()->renderForm($resumed, [], false);
        $document = nativeSaveDocument($html);
        $links = $document->query('//a[contains(@href,"resumeToken=")]');
        expect($links->length)->toBe(1)
            ->and($links->item(0)->getAttribute('href'))->toBe($location)
            ->and(substr_count($html, 'Your progress has been saved.'))->toBe(1)
            ->and($resumed->getCurrentSubmission()->id)->toBe($submission->id)
            ->and(Craft::$app->getResponse()->getHeaders()->get('Cache-Control'))->toContain('no-store')
            ->and(json_encode(Craft::$app->getView()->js))->toContain("searchParams.delete('resumeToken')");
        return ['url' => $links->item(0)->getAttribute('href'), 'id' => $submission->id];
    }, ['method' => 'POST', 'headers' => ['Accept' => 'text/html'], 'requestUri' => '/native-save?mode=normal']);

    parse_str(parse_url($saved['url'], PHP_URL_QUERY), $query);
    WebRequestTestHelper::withWebRequestContext(function() use ($form, $saved): void {
        Craft::$app->getUser()->setIdentity(null);
        $fresh = Form::find()->id($form->id)->one();
        expect($fresh->getCurrentSubmission()->id)->toBe($saved['id'])
            ->and($fresh->getCurrentSubmission()->getFieldValue('message'))->toBe('Saved for another browser');
    }, ['queryParams' => $query]);
})->with(['top-form', 'bottom-form', '']);

it('escapes the saved link and shows it even when the form is unavailable', function(): void {
    $form = formie()->form()->settings(['requireUser' => true])->singleLineTextField('message')->create();
    WebRequestTestHelper::withWebRequestContext(function() use ($form): void {
        $url = 'https://craft.example.test/form?resumeToken=valid&label=" autofocus onfocus="alert(1)';
        $form->getRenderId();
        $service = Formie::$plugin->getService();
        $service->setNotice($form->getFlashNamespace(), 'Your progress has been saved.');
        $service->setFlash($form->getFlashNamespace(), 'savedProgress', ['message' => 'Your progress has been saved.', 'resumeUrl' => $url]);
        Craft::$app->getUser()->setIdentity(null);
        expect($form->isAvailable())->toBeFalse();
        $html = (string)Formie::$plugin->getRendering()->renderForm($form, [], false);
        $document = nativeSaveDocument($html);
        $links = $document->query('//a[contains(@href,"resumeToken=")]');
        expect($links->length)->toBe(1)
            ->and($links->item(0)->getAttribute('href'))->toBe($url)
            ->and($document->query('//*[@autofocus or @onfocus]')->length)->toBe(0);
    });
});

it('keeps ordinary completion notices governed by their configured position', function(string $position, bool $visible): void {
    $form = formie()->form()->settings(['successMessagePosition' => $position])->singleLineTextField('message')->create();
    WebRequestTestHelper::withWebRequestContext(function() use ($form, $visible): void {
        $form->getRenderId();
        Formie::$plugin->getService()->setNotice($form->getFlashNamespace(), 'Ordinary completion notice');
        $html = (string)Formie::$plugin->getRendering()->renderForm($form, [], false);
        expect(str_contains($html, 'Ordinary completion notice'))->toBe($visible);
    });
})->with([['', false], ['top-form', true], ['bottom-form', true]]);
