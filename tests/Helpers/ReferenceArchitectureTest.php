<?php

use verbb\formie\helpers\References;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceDiagnostic;
use verbb\formie\references\ReferenceException;
use verbb\formie\references\ReferenceOutputContext;
use verbb\formie\references\ReferenceParser;

it('resolves trusted system mail aliases without expanding submitted values', function() {
    $form = formie()->form()->singleLineTextField('value')->create();
    $submission = formie()->submission($form)->with(['value' => '$FORMIE_REFERENCE_SYSTEM_EMAIL'])->save();
    $projectConfig = Craft::$app->getProjectConfig();
    $workingConfigProperty = new ReflectionProperty($projectConfig, '_currentWorkingConfig');
    $originalWorkingConfig = $workingConfigProperty->getValue($projectConfig);
    $config = $projectConfig->get();
    $config['email']['fromEmail'] = '$FORMIE_REFERENCE_SYSTEM_EMAIL';
    $config['email']['replyToEmail'] = '$FORMIE_REFERENCE_SYSTEM_REPLY_TO';
    $config['email']['fromName'] = '$FORMIE_REFERENCE_SYSTEM_NAME';
    $config['email']['siteOverrides'] = [];
    $_SERVER['FORMIE_REFERENCE_SYSTEM_EMAIL'] = 'notifications@example.test';
    $_SERVER['FORMIE_REFERENCE_SYSTEM_REPLY_TO'] = 'replies@example.test';
    $_SERVER['FORMIE_REFERENCE_SYSTEM_NAME'] = 'Formie Test';

    try {
        $workingConfigProperty->setValue($projectConfig, new craft\models\ProjectConfigData($config, $projectConfig));
        $context = ReferenceContext::forSubmission($submission);
        $fieldToken = References::field((string)$form->getFieldByHandle('value')->reference);

        expect(References::resolveValue('{system:email}', $context)->requireValue())->toBe('notifications@example.test')
            ->and(References::resolveValue('{system:replyTo}', $context)->requireValue())->toBe('replies@example.test')
            ->and(References::resolveValue('{system:name}', $context)->requireValue())->toBe('Formie Test')
            ->and(References::resolveValue($fieldToken, $context)->requireValue())->toBe('$FORMIE_REFERENCE_SYSTEM_EMAIL');
    } finally {
        $workingConfigProperty->setValue($projectConfig, $originalWorkingConfig);
        unset(
            $_SERVER['FORMIE_REFERENCE_SYSTEM_EMAIL'],
            $_SERVER['FORMIE_REFERENCE_SYSTEM_REPLY_TO'],
            $_SERVER['FORMIE_REFERENCE_SYSTEM_NAME'],
        );
    }
});

it('round trips reserved characters without executing expressions', function() {
    $token = References::token('field', 'abc', 'firstName', ['scope' => 'all', 'transform' => 'replace', 'search' => 'a;b|c', 'replace' => '+{}%'], 'A|B}');
    $expression = ReferenceParser::parse($token);
    expect($expression->isValid)->toBeTrue()->and(ReferenceParser::serialize($expression))->toBe($token)
        ->and($expression->default)->toBe('A|B}')->and($expression->transformerParams['replace'])->toBe('+{}%');
    expect(ReferenceParser::parse('{formName|fallback}')->default)->toBe('fallback');
    expect(ReferenceParser::parse('{field:a;v=99}')->diagnostic)->toBe('unsupportedVersion');
    expect(ReferenceParser::parse('{{ craft.app }}')->isValid)->toBeFalse();
});

it('parses stable flat and object-template tokens without Twig', function() {
    expect(ReferenceParser::parse('{formName}')->target)->toBe('form')
        ->and(ReferenceParser::parse('{field.group.name}')->identifier)->toBe('group.name');
});

it('resolves native values and encodes the selected output exactly once', function() {
    $form = formie()->form()->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->with(['message' => '<script>x&"</script>'])->save();
    $field = $form->getFieldByHandle('message');
    $token = References::field($field->reference);
    $context = ReferenceContext::forSubmission($submission);
    expect(References::resolveValue($token, $context)->requireValue())->toBe('<script>x&"</script>');
    expect(References::interpolateText($token, $context, ReferenceOutputContext::Html))->toBe('&lt;script&gt;x&amp;&quot;&lt;/script&gt;');
    expect(References::interpolateText($token, $context, ReferenceOutputContext::UrlComponent))->toBe(rawurlencode('<script>x&"</script>'));
});

