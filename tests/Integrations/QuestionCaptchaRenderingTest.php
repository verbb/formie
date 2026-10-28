<?php

declare(strict_types=1);

use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\SpamProtectionSettings;

it('renders question captchas with strict Twig variables on initial and failed page reloads', function (bool $failed): void {
    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        $integrations = Formie::$plugin->getIntegrations();
        $captcha = $integrations->getCaptchaByHandle('question');
        $captcha->setEnabled(true);
        $captcha->questions = [['question' => 'What is two plus three?', 'answers' => '5,five']];
        expect($integrations->saveCaptcha($captcha))->toBeTrue();

        $form = formie()->form(['title' => 'Question CAPTCHA rendering'])
            ->singleLineTextField('message')
            ->settings([
                'submitMethod' => 'page-reload',
                'disableCaptchas' => false,
                'integrations' => ['question' => ['enabled' => true]],
            ])->create();

        WebRequestTestHelper::withWebRequestContext(function () use ($failed, $form): void {
            $view = Craft::$app->getView();
            $view->setTemplateMode(View::TEMPLATE_MODE_CP);
            $view->getTwig()->enableStrictVariables();

            if ($failed) {
                $submission = new Submission();
                $submission->setForm($form);
                $submission->addError('formieCaptchaQuestion', 'Incorrect answer. Please try again.');
                $form->setCurrentSubmission($submission);
            }

            $html = (string)Formie::$plugin->getRendering()->renderForm($form, ['renderCss' => false, 'renderJs' => false]);
            $document = new DOMDocument();
            @$document->loadHTML($html);
            $xpath = new DOMXPath($document);
            $inputs = $xpath->query('//input[@name="formieCaptchaQuestion[answer]"]');
            expect($inputs->length)->toBe(1);
            $input = $inputs->item(0);
            expect($input->hasAttribute('required'))->toBeTrue()
                ->and($input->getAttribute('value'))->toBe($failed ? 'four' : '')
                ->and($xpath->query('//input[@name="formieCaptchaQuestion[index]"]')->item(0)->getAttribute('value'))->toBe('0');
            $label = $xpath->query('//label[@for="' . $input->getAttribute('id') . '"]')->item(0);
            expect($label)->not->toBeNull()
                ->and($label->textContent)->toContain('What is two plus three?');

            if ($failed) {
                expect($html)->toContain('Incorrect answer. Please try again.');
            }

            // Submit the names actually emitted in the HTML, as a browser would.
            $index = $xpath->query('//input[@name="formieCaptchaQuestion[index]"]')->item(0);
            parse_str(http_build_query([$index->getAttribute('name') => '0', $input->getAttribute('name') => 'five']), $body);
            Craft::$app->getRequest()->setBodyParams($body);
            $submission = new Submission();
            $submission->setForm($form);
            $captcha = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form)[0];
            expect($captcha->validateSubmission($submission))->toBeTrue();

            $body['formieCaptchaQuestion']['answer'] = 'four';
            Craft::$app->getRequest()->setBodyParams($body);
            expect($captcha->validateSubmission($submission))->toBeFalse();
        }, ['bodyParams' => $failed ? ['formieCaptchaQuestion' => ['index' => '0', 'answer' => 'four']] : []]);
    } finally {
        $transaction->rollBack();
        Craft::$app->getInfo()->configVersion = (new \craft\db\Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
        Craft::$app->getProjectConfig()->reset();
        SpamProtectionSettings::resetCaches();
    }
})->with([false, true]);

it('keeps optional field label decorations when rendering with strict Twig variables', function (array $decorations, string $expected): void {
    $view = Craft::$app->getView();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);
    $twig = $view->getTwig();
    $strict = $twig->isStrictVariables();
    $twig->enableStrictVariables();

    try {
        $form = formie()->form()->singleLineTextField('message', ['label' => 'Message'])->create();
        $field = $form->getFieldByHandle('message');
        $html = $form->renderTemplate('field/label', array_merge([
            'form' => $form,
            'field' => $field,
            'labelPosition' => (new \verbb\formie\variables\Formie())->getLabelPosition($field, $form),
            'position' => 'above',
        ], $decorations));

        expect($html)->toContain($expected);
        $fieldHtml = (string)Formie::$plugin->getRendering()->renderField($form, $field, $decorations);
        expect($fieldHtml)->toContain($expected);
    } finally {
        if (!$strict) {
            $twig->disableStrictVariables();
        }
    }
})->with([
    'omitted' => [[], 'Message'],
    'null' => [['fieldLabelPrefix' => null, 'fieldLabelSuffix' => null], 'Message'],
    'prefix only' => [['fieldLabelPrefix' => '<b>Before</b>'], '<b>Before</b>Message'],
    'suffix only' => [['fieldLabelSuffix' => '<b>After</b>'], 'Message&nbsp;<b>After</b>'],
    'both' => [['fieldLabelPrefix' => '<b>Before</b>', 'fieldLabelSuffix' => '<b>After</b>'], '<b>Before</b>Message&nbsp;<b>After</b>'],
]);
