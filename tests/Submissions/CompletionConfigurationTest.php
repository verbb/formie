<?php

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\CompletionBehavior;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\helpers\CompletionRedirectPolicy;
use verbb\formie\helpers\UrlHelper;
use verbb\formie\models\FormInstanceConfig;
use verbb\formie\models\SubmissionConfig;
use verbb\formie\services\CompletionResolver;
use verbb\formie\services\RuntimeConfiguration;
use verbb\formie\workflow\WorkflowContext;

it('resolves each completion behavior only for the completed outcome', function (string $behavior): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->setSettings(['completionBehavior' => $behavior, 'submitActionUrl' => '/thanks']);
    $submission = new Submission();
    $submission->setForm($form);
    $context = new WorkflowContext(submissionCommand(['form' => $form, 'submission' => $submission]));
    foreach ([SubmissionOutcomeType::PAGE_CHANGED, SubmissionOutcomeType::DRAFT_SAVED, SubmissionOutcomeType::PAYMENT_ACTION_REQUIRED, SubmissionOutcomeType::PAYMENT_PENDING] as $type) {
        expect($context->result($type)->data)->not->toHaveKey('completion');
    }
    expect($context->result(SubmissionOutcomeType::COMPLETED)->data['completion']['behavior'])->toBe($behavior);
})->with(['message', 'redirect', 'reload', 'reset']);

it('rejects dangerous final redirect targets', function (string $url): void {
    expect(CompletionRedirectPolicy::validate($url))->toBe('');
})->with(['javascript:alert(1)', '//evil.test/path', '/\\evil.test', "/safe\r\nLocation: https://evil.test", '/safe%0d%0aInjected', '/safe%250d%250aInjected', 'https://safe.test@evil.test', 'https://evil.test/steal', 'data:text/html,bad', 'javascript%3aalert(1)']);

it('allows project paths and explicitly approved origins', function (): void {
    $settings = Formie::$plugin->getSettings();
    $original = $settings->completionRedirectAllowedOrigins;
    $settings->completionRedirectAllowedOrigins = ['https://partner.test'];
    try {
        expect(CompletionRedirectPolicy::validate('/thanks?ok=1'))->toBe('/thanks?ok=1')
            ->and(CompletionRedirectPolicy::validate('thanks'))->toBe('thanks')
            ->and(CompletionRedirectPolicy::validate('https://partner.test/thanks'))->toBe('https://partner.test/thanks')
            ->and(CompletionRedirectPolicy::validate('https://partner.test.evil.test/thanks'))->toBe('');
    } finally { $settings->completionRedirectAllowedOrigins = $original; }
});

it('validates the template and stable PHP event overrides last', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->setRedirectUrl('/template');
    $submission = new Submission(); $submission->setForm($form);
    expect((new CompletionResolver())->resolve($form, $submission)->url)->toBe('/template');
    $handler = static function ($event) { $event->redirectUrl = "https://evil.test/%0d%0aX-Test:bad"; };
    \yii\base\Event::on(\verbb\formie\controllers\SubmissionsController::class, 'afterSubmissionRequest', $handler);
    try {
        $result = (new CompletionResolver())->resolve($form, $submission);
        expect($result->behavior)->toBe(CompletionBehavior::Message)->and($result->url)->toBeNull();
    } finally { \yii\base\Event::off(\verbb\formie\controllers\SubmissionsController::class, 'afterSubmissionRequest', $handler); }
});

it('captures allowlisted query values and preserves explicit empty target parameters', function (): void {
    $query = UrlHelper::filterRedirectQueryParams(['utm_source' => 'initial', 'utm_medium' => ['nested'], 'token' => 'secret', 'random' => 'bad']);
    expect($query)->toBe(['utm_source' => 'initial']);
    expect(UrlHelper::appendQueryParams('/thanks?utm_source=&keep=yes#done', $query))->toBe('/thanks?utm_source=&keep=yes#done');
    $settings = Formie::$plugin->getSettings(); $original = $settings->completionQueryAllowlist;
    try {
        $settings->completionQueryAllowlist = [];
        expect(UrlHelper::filterRedirectQueryParams(['utm_source' => 'no']))->toBe([]);
        $settings->completionQueryAllowlist = ['affiliate', 'csrfToken'];
        expect(UrlHelper::filterRedirectQueryParams(['affiliate' => 'a', 'csrfToken' => 'secret']))->toBe(['affiliate' => 'a']);
    } finally { $settings->completionQueryAllowlist = $original; }
});