it('diagnoses missing fields unknown extensions and forbidden environment access', function() {
    $context = new ReferenceContext();
    expect(References::resolveValue('{field:deleted|fallback}', $context)->diagnostic)->toBe(ReferenceDiagnostic::MissingField)
        ->and(References::resolveValue('{custom:missing/source}', $context)->diagnostic)->toBe(ReferenceDiagnostic::UnknownSource)
        ->and(References::resolveValue('{env:SECURITY_KEY}', $context)->diagnostic)->toBe(ReferenceDiagnostic::ForbiddenSource);
    expect(fn() => References::interpolateText("a\r\nBcc: b", $context, ReferenceOutputContext::EmailHeader))->toThrow(ReferenceException::class);
});

it('preserves rich native values while text uses the owning reference projection', function() {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true])->create();
    $submission = formie()->submission($form)->save();
    $submission->setFieldValue('person', new \verbb\formie\fields\values\NameFieldValue(['firstName' => 'Ada', 'lastName' => 'Lovelace']));
    $token = References::field($form->getFieldByHandle('person')->reference);
    $context = ReferenceContext::forSubmission($submission);
    expect(References::resolveValue($token, $context)->requireValue())->toBeInstanceOf(\verbb\formie\fields\values\NameFieldValue::class)
        ->and(References::interpolateText($token, $context))->toBe('Ada Lovelace')
        ->and(References::interpolateText(References::withDefault($token, 'fallback'), $context))->toBe('Ada Lovelace')
        ->and(References::resolveValue('{field:person}', $context)->requireValue()->firstName)->toBe('Ada')
        ->and(References::resolveValue('{field:person;transform=upper}', $context)->requireValue())->toBe('ADA LOVELACE');
});

it('uses the reference projection event exactly once before contextual encoding', function() {
    $form = formie()->form()->singleLineTextField('referenceProjection')->create();
    $submission = formie()->submission($form)->with(['referenceProjection' => 'stored'])->save();
    $field = $submission->getForm()->getFieldByHandle('referenceProjection');
    $token = References::field($field->reference);
    $context = ReferenceContext::forSubmission($submission);
    $events = [];
    $handler = function ($event) use (&$events) {
        if ($event->field->handle === 'referenceProjection') {
            $events[] = $event->name;
            $event->value = '<reference & value>';
        }
    };
    $referenceEvent = \verbb\formie\base\Field::EVENT_MODIFY_VALUE_FOR_REFERENCE;
    $stringEvent = \verbb\formie\base\Field::EVENT_MODIFY_VALUE_AS_STRING;
    foreach ([$referenceEvent, $stringEvent] as $event) {
        \yii\base\Event::on(\verbb\formie\base\Field::class, $event, $handler);
    }
    try {
        expect(References::interpolateText($token, $context, ReferenceOutputContext::Html))->toBe('&lt;reference &amp; value&gt;')
            ->and($events)->toBe([$referenceEvent])
            ->and(References::resolveValue($token, $context)->requireValue())->toBe('stored');
        $events = [];
        expect(References::interpolateText($token, $context, ReferenceOutputContext::EmailHeader))->toBe('<reference & value>')
            ->and($events)->toBe([$referenceEvent]);
        $events = [];
        expect(References::interpolateText('{field:referenceProjection;transform=upper}', $context, ReferenceOutputContext::Html))->toBe('&lt;REFERENCE &amp; VALUE&gt;')
            ->and($events)->toBe([$referenceEvent]);
    } finally {
        foreach ([$referenceEvent, $stringEvent] as $event) {
            \yii\base\Event::off(\verbb\formie\base\Field::class, $event, $handler);
        }
    }
});

