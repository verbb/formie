<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use craft\elements\User;
use verbb\formie\controllers\EmailController;
use verbb\formie\Formie;
use verbb\formie\models\Stencil;
use verbb\formie\services\Stencils;
use yii\mail\BaseMailer;
use yii\web\ForbiddenHttpException;

it('uses stencil fields for email previews and test sends with builder IDs', function (string $action, string $idMode): void {
    $source = formie()->form()->singleLineTextField('stencilMessage', ['label' => 'Stencil message'])->create();
    $unrelated = formie()->form()->singleLineTextField('unrelatedMessage', ['label' => 'Unrelated message'])->create();
    $stencil = new Stencil([
        'name' => 'Email preview stencil',
        'handle' => 'emailPreviewStencil' . bin2hex(random_bytes(6)),
        'scope' => Stencils::SCOPE_SITE,
    ]);
    $stencil->data->populateFormData($source);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();

    $config = Formie::$plugin->getStencils()->getStencilById($stencil->id)->getFormBuilderConfig();
    $formId = match ($idMode) {
        'saved' => $config['id'],
        'collision' => $unrelated->id,
        default => null,
    };

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($action): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $controller = new EmailController('email', Formie::$plugin);

        if ($action === 'preview') {
            $result = $controller->actionPreview()->data;
            expect($result)->not->toHaveKey('error');
            $body = $result['body'];
            $subject = $result['subject'];
        } else {
            $captured = null;
            $capture = function ($event) use (&$captured): void {
                $captured = $event->message->getSymfonyEmail();
                // Exercise rendering and recipient overrides without delivering mail.
                $event->isValid = false;
            };
            $mailer = Craft::$app->getMailer();
            $mailer->on(BaseMailer::EVENT_BEFORE_SEND, $capture);

            try {
                $controller->actionSendTestEmail();
            } finally {
                $mailer->off(BaseMailer::EVENT_BEFORE_SEND, $capture);
            }

            expect($captured)->not->toBeNull()
                ->and($captured->getTo()[0]->getAddress())->toBe('test@example.test');
            $body = $captured->getHtmlBody();
            $subject = $captured->getSubject();
        }

        expect($subject)->toBe('Stencil preview: Email preview stencil')
            ->and($body)->toContain('Email preview stencil', 'Stencil message')->not->toContain('Unrelated message');
    }, [
        'method' => 'POST',
        'bodyParams' => [
            'formId' => $formId,
            'handle' => $config['handle'],
            'isStencil' => $config['isStencil'],
            'to' => 'test@example.test',
            'notification' => [
                'name' => 'Stencil notification',
                'handle' => 'stencilNotification',
                'subject' => 'Stencil preview: {formName}',
                'from' => 'sender@example.test',
                'to' => 'recipient@example.test',
                'content' => '<p>{form:name}</p>{allFields}',
            ],
        ],
    ]);
})->with(['preview', 'send-test-email'])->with(['saved', 'collision', 'absent']);

it('requires stencil permission even when a stencil request has a form ID', function (string $action): void {
    $form = formie()->form()->create();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($action): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(null);
        $controller = new EmailController('email', Formie::$plugin);

        expect(fn() => $action === 'preview' ? $controller->actionPreview() : $controller->actionSendTestEmail())
            ->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'bodyParams' => ['formId' => $form->id, 'handle' => 'missingStencil', 'isStencil' => true],
    ]);
})->with(['preview', 'send-test-email']);