it('isolates cloned render occurrences and snapshots by stable UID', function (): void {
    $base = formie()->form()->singleLineTextField('name')->create();
    $a = clone $base; $b = clone $base;
    $a->setFieldSettings('name', ['label' => 'A']);
    $b->setFieldSettings('name', ['label' => 'B']);
    $a->setPageSettings(0, ['submitButtonLabel' => 'A button']);
    expect($a->getFieldByHandle('name')->label)->toBe('A')
        ->and($b->getFieldByHandle('name')->label)->toBe('B')
        ->and($base->getFieldByHandle('name')->label)->not->toBe('A')
        ->and(array_keys($a->getSnapshotData('fields')))->toBe([$base->getFieldByHandle('name')->uid])
        ->and($b->getPages()[0]->getPageSettings()->submitButtonLabel)->not->toBe('A button');
});

it('rejects unknown and forbidden trusted targets and settings', function (string $target, array $settings): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    expect(fn() => $form->setFieldSettings($target, $settings))->toThrow(\Twig\Error\RuntimeError::class);
})->with([['missing', ['label' => 'bad']], ['name', ['uid' => 'bad']], ['name', ['columnPrefix' => 'bad']]]);

it('merges maps and replaces lists and explicit empty values', function (): void {
    expect(FormInstanceConfig::merge(['map' => ['a' => 1, 'b' => 2], 'list' => [1, 2]], ['map' => ['a' => null], 'list' => []]))
        ->toBe(['map' => ['a' => null, 'b' => 2], 'list' => []]);
});

it('preserves population force and explicit empty values across a durable snapshot', function (bool $force): void {
    $form = formie()->form()->singleLineTextField('name', ['defaultValue' => 'default'])->create();
    Formie::$plugin->getRendering()->populateFormValues($form, ['name' => 'server'], $force);
    $submission = new Submission(); $submission->setForm($form);
    $submission->setFieldValueFromRequest('name', '');
    (new RuntimeConfiguration())->applyValues($submission);
    expect($submission->getFieldValue('name'))->toBe($force ? 'server' : '');
    $restored = new Submission(['snapshot' => $submission->snapshot]);
    $restored->setForm(Form::find()->id($form->id)->status(null)->one());
    $restored->setFieldValueFromRequest('name', 'attacker');
    (new RuntimeConfiguration())->applyValues($restored);
    expect($restored->getFieldValue('name'))->toBe($force ? 'server' : 'attacker');
})->with([false, true]);

it('captures query prefill once and accepts the stable prePopulate hydration alias', function (): void {
    $form = formie()->form()->singleLineTextField('name', ['prePopulate' => 'visitor', 'defaultValue' => 'default'])->create();
    $runtime = new RuntimeConfiguration();
    $runtime->establish($form, ['visitor' => '{{ literal }}']);
    $runtime->establish($form, ['visitor' => 'replacement']);
    $field = $form->getFieldByHandle('name');
    expect($field->prefillQueryParam)->toBe('visitor')->and($field->getInitialValue($form))->toBe('{{ literal }}');
    Formie::$plugin->getRendering()->populateFormValues($form, ['name' => '']);
    expect($form->getFieldByHandle('name')->getInitialValue($form))->toBe('');
});

it('reapplies authoritative Hidden values without mutating reusable defaults', function (): void {
    $form = formie()->form()->hiddenField('date', ['defaultOption' => 'dateInt', 'defaultValue' => 'saved'])->create();
    $field = $form->getFieldByHandle('date');
    expect($field->valueSource)->toBe('dateInt')->and($field->defaultValue)->toBe('saved');
    $submission = new Submission(); $submission->setForm($form);
    $submission->setFieldValueFromRequest('date', 'attacker');
    (new RuntimeConfiguration())->applyValues($submission);
    expect($submission->getFieldValue('date'))->toBe(date('d/m/Y'))->and($field->defaultValue)->toBe('saved');
});

it('stores opaque scoped instance references and never arbitrary browser settings', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->setRedirectUrl('/durable');
    Formie::$plugin->getRendering()->populateFormValues($form, ['name' => 'enforced'], true);
    $token = $form->getRequestToken();
    $fresh = Form::find()->id($form->id)->status(null)->one();
    (new RuntimeConfiguration())->restoreToken($fresh, $token);
    expect($fresh->settings->redirectUrl)->toBe('/durable')
        ->and($fresh->getInstanceConfig()->forced[$fresh->getFieldByHandle('name')->uid])->toBe('enforced')
        ->and($token)->not->toContain('enforced');
    $other = formie()->form()->singleLineTextField('name')->create();
    (new RuntimeConfiguration())->restoreToken($other, $token);
    expect($other->getInstanceConfig()->forced)->toBe([]);
});

 it('uses the configured site origin for entry redirects', function () {
    $url = rtrim(Craft::getAlias(Craft::$app->getSites()->getPrimarySite()->getBaseUrl()), '/') . '/thanks';
    expect(CompletionRedirectPolicy::validate($url))->toBe($url);
 });


