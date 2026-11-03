<?php

use Tests\Support\WebRequestTestHelper;
use craft\elements\User;
use craft\web\View;
use verbb\formie\controllers\SupportController;
use verbb\formie\Formie;
use verbb\formie\models\Support;

it('retains support request values when a required field is missing', function(string $missing): void {
    $body = [
        'fromEmail' => 'support-test@example.test',
        'formId' => '123',
        'message' => "Please keep this message.\nIt contains <markup> & details.",
        'includeLogs' => '0',
    ];
    $body[$missing] = '';

    WebRequestTestHelper::withWebRequestContext(function($request) use ($body, $missing): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $controller = new SupportController('support', Formie::$plugin);
        expect($controller->actionSendSupportRequest())->toBeNull();
        $support = Craft::$app->getUrlManager()->getRouteParams()['support'] ?? null;

        expect($support)->toBeInstanceOf(Support::class)
            ->and($support->hasErrors($missing))->toBeTrue()
            ->and($support->fromEmail)->toBe($body['fromEmail'])
            ->and($support->formId)->toBe($body['formId'] === '' ? null : 123)
            ->and($support->message)->toBe($body['message'])
            ->and($support->includeLogs)->toBeFalse();

        Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
        $html = Craft::$app->getView()->renderPageTemplate('formie/settings/support', [
            'settings' => Formie::$plugin->getSettings(),
            'support' => $support,
        ], View::TEMPLATE_MODE_CP);
        $document = new DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($document);

        expect($xpath->query('//textarea[@name="message"]')->item(0)->textContent)->toBe($body['message'])
            ->and($xpath->query('//input[@name="fromEmail"]')->item(0)->getAttribute('value'))->toBe($body['fromEmail'])
            ->and($xpath->query('//input[@name="includeLogs" and @type="checkbox"]')->item(0)->hasAttribute('checked'))->toBeFalse();
    }, ['method' => 'POST', 'bodyParams' => $body]);
})->with(['fromEmail', 'formId', 'message']);
