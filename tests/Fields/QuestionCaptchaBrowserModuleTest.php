<?php

declare(strict_types=1);

use verbb\formie\integrations\captchas\Question;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;

// Client-rendered forms have no form template to show the security question, so the Question captcha sends it to its
// browser module, with the index the answer is checked against.
it('sends the security question to client-rendered forms only', function(): void {
    $form = formie()->form()->singleLineTextField('visitorName', ['label' => 'Visitor name'])->create();

    try {
        $captcha = new Question(['questions' => [['question' => 'What colour is a clear sky?', 'answers' => 'blue']]]);
        $module = $captcha->getBrowserModule(new BrowserModuleContext(['form' => $form]));

        expect($module->moduleId)->toBe('formie:question-captcha')
            ->and($module->surfaces)->toBe([BrowserModule::SURFACE_CLIENT_RENDERED])
            ->and($module->config)->toMatchArray(['question' => 'What colour is a clear sky?', 'index' => 0])
            ->and((new Question(['questions' => []]))->getBrowserModule(new BrowserModuleContext(['form' => $form])))->toBeNull();
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
});