it('keeps ordinary renders free of instance rows', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $before = (new \craft\db\Query())->from('{{%formie_instance_configs}}')->count();
    $form->getRequestToken();
    expect((new \craft\db\Query())->from('{{%formie_instance_configs}}')->count())->toBe($before);
});

it('preserves instance settings when refreshing a client session', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->setRedirectUrl('/refresh-preserved');
    $runtime = new RuntimeConfiguration();
    $runtime->establish($form, ['utm_source' => 'initial']);
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($form, $runtime): void {
    $session = Formie::$plugin->getClientSessionService()->issueInitialSession($form);
    $refreshed = Formie::$plugin->getClientSessionService()->refreshSession(new \verbb\formie\client\models\SessionRefreshRequest(['handle' => $form->handle, 'session' => $session->toArrayRecursive()]));
    $fresh = Form::find()->id($form->id)->status(null)->one();
    $runtime->restoreToken($fresh, $refreshed->tokens['request']);
    expect($fresh->settings->redirectUrl)->toBe('/refresh-preserved')->and($fresh->getInstanceConfig()->query)->toBe(['utm_source' => 'initial']);
    });
});

it('maps beta snapshot handles and Formie 3 field aliases without accepting arbitrary properties', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $decoded = SubmissionConfig::decode(['form' => ['submitAction' => 'url', 'submitActionUrl' => '/legacy', 'requireUser' => false], 'fields' => ['name' => ['prePopulate' => 'visitor', 'columnPrefix' => 'bad']]], $form);
    expect($decoded->form['completionBehavior'])->toBe('redirect')->and($decoded->form)->not->toHaveKey('requireUser')
        ->and($decoded->fields[$form->getFieldByHandle('name')->uid])->toBe(['prefillQueryParam' => 'visitor']);
    expect(\verbb\formie\helpers\RuntimeConfigurationMigration::migrate(['type' => \verbb\formie\fields\Hidden::class, 'settings' => ['defaultOption' => 'dateInt', 'prePopulate' => 'date']]))
        ->toBe(['type' => \verbb\formie\fields\Hidden::class, 'settings' => ['prefillQueryParam' => 'date', 'valueSource' => 'dateInt']]);
});

