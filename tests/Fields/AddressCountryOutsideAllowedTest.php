<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\exporters\SubmissionExport;
use verbb\formie\fields\Address;
use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\references\ReferenceOutputContext;

/**
 * An Address country stays readable in exports, summaries, text and reports after Allowed Countries no longer includes it.
 */
function addressOutsideAllowedRows(array $country = []): array
{
    $rows = (new Address())->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$child) {
            if ($child['handle'] === 'country') {
                $child = array_merge($child, ['enabled' => true], $country);
            }
        }
        unset($child);
    }
    unset($row);

    return $rows;
}

/**
 * Saves a submission, then narrows Allowed Countries with a normal form save, as an admin would after go-live.
 */
function addressOutsideAllowedOutputs(array $country, string $stored, ?array $allowed): array
{
    $form = formie()->form()->addressField('address', ['label' => 'Address', 'rows' => addressOutsideAllowedRows($country)])->create();
    $submission = formie()->submission($form)->with(['address' => ['address1' => '1 Main St', 'country' => $stored]])->save();

    if ($allowed !== null) {
        $form = Form::find()->id($form->id)->one();
        $form->getFieldByHandle('address')->allowedCountryCodes = $allowed;
        expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
    }

    $query = Submission::find()->id($submission->id)->status(null);
    $submission = (clone $query)->one();
    $address = Form::find()->id($form->id)->one()->getFieldByHandle('address');
    $value = $submission->getFieldValue('address');

    return [
        'allowed' => $address->allowedCountryCodes,
        'export' => (new SubmissionExport())->export(clone $query)[0]['Address: Country'] ?? '<missing>',
        'string' => $address->getValueAsString($value, $submission),
        'summary' => (string)$address->getValueForSummary($value, $submission),
        'report' => $address->getValueForReport($value, $submission),
        'reportValues' => $address->getValueForReport($value, $submission, false),
        'variable' => (string)References::parseContent(References::field($address->reference), $submission, ['outputContext' => ReferenceOutputContext::Html]),
    ];
}

it('names a country that is no longer in the Allowed Countries list', function (array $country, string $stored, string $label): void {
    $outputs = addressOutsideAllowedOutputs($country, $stored, ['NZ']);

    expect($outputs['allowed'])->toBe(['NZ'])
        ->and($outputs['export'])->toBe($label)
        ->and($outputs['string'])->toBe('1 Main St, ' . $label)
        ->and($outputs['summary'])->toContain('<strong>Country</strong> ' . $label . '<br>')
        ->and($outputs['report'])->toBe('1 Main St, ' . $label)
        ->and($outputs['reportValues'])->toBe('1 Main St, ' . $stored)
        ->and($outputs['variable'])->toContain($label);
})->with([
    'ISO values' => [[], 'AU', 'Australia'],
    'full-name values' => [['optionValue' => 'full'], 'Australia', 'Australia'],
    'ISO labels' => [['optionLabel' => 'short'], 'AU', 'AU'],
]);

it('keeps the country label for a country still in the list', function (array $country, string $stored, ?array $allowed, string $label): void {
    $outputs = addressOutsideAllowedOutputs($country, $stored, $allowed);

    expect($outputs['export'])->toBe($label)
        ->and($outputs['string'])->toBe('1 Main St, ' . $label)
        ->and($outputs['summary'])->toContain('<strong>Country</strong> ' . $label . '<br>')
        ->and($outputs['report'])->toBe('1 Main St, ' . $label)
        ->and($outputs['reportValues'])->toBe('1 Main St, ' . $stored)
        ->and($outputs['variable'])->toContain($label);
})->with([
    'all countries' => [[], 'AU', null, 'Australia'],
    'narrowed, still allowed' => [[], 'AU', ['AU', 'NZ'], 'Australia'],
    'full-name values' => [['optionValue' => 'full'], 'Australia', null, 'Australia'],
    'ISO labels' => [['optionLabel' => 'short'], 'AU', null, 'AU'],
]);

it('shows a stored country that matches no country as stored', function (): void {
    // Imported or legacy answers can hold text that isn't a country, which the form would now reject.
    $form = formie()->form()->addressField('address', ['label' => 'Address', 'rows' => addressOutsideAllowedRows()])->create();
    $saved = formie()->submission($form)->with(['address' => ['address1' => '1 Main St', 'country' => 'AU']])->save();
    $saved->setFieldValue('address', ['address1' => '1 Main St', 'country' => 'Atlantis']);
    expect(Craft::$app->getElements()->saveElement($saved, false))->toBeTrue();

    $query = Submission::find()->id($saved->id)->status(null);
    $submission = (clone $query)->one();
    $address = $form->getFieldByHandle('address');
    $value = $submission->getFieldValue('address');

    expect((new SubmissionExport())->export(clone $query)[0]['Address: Country'] ?? '<missing>')->toBe('Atlantis')
        ->and($address->getValueAsString($value, $submission))->toBe('1 Main St, Atlantis')
        ->and((string)$address->getValueForSummary($value, $submission))->toContain('<strong>Country</strong> Atlantis<br>');
});

it('leaves an empty country empty', function (): void {
    $outputs = addressOutsideAllowedOutputs([], '', ['NZ']);

    expect($outputs['export'])->toBe('')
        ->and($outputs['string'])->toBe('1 Main St')
        ->and($outputs['summary'])->toContain('<strong>Country</strong> <br>')
        ->and($outputs['report'])->toBe('1 Main St');
});
