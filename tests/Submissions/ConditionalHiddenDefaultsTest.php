<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Dropdown;
use verbb\formie\helpers\Table;
use verbb\formie\models\HtmlSubmissionRequest;

// Fields hidden by conditions are disabled in the browser and not posted. Their
// default options must not be saved in place of the cleared (empty) answer.

function conditionalHiddenDefaultsRule(string $source = 'trigger'): array
{
    return ['enableConditions' => true, 'conditions' => ['showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['field' => $source, 'condition' => '=', 'value' => 'show']]]];
}

function conditionalHiddenDefaultsForm(): Form
{
    $when = conditionalHiddenDefaultsRule();

    return formie()->form()->settings(['disableCaptchas' => true])
        ->radioField('trigger', ['options' => [['label' => 'Show', 'value' => 'show'], ['label' => 'Hide', 'value' => 'hide', 'isDefault' => true]]])
        ->dropdownField('pick', ['options' => [['label' => 'Gamma', 'value' => 'gamma'], ['label' => 'Delta', 'value' => 'delta', 'isDefault' => true]]] + $when)
        ->checkboxesField('extras', ['options' => [['label' => 'One', 'value' => 'one', 'isDefault' => true], ['label' => 'Two', 'value' => 'two']]] + $when)
        ->radioField('choice', ['options' => [['label' => 'Yes', 'value' => 'yes', 'isDefault' => true], ['label' => 'No', 'value' => 'no']]] + $when)
        ->tableField('items', ['columns' => ['col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline']], 'defaults' => [['col1' => 'Default']]] + $when)
        ->groupField('contact', ['rows' => [['fields' => [
            ['type' => Dropdown::class, 'handle' => 'size', 'label' => 'Size', 'options' => [['label' => 'Small', 'value' => 'small'], ['label' => 'Large', 'value' => 'large', 'isDefault' => true]]] + conditionalHiddenDefaultsRule('trigger'),
        ]]]])
        ->create();
}

function conditionalHiddenDefaultsSubmit(Form $form, array $fields): Submission
{
    $result = WebRequestTestHelper::withWebRequestContext(fn() => runHtmlSubmission(new HtmlSubmissionRequest([
        'handle' => $form->handle,
        'submitAction' => 'submit',
        'fieldParamNamespace' => 'fields',
    ])), ['method' => 'POST', 'bodyParams' => ['fields' => $fields]]);

    expect($result->response->success)->toBeTrue(json_encode($result->response->errors ?? null));

    return Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->orderBy(['elements.id' => SORT_DESC])->one();
}

it('does not save default options for fields hidden by conditions', function (): void {
    $form = conditionalHiddenDefaultsForm();

    // The browser posts only the visible fields; hidden Checkboxes still post their empty marker.
    $saved = conditionalHiddenDefaultsSubmit($form, ['trigger' => 'hide', 'extras' => '', 'contact' => []]);

    expect($saved->getFieldValue('trigger')->value)->toBe('hide')
        ->and($saved->getFieldValue('pick')?->value)->toBeNull()
        ->and($saved->getFieldValue('extras')?->values() ?? [])->toBe([])
        ->and($saved->getFieldValue('choice')?->value)->toBeNull()
        ->and($saved->getFieldValue('items') ?? [])->toBe([])
        ->and($saved->getFieldValue('contact.size')?->value)->toBeNull();

    // Check the stored content too, not only the reloaded values.
    $content = Json::decode((string)(new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $saved->id])->scalar());
    $stored = fn(string $handle): mixed => $content[$form->getFieldByHandle($handle)->uid] ?? null;

    expect($stored('pick'))->toBeEmpty()
        ->and($stored('extras'))->toBeEmpty()
        ->and($stored('choice'))->toBeEmpty()
        ->and($stored('items'))->toBeEmpty()
        ->and(array_filter((array)$stored('contact')))->toBeEmpty();
});

it('keeps posted and default answers for fields shown by conditions', function (): void {
    $form = conditionalHiddenDefaultsForm();

    $saved = conditionalHiddenDefaultsSubmit($form, [
        'trigger' => 'show',
        'pick' => 'delta',
        'extras' => ['one'],
        'choice' => 'yes',
        'items' => [['col1' => 'Default']],
        'contact' => ['size' => 'large'],
    ]);

    expect($saved->getFieldValue('pick')->value)->toBe('delta')
        ->and($saved->getFieldValue('extras')->values())->toBe(['one'])
        ->and($saved->getFieldValue('choice')->value)->toBe('yes')
        ->and($saved->getFieldValue('items')[0]['col1'] ?? null)->toBe('Default')
        ->and($saved->getFieldValue('contact.size')->value)->toBe('large');
});
