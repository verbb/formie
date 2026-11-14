<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;
use verbb\formie\references\ReferenceOutputContext;

it('never writes a stored password hash into a notification', function (): void {
    $form = formie()->form()->passwordField('secret')->singleLineTextField('company')->create();
    $submission = formie()->submission($form)->with(['secret' => 'correct horse', 'company' => 'S Group'])->save();
    $reloaded = Submission::find()->id($submission->id)->status(null)->one();
    $token = References::field((string)$form->getFieldByHandle('secret')->reference);

    expect(References::parseContent($token, $reloaded, ['outputContext' => ReferenceOutputContext::Html]))->toBe('');

    $result = Formie::$plugin->getEmails()->renderEmail(new Notification([
        'name' => 'Password',
        'handle' => 'passwordField',
        'to' => 'staff@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Password [' . $token . ']',
        'content' => 'Password=[' . $token . ']',
    ]), $reloaded);

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getSubject())->toBe('Password []')
        ->and($result['email']->getSymfonyEmail()->getTextBody())->toContain('Password=[]')
        ->and($result['email']->getSymfonyEmail()->getHtmlBody())->not->toContain('$2y$');
});
