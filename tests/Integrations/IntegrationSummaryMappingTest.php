<?php

use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\References;
use verbb\formie\integrations\helpdesk\Freshdesk;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceDiagnostic;
use verbb\formie\references\ReferenceUsage;

function summaryMappingDestination(string $format = 'html'): IntegrationField
{
    // Any free-text destination accepts summaries; only declared HTML destinations receive markup.
    return new IntegrationField(['handle' => 'description', 'name' => 'Description', 'acceptsHtml' => $format === 'html']);
}

it('maps persisted submission summaries in the destination format with existing exclusions', function(string $token, string $format) {
    $form = formie()->form()
        ->singleLineTextField('answer', ['label' => 'Public answer'])
        ->singleLineTextField('emptyAnswer', ['label' => 'Empty answer'])
        ->singleLineTextField('privateAnswer', ['label' => 'Private answer', 'includeInEmailFieldSummaries' => false])
        ->hiddenField('hiddenAnswer', ['label' => 'Hidden answer'])
        ->singleLineTextField('conditional', ['label' => 'Conditional answer', 'enableConditions' => true, 'conditions' => [
            'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['field' => 'answer', 'condition' => '=', 'value' => 'show']],
        ]])->create();
    $saved = formie()->submission($form)->with(['answer' => 'A & B <script>alert(1)</script>', 'privateAnswer' => 'Secret answer', 'hiddenAnswer' => 'Hidden value', 'conditional' => 'Conditional secret'])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $integration = new Freshdesk();
    $result = $integration->getFieldMappingValues($submission, ['description' => ['kind' => 'reference', 'value' => $token]], [summaryMappingDestination($format)])['description'];

    expect($result)->toContain($format === 'html' ? 'Public answer' : 'PUBLIC ANSWER', $format === 'html' ? 'A &amp; B' : 'A & B')
        ->not->toContain('Private answer', 'Secret answer', 'Conditional answer', 'Conditional secret');
    if ($format === 'html') {
        expect($result)->toContain('<p>', '&lt;script&gt;')->not->toContain('<script>');
    } else {
        expect($result)->not->toContain('<p>', '<table', '&lt;', '&amp;');
    }
    expect(str_contains(strtolower($result), 'empty answer'))->toBe($token !== '{allContentFields}')
        ->and(str_contains(strtolower($result), 'hidden answer'))->toBe($token !== '{allVisibleFields}');
})->with(['{allFields}', '{allContentFields}', '{allVisibleFields}'])->with(['html', 'text']);

it('formats complete containers while retaining excluded child mappings and stored values', function(string $container, string $format) {
    $form = formie()->form()->{$container}('details', ['label' => 'Details', 'rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'answer', 'label' => 'Answer'],
        ['type' => SingleLineText::class, 'handle' => 'secret', 'label' => 'Secret', 'includeInEmailFieldSummaries' => false],
    ]]]])->create();
    $row = ['answer' => 'First response', 'secret' => 'Private response'];
    $saved = formie()->submission($form)->with(['details' => $container === 'repeaterField' ? [$row, array_replace($row, ['answer' => 'Second response'])] : $row])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $field = $submission->getForm()->getFieldByHandle('details');
    $token = References::field($field->reference, 'summary');
    $integration = new Freshdesk();
    $result = $integration->resolveMappedFieldValue(['kind' => 'reference', 'value' => $token], $submission, summaryMappingDestination($format));
    expect(strtolower($result))->toContain('details', 'answer', 'first response')->not->toContain('secret', 'private response');
    if ($container === 'repeaterField') {
        expect(strtolower($result))->toContain('row #1', 'row #2', 'second response');
    }
    expect(str_contains($result, '<table'))->toBe($format === 'html');

    $child = $field->getFieldByHandle('secret');
    $metadata = $container === 'repeaterField' ? ['scope' => 'all'] : [];
    $existing = $integration->resolveMappedFieldValue(References::field($child->reference, metadata: $metadata), $submission, new IntegrationField(['type' => 'string']));
    expect($existing)->toBe($container === 'repeaterField' ? ['Private response', 'Private response'] : 'Private response');

    $field->includeInEmailFieldSummaries = false;
    expect(trim($integration->resolveMappedFieldValue($token, $submission, summaryMappingDestination($format))))->toBe('');
})->with(['groupField', 'repeaterField'])->with(['html', 'text']);

