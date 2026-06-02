<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use Craft;
use craft\web\View;
use function htmlspecialchars;
use verbb\formie\Formie;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\controllers\server\SubmissionsController as ServerSubmissionsController;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\RichText;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\models\SubmissionResponse;
use verbb\formie\services\SubmissionWorkflow;

function renderFormieTemplate(string $template, array $variables): string
{
    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        return $view->renderTemplate($template, $variables);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }
}

it('does not evaluate Twig-style submission content when rerendering after a page-reload validation failure', function (): void {
    $form = formie()
        ->form(['title' => 'Validation Failure Rerender Security'])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'custom',
            'defaultValue' => 'safe-default',
        ])
        ->singleLineTextField('fullName', ['required' => true])
        ->submitAction('message', ['method' => 'page-reload'])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('trackingToken', MaliciousPayloads::twigMathProbe());
    $submission->setFieldValueFromRequest('fullName', '');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $form->setCurrentSubmission($response->submission);
    $field = $form->getFieldByHandle('trackingToken');
    $inputOptions = $field?->getInputTemplateVariables(
        $form,
        $response->submission?->getFieldValue('trackingToken')
    );
    $value = (string)($inputOptions['value'] ?? '');

    expect($response->success)->toBeFalse()
        ->and($response->submission?->getErrors())->not->toBeEmpty()
        ->and($value)->toBe(MaliciousPayloads::twigMathProbe())
        ->and($value)->not->toBe('49');
})->group('security');

dataset('qualysTextareaPayloads', [
    'attribute-breakout probe' => [
        'payload' => '1"\'><qssanKis1NL>',
    ],
    'script tag probe' => [
        'payload' => '<script src=http://localhost/j ',
    ],
    'multiline script probe' => [
        'payload' => "<\n\rscript a=4>qss88rTb4rT=7<\n\r/script>",
    ],
    'real textarea breakout probe' => [
        'payload' => '</textarea><script>alert(1)</script>',
    ],
]);

it('escapes textarea content when rerendering plain text after a page-reload validation failure', function (string $payload): void {
    $form = formie()
        ->form(['title' => 'Textarea Validation Failure Escaping'])
        ->multiLineTextField('message')
        ->singleLineTextField('fullName', ['required' => true])
        ->submitAction('message', ['method' => 'page-reload'])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('message', $payload);
    $submission->setFieldValueFromRequest('fullName', '');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $form->setCurrentSubmission($response->submission);
    $field = $form->getFieldByHandle('message');
    $value = $response->submission?->getFieldValue('message');
    $rendered = (string)$field?->renderInput($form, $value);
    $escapedPayload = htmlspecialchars($payload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    expect($response->success)->toBeFalse()
        ->and($response->submission?->getErrors())->not->toBeEmpty()
        ->and($value)->toBe($payload)
        ->and($rendered)->toContain('<textarea')
        ->and($rendered)->toContain($escapedPayload)
        ->and($rendered)->not->toContain($payload)
        ->and($rendered)->not->toContain('</textarea><script>')
        ->and($rendered)->not->toContain('<script src=http://localhost/j ');
})->with('qualysTextareaPayloads')->group('security');

it('escapes single-line input values in attribute contexts after a page-reload validation failure', function (): void {
    $payload = MaliciousPayloads::attributeBreakoutProbe();
    $form = formie()
        ->form(['title' => 'Single Line Validation Failure Escaping'])
        ->singleLineTextField('fullName')
        ->singleLineTextField('email', ['required' => true])
        ->submitAction('message', ['method' => 'page-reload'])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', $payload);
    $submission->setFieldValueFromRequest('email', '');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $form->setCurrentSubmission($response->submission);
    $field = $form->getFieldByHandle('fullName');
    $rendered = (string)$field?->renderInput($form, $response->submission?->getFieldValue('fullName'));

    expect($response->success)->toBeFalse()
        ->and($response->submission?->getErrors())->not->toBeEmpty()
        ->and($rendered)->toContain('value="&quot; autofocus onfocus=&quot;alert(&#039;xss&#039;)&quot; data-breakout=&quot;1"')
        ->and($rendered)->not->toContain('value="" autofocus onfocus="alert')
        ->and($rendered)->not->toContain('data-breakout="1"');
})->group('security');

it('preserves hostile values safely across multipage transitions when earlier pages are rendered again', function (): void {
    $payload = MaliciousPayloads::attributeBreakoutProbe();
    $form = formie()
        ->form(['title' => 'Multipage Value Preservation Security'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOneField')
        ->onPage(2)->singleLineTextField('pageTwoField')
        ->create();

    $pages = $form->getPages();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('pageOneField', $payload);

    $process = new SubmissionWorkflow();
    $forward = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'pageId' => (int)$pages[0]->id,
    ]));

    expect($forward->success)->toBeTrue()
        ->and($forward->nextPage?->id)->toBe($pages[1]->id);

    $submission = $forward->submission;
    $back = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_BACK,
        'pageId' => (int)$pages[1]->id,
    ]));

    expect($back->success)->toBeTrue()
        ->and($back->nextPage?->id)->toBe($pages[0]->id);

    $form->setCurrentSubmission($back->submission);
    $form->setCurrentPage($pages[0]);

    $rendered = (string)Formie::$plugin->getRendering()->renderPage($form, $pages[0]);

    expect($rendered)
        ->toContain('value="&quot; autofocus onfocus=&quot;alert(&#039;xss&#039;)&quot; data-breakout=&quot;1"')
        ->and($rendered)->not->toContain('value="" autofocus onfocus="alert')
        ->and($rendered)->not->toContain('data-breakout="1"');
})->group('security');