it('resolves persisted repeater children only in explicit current-row context', function() {
    $form = formie()->form()->repeaterField('people', ['rows' => [['fields' => [['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'name', 'label' => 'Name']]]]])->create();
    $parent = $form->getFieldByHandle('people');
    $child = $parent->getFields()[0];
    $submission = formie()->submission($form)->with(['people' => [['name' => 'One'], ['name' => 'Two']]])->save();
    $token = References::field($child->reference);
    expect(References::resolveValue($token, ReferenceContext::forSubmission($submission))->diagnostic)->toBe(ReferenceDiagnostic::MissingRowScope);
    expect(References::resolveValue($token, ReferenceContext::forSubmission($submission, rows: [$parent->reference => 1]))->requireValue())->toBe('Two');
    expect(References::resolveValue(References::field($child->reference, metadata: ['scope' => 'all']), ReferenceContext::forSubmission($submission))->requireValue())->toBe(['One', 'Two']);
});

it('keeps unknown brace tokens literal and records known unresolved references', function() {
    $context = new ReferenceContext();

    expect(References::interpolateText('Hello {other:token}', $context))->toBe('Hello {other:token}')
        ->and(References::interpolateText('Hello {field:deleted}', $context))->toBe('Hello ')
        ->and($context->diagnostics->all())->toBe([
            ['token' => '{field:deleted}', 'diagnostic' => ReferenceDiagnostic::MissingField->value],
        ]);
    expect(fn() => References::interpolateText('{other:token}', $context, ReferenceOutputContext::EmailHeader))->toThrow(ReferenceException::class);
});

it('keeps field identity stable across renaming and rejects cross-form references', function() {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $other = formie()->form()->singleLineTextField('answer')->create();
    $token = References::field($form->getFieldByHandle('answer')->reference);
    $submission = formie()->submission($other)->with(['answer' => 'Wrong'])->save();
    expect(References::resolveValue($token, ReferenceContext::forSubmission($submission))->diagnostic)->toBe(ReferenceDiagnostic::MissingField);
});

it('does not resolve submitted dollar values as environment secrets and rejects header injection', function() {
    $form = formie()->form()->singleLineTextField('value')->create();
    $submission = formie()->submission($form)->with(['value' => '$SECURITY_KEY'])->save();
    $context = ReferenceContext::forSubmission($submission);
    $token = References::field($form->getFieldByHandle('value')->reference);
    expect(References::interpolateText($token, $context))->toBe('$SECURITY_KEY');
    $submission->setFieldValue('value', "a@example.test\r\nBcc: injected@example.test");
    expect(fn() => References::interpolateText($token, $context, ReferenceOutputContext::EmailHeader))->toThrow(ReferenceException::class);
});

it('only exposes allowlisted environment names and never their values in metadata', function() {
    $settings = \verbb\formie\Formie::$plugin->getSettings();
    $original = $settings->referenceEnvironmentAllowlist;
    putenv('FORMIE_REFERENCE_SECRET=private-secret');
    try {
        $settings->referenceEnvironmentAllowlist = [];
        expect(json_encode(\verbb\formie\helpers\Variables::getCategoryConfig()))->not->toContain('FORMIE_REFERENCE_SECRET');
        $settings->referenceEnvironmentAllowlist = ['FORMIE_REFERENCE_SECRET'];
        $catalogue = json_encode(\verbb\formie\helpers\Variables::getCategoryConfig());
        expect($catalogue)->toContain('FORMIE_REFERENCE_SECRET')->not->toContain('private-secret');
        $form = formie()->form()->create();
        $submission = formie()->submission($form)->save();
        expect(References::resolveValue('{env:FORMIE_REFERENCE_SECRET}', ReferenceContext::forSubmission($submission))->requireValue())->toBe('private-secret');
    } finally {
        $settings->referenceEnvironmentAllowlist = $original;
        putenv('FORMIE_REFERENCE_SECRET');
    }
});

it('keeps slot semantics independent of braces and migrates legacy mapping data idempotently', function() {
    $context = new ReferenceContext();
    $literal = new \verbb\formie\references\ReferenceSlot(\verbb\formie\references\ReferenceSlotKind::Literal, '{env:SECRET}');
    expect($literal->resolve($context))->toBe('{env:SECRET}');
    $settings = ['fieldMapping' => ['token' => '{field:abc}', 'literal' => 'Hello', 'provider' => '{providerOption:a%2Bb}'], 'apiKey' => '$PRIVATE'];
    $migrated = \verbb\formie\references\ReferenceMigration::integrationSlots($settings);
    expect($migrated['fieldMapping']['token'])->toBe(['kind' => 'reference', 'value' => '{field:abc}'])
        ->and($migrated['fieldMapping']['provider'])->toBe(['kind' => 'literal', 'value' => 'a+b'])
        ->and($migrated['apiKey'])->toBe('$PRIVATE')
        ->and(\verbb\formie\references\ReferenceMigration::integrationSlots($migrated))->toBe($migrated);
});

it('migrates beta parent-child tokens to stable nested field identities', function() {
    $rows = [['fields' => [[
        'type' => \verbb\formie\fields\SingleLineText::class,
        'handle' => 'innerText',
        'label' => 'Inner Text',
    ]]]];
    $form = formie()->form()
        ->groupField('groupContent', ['rows' => $rows])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();
    $group = $form->getFieldByHandle('groupContent');
    $repeater = $form->getFieldByHandle('lineItems');
    $groupChild = $group->getFieldByHandle('innerText');
    $repeaterChild = $repeater->getFieldByHandle('innerText');
    $stored = implode(' / ', [
        References::field((string)$group->reference, 'innerText'),
        References::field((string)$repeater->reference, 'innerText', ['scope' => 'all', 'transform' => 'join']),
    ]);
    $migrated = \verbb\formie\references\ReferenceMigration::canonicalFieldTokens($form, $stored);

    expect($migrated)->toBe(implode(' / ', [
        References::field((string)$groupChild->reference),
        References::field((string)$repeaterChild->reference, metadata: ['scope' => 'all', 'transform' => 'join']),
    ]))
        ->and(\verbb\formie\references\ReferenceMigration::canonicalFieldTokens($form, $migrated))->toBe($migrated);
});

it('uses one instance reference across conditions integrations headers redirects and the picker', function() {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->with(['answer' => 'A&B <C>'])->save();
    $field = $form->getFieldByHandle('answer');
    $token = References::field($field->reference);
    $context = ReferenceContext::forSubmission($submission);
    $integration = new \verbb\formie\integrations\crm\HubSpot(['name' => 'Parity', 'handle' => 'parity']);
    $destination = new \verbb\formie\models\IntegrationField(['handle' => 'answer']);
    expect(References::resolveValue($token, $context)->requireValue())->toBe('A&B <C>')
        ->and($integration->getMappedFieldValue(['kind' => 'reference', 'value' => $token], $submission, $destination))->toBe('A&B <C>')
        ->and($integration->getFieldMappingValues($submission, ['answer' => ['kind' => 'reference', 'value' => $token]], [$destination]))->toBe(['answer' => 'A&B <C>'])
        ->and(References::interpolateText($token, $context, ReferenceOutputContext::EmailHeader))->toBe('A&B <C>')
        ->and(References::parseUrl('https://example.test/?answer=' . $token, $submission))->toBe('https://example.test/?answer=A%26B%20%3CC%3E');
    $field->handle = 'renamed';
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $reloaded = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect(References::resolveValue($token, ReferenceContext::forSubmission($reloaded))->requireValue())->toBe('A&B <C>');
});

it('resolves fixed children directly and rejects invalid row and modifier syntax', function() {
    $form = formie()->form()->groupField('contact', ['rows' => [['fields' => [['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'name', 'label' => 'Name']]]]])->create();
    $child = $form->getFieldByHandle('contact')->getFields()[0];
    $submission = formie()->submission($form)->with(['contact' => ['name' => 'Ada']])->save();
    $context = ReferenceContext::forSubmission($submission);
    expect(References::resolveValue(References::field($child->reference), $context)->requireValue())->toBe('Ada')
        ->and(References::resolveValue('{field:contact;unsupported=x}', $context)->diagnostic)->toBe(ReferenceDiagnostic::InvalidExpression)
        ->and(ReferenceParser::parse('{field:contact;search=%FF}')->isValid)->toBeFalse();
});

it('preserves zero and false and does not reparse object-template reference values', function() {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->with(['answer' => '{{ 7 * 7 }} {env:SECURITY_KEY}'])->save();
    $token = References::field($form->getFieldByHandle('answer')->reference);
    expect(\verbb\formie\Formie::$plugin->getTemplates()->renderSandboxedObjectTemplate($token . '/{{ "folder"|upper }}', $submission, autoescape: false))->toBe('{{ 7 * 7 }} {env:SECURITY_KEY}/FOLDER');
    foreach ([0, false] as $value) {
        $context = new ReferenceContext(report: ['value' => $value]);
        expect(References::resolveValue('{report:value|fallback}', $context)->requireValue())->toBe($value);
    }
});