it('maps plain-text summaries to destinations without a supplied schema', function() {
    $form = formie()->form()->singleLineTextField('answer', ['label' => 'Answer'])->create();
    $submission = formie()->submission($form)->with(['answer' => 'A & B'])->save();
    $result = (new Freshdesk())->getFieldMappingValues($submission, ['notes' => ['kind' => 'reference', 'value' => '{allFields}']])['notes'];

    expect($result)->toContain('ANSWER', 'A & B')->not->toContain('<p>', '&amp;');
});

it('rejects summary references for non-text and option destinations', function(string $type, array $options) {
    $form = formie()->form()->singleLineTextField('answer')->groupField('details', ['rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'child', 'label' => 'Child'],
    ]]]])->create();
    $submission = formie()->submission($form)->with(['answer' => 'Hello', 'details' => ['child' => 'World']])->save();
    $destination = new IntegrationField(['handle' => 'destination', 'type' => $type, 'acceptsHtml' => true, 'options' => $options]);
    $integration = new Freshdesk();
    foreach (['{allFields}', References::field($form->getFieldByHandle('details')->reference, 'summary')] as $token) {
        foreach (['reference', 'text'] as $kind) {
            expect(fn() => $integration->resolveMappedFieldValue(['kind' => $kind, 'value' => $token], $submission, $destination))->toThrow(IntegrationException::class, 'forbiddenSource');
        }
    }
})->with([
    ['number', []], ['boolean', []], ['date', []], ['array', []], ['string', [['value' => 'choice']]],
]);

it('keeps literal and scalar mappings unchanged and exposes summaries to mapping events', function(string $format) {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->with(['answer' => 'A & <B>'])->save();
    $integration = new Freshdesk();
    $destination = summaryMappingDestination($format);
    $token = References::field($form->getFieldByHandle('answer')->reference);
    expect($integration->resolveMappedFieldValue(['kind' => 'literal', 'value' => '{allFields}'], $submission, $destination))->toBe('{allFields}')
        ->and($integration->resolveMappedFieldValue($token, $submission, $destination))->toBe('A & <B>')
        ->and($integration->resolveMappedFieldValue(['kind' => 'text', 'value' => 'Prefix ' . $token], $submission, $destination))->toBe('Prefix A & <B>');
    $seen = null;
    $integration->on(Freshdesk::EVENT_MODIFY_FIELD_MAPPING_VALUE, function($event) use (&$seen) {
        $seen = $event->value;
        $event->value .= ' suffix';
    });
    $value = $integration->resolveMappedFieldValue(['kind' => 'text', 'value' => 'Prefix {allFields}'], $submission, $destination);
    expect($seen)->toStartWith('Prefix ')->and($value)->toBe($seen . ' suffix');
})->with(['html', 'text']);

it('treats only the Freshdesk ticket description as HTML including previously cached metadata', function() {
    $integration = new Freshdesk();
    $fields = [new IntegrationField(['handle' => 'subject']), new IntegrationField(['handle' => 'description'])];
    $integration->cache = ['config' => (new IntegrationConfig(['ticket' => $fields, 'contact' => $fields], $integration->getIntegrationConfigKey(), time()))->toStorage()];
    expect($integration->getConfigValue('ticket')[1]->acceptsHtml)->toBeTrue()
        ->and($integration->getConfigValue('ticket')[0]->acceptsHtml)->toBeFalse()
        ->and($integration->getConfigValue('contact')[1]->acceptsHtml)->toBeFalse();
    $schema = $integration->createFieldMappingField(['dataKey' => 'ticket']);
    expect($schema['integrationFields'][0]['acceptsSummaries'])->toBeTrue()
        ->and($schema['integrationFields'][1]['acceptsSummaries'])->toBeTrue();
    $roundTrip = IntegrationConfig::decode(IntegrationConfig::encode(summaryMappingDestination('html')));
    expect($roundTrip->acceptsHtml)->toBeTrue();
});

it('does not enable block summaries in generic reference contexts', function() {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->with(['answer' => 'Hello'])->save();
    foreach ([ReferenceUsage::Integration, ReferenceUsage::Text] as $usage) {
        expect(References::resolveValue('{allFields}', ReferenceContext::forSubmission($submission, usage: $usage))->diagnostic)->toBe(ReferenceDiagnostic::ForbiddenSource);
    }
});
