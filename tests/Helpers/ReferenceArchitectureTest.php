<?php

use verbb\formie\helpers\References;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceDiagnostic;
use verbb\formie\references\ReferenceException;
use verbb\formie\references\ReferenceOutputContext;
use verbb\formie\references\ReferenceParser;

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

it('preserves rich native values while text uses the owning string projection', function() {
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

it('resolves persisted repeater children only in explicit current-row context', function() {
    $form = formie()->form()->repeaterField('people', ['rows' => [['fields' => [['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'name', 'label' => 'Name']]]]])->create();
    $parent = $form->getFieldByHandle('people');
    $child = $parent->getFields()[0];
    $submission = formie()->submission($form)->with(['people' => [['name' => 'One'], ['name' => 'Two']]])->save();
    $token = References::field($child->reference);
    expect(References::resolveValue($token, ReferenceContext::forSubmission($submission))->diagnostic)->toBe(ReferenceDiagnostic::MissingRowScope);
    expect(References::resolveValue($token, ReferenceContext::forSubmission($submission, rows: [$parent->reference => 1]))->requireValue())->toBe('Two');
    expect(References::resolveValue(References::field($parent->reference, 'name', ['scope' => 'all']), ReferenceContext::forSubmission($submission))->requireValue())->toBe(['One', 'Two']);
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