it('sanitizes page-reload form error flash content while preserving safe html', function (): void {
    $form = formie()
        ->form(['title' => 'Flash Error Rendering Security'])
        ->create();

    $html = renderFormieTemplate('formie/_special/form-template/form/message-error', [
        'form' => $form,
        'flashError' => StringHelper::sanitizeMessageHtml('<p>Please retry. <a href="https://example.com/help">Help</a></p><script>alert("xss")</script>'),
    ]);

    expect($html)
        ->toContain('<a href="https://example.com/help">Help</a>')
        ->toContain('<p>Please retry.')
        ->and($html)->not->toContain('<script>alert("xss")</script>');
})->group('security');

it('sanitizes page-reload success flash content while preserving safe html', function (): void {
    $form = formie()
        ->form(['title' => 'Flash Success Rendering Security'])
        ->create();

    $html = renderFormieTemplate('formie/_special/form-template/form/message-success', [
        'form' => $form,
        'flashNotice' => StringHelper::sanitizeMessageHtml('<p>Saved. <a href="https://example.com/next">Continue</a></p><img src=x onerror=alert("xss")>'),
    ]);

    expect($html)
        ->toContain('<a href="https://example.com/next">Continue</a>')
        ->toContain('<img')
        ->and($html)->not->toContain('onerror=');
})->group('security');

it('sanitizes submit json form errors while preserving safe html links', function (): void {
    $form = formie()
        ->form(['title' => 'JSON Error Message Security'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();
    $form->settings->errorMessage = RichText::from('<p>Please retry. <a href="https://example.com/help">Help</a></p><script>alert("xss")</script>');
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', '');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));
    $response->submission->addError('form', '<p>Please retry. <a href="https://example.com/help">Help</a></p><script>alert("xss")</script>');

    $controller = new SubmissionsController('formie-submissions-security', Craft::$app);
    $method = new ReflectionMethod(SubmissionsController::class, '_createSubmitJsonResponsePayload');
    $method->setAccessible(true);
    $payload = $method->invoke($controller, $response, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, []);
    $formErrors = $payload['errors']['form'] ?? [];

    expect($formErrors)->not->toBeEmpty()
        ->and($formErrors[0] ?? '')->toContain('<a href="https://example.com/help">Help</a>')
        ->and($formErrors[0] ?? '')->not->toContain('<script');
})->group('security');

it('sanitizes submit json field errors before returning legacy ajax payloads', function (): void {
    $form = formie()
        ->form(['title' => 'JSON Field Error Message Security'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', '');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));
    $response->submission->addError('fullName', '<script>alert("xss")</script><p>Retry this field.</p>');

    $controller = new SubmissionsController('formie-submissions-security', Craft::$app);
    $method = new ReflectionMethod(SubmissionsController::class, '_createSubmitJsonResponsePayload');
    $method->setAccessible(true);
    $payload = $method->invoke($controller, $response, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, []);
    $fieldErrors = $payload['errors']['fullName'] ?? [];
    $fieldErrorText = implode(' ', $fieldErrors);

    expect($fieldErrors)->not->toBeEmpty()
        ->and($fieldErrorText)->toContain('Retry this field.')
        ->and($fieldErrorText)->not->toContain('<script');
})->group('security');

it('sanitizes submit json field errors before returning runtime html ajax payloads', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime JSON Field Error Message Security'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', '');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));
    $response->submission->addError('fullName', '<script>alert("xss")</script><p>Retry this field.</p>');

    $controller = new ServerSubmissionsController('formie-server-submissions-security', Craft::$app);
    $method = new ReflectionMethod(ServerSubmissionsController::class, '_createSubmitJsonResponsePayload');
    $method->setAccessible(true);
    $payload = $method->invoke($controller, $response, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, []);
    $fieldErrors = $payload['errors']['fullName'] ?? [];
    $fieldErrorText = implode(' ', $fieldErrors);

    expect($fieldErrors)->not->toBeEmpty()
        ->and($fieldErrorText)->toContain('Retry this field.')
        ->and($fieldErrorText)->not->toContain('<script');
})->group('security');

it('sanitizes submit json success messages while preserving safe html links', function (): void {
    $form = formie()
        ->form(['title' => 'JSON Success Message Security'])
        ->singleLineTextField('fullName', ['required' => true])
        ->submitAction('message', [
            'method' => 'ajax',
            'message' => '<p>Thanks. <a href="https://example.com/next">Next steps</a></p><script>alert("xss")</script>',
        ])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', 'Security Tester');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    expect($response)->toBeInstanceOf(SubmissionResponse::class)
        ->and($response->success)->toBeTrue();

    $controller = new SubmissionsController('formie-submissions-security', Craft::$app);
    $method = new ReflectionMethod(SubmissionsController::class, '_createSubmitJsonResponsePayload');
    $method->setAccessible(true);
    $payload = $method->invoke($controller, $response, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, []);
    $message = (string)($payload['submitActionMessage'] ?? '');

    expect($message)
        ->toContain('<a href="https://example.com/next">Next steps</a>')
        ->and($message)->not->toContain('<script');
})->group('security');