it('enforces nested values and UID settings after reloading a durable submission', function (): void {
    $form = formie()->form()->groupField('group', ['rows' => [['fields' => [['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'name', 'label' => 'Name']]]]])->create();
    $form->setFieldSettings('group.name', ['required' => true]);
    Formie::$plugin->getRendering()->populateFormValues($form, ['group.name' => 'server'], true);
    $submission = new Submission(); $submission->setForm($form);
    $submission->title = 'Nested forced contract';
    $submission->setFieldValueFromRequest('group', ['name' => 'attacker']);
    (new RuntimeConfiguration())->applyValues($submission);
    $saved = Craft::$app->getElements()->saveElement($submission);
    expect($submission->getErrors())->toBe([]);
    expect($saved)->toBeTrue();
    $loaded = Submission::find()->id($submission->id)->status(null)->one();
    expect($loaded->getFieldValue('group.name'))->toBe('server')
        ->and((new RuntimeConfiguration())->findField($loaded->getForm(), 'group.name')->required)->toBeTrue();
});

it('retains completion and forced values through persisted draft and queued reload', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->setRedirectUrl('/durable?utm_medium=explicit');
    (new RuntimeConfiguration())->establish($form, ['utm_source' => 'journey', 'utm_medium' => 'captured']);
    Formie::$plugin->getRendering()->populateFormValues($form, ['name' => 'server'], true);
    $draft = new Submission(['isIncomplete' => true]); $draft->setForm($form);
    $draft->setFieldValueFromRequest('name', 'attacker');
    (new RuntimeConfiguration())->applyValues($draft);
    expect(Craft::$app->getElements()->saveElement($draft, false))->toBeTrue();
    $base = Form::find()->id($form->id)->status(null)->one();
    $base->settings->submitActionUrl = '/changed-base';
    Craft::$app->getElements()->saveElement($base, false);
    $loaded = Submission::find()->id($draft->id)->isIncomplete(true)->status(null)->one();
    $loaded->setFieldValueFromRequest('name', 'replacement');
    (new RuntimeConfiguration())->applyValues($loaded);
    expect($loaded->getFieldValue('name'))->toBe('server');
    $outcome = (new CompletionResolver())->resolve($loaded->getForm(), $loaded);
    expect($outcome->url)->toBe('/durable?utm_source=journey&utm_medium=explicit');
});

it('rejects unknown integrations from the aggregate trusted settings API', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    expect(fn() => $form->setSettings(['integrations' => ['missingProvider' => ['secretKey' => 'bad']]]))->toThrow(\Twig\Error\RuntimeError::class);
});


it('keeps captured query text literal through final URL validation', function (): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->setRedirectUrl('/thanks');
    (new RuntimeConfiguration())->establish($form, ['utm_source' => '{{ literal }}']);
    $submission = new Submission(); $submission->setForm($form);
    expect((new CompletionResolver())->resolve($form, $submission)->url)->toBe('/thanks?utm_source=%7B%7B%20literal%20%7D%7D');
});

it('rejects controls returned by an exact reference before sanitizing the URL', function (): void {
    $form = formie()->form()->singleLineTextField('target')->create();
    $form->setRedirectUrl('{field:' . $form->getFieldByHandle('target')->reference . '}');
    $submission = new Submission(); $submission->setForm($form);
    $submission->setFieldValue('target', "/thanks\r\nLocation: https://evil.test");
    expect((new CompletionResolver())->resolve($form, $submission)->behavior)->toBe(CompletionBehavior::Message);
});


it('routes submission field overrides through the durable validated API', function (): void {
    $form = formie()->form()->groupField('group', ['rows' => [['fields' => [['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'name', 'label' => 'Name']]]]])->create();
    $submission = new Submission(); $submission->setForm($form);
    $submission->setFieldSettings('group.name', ['required' => true]);
    $uid = (new RuntimeConfiguration())->findField($form, 'group.name')->uid;
    expect($submission->snapshot['fields'][$uid]['required'])->toBeTrue();
    $restored = new Submission(['snapshot' => $submission->snapshot]);
    $restored->setForm(Form::find()->id($form->id)->status(null)->one());
    expect((new RuntimeConfiguration())->findField($restored->getForm(), 'group.name')->required)->toBeTrue();
    expect(fn() => $submission->setFieldSettings('missing', ['label' => 'bad']))->toThrow(\Twig\Error\RuntimeError::class);
});

it('freezes legacy element query population to portable IDs', function (): void {
    $form = formie()->form()->entriesField('entries')->create();
    $query = \craft\elements\Entry::find()->id([]);
    Formie::$plugin->getRendering()->populateFormValues($form, ['entries' => $query], true);
    $field = $form->getFieldByHandle('entries');
    expect($form->getInstanceConfig()->forced[$field->uid])->toBe([]);
    $field->populateValue($query, null);
    expect($form->getInstanceConfig()->initial[$field->uid])->toBe([]);
});

it('preserves trusted Agree descriptionHtml authoring without object snapshots', function (): void {
    $form = formie()->form()->agreeField('agree')->create();
    $form->setFieldSettings('agree', ['descriptionHtml' => '<p>Agree to terms</p>']);
    expect($form->getInstanceConfig()->fields[$form->getFieldByHandle('agree')->uid]['descriptionHtml'])->toBe('<p>Agree to terms</p>');
    \verbb\formie\content\FieldStorageCodec::assertSafe($form->getInstanceConfig()->toArray());
});


it('applies enforced control values before native conditional clearing', function (): void {
    $form = formie()->form()->singleLineTextField('control')->singleLineTextField('detail', [
        'enableConditions' => true,
        'conditions' => ['showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [[
            'field' => 'control', 'condition' => \verbb\formie\conditions\ConditionOperator::EQ, 'value' => 'show',
        ]]],
    ])->create();
    Formie::$plugin->getRendering()->populateFormValues($form, ['control' => 'show'], true);
    $submission = new Submission(['isIncomplete' => true]); $submission->setForm($form);
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($submission): void {
        $submission->setFieldValuesFromRequest('fields');
        expect($submission->getFieldValue('control'))->toBe('show')->and($submission->getFieldValue('detail'))->toBe('retained');
    }, ['method' => 'POST', 'bodyParams' => ['fields' => ['control' => 'hide', 'detail' => 'retained']]]);
});
