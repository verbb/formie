<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;
use verbb\formie\references\ReferenceOutputContext;

function subFieldSummaryRows(array $rows, string $handle, array $settings): array
{
    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$child) {
            if ($child['handle'] === $handle) {
                $child = array_replace($child, ['enabled' => true], $settings);
            }
        }
        unset($child);
    }
    unset($row);

    return $rows;
}

function subFieldSummaryHtml(Form $form, array $values, string $token): string
{
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $form = Form::find()->id($form->id)->one();
    $saved = formie()->submission($form)->with($values)->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();

    return (string)References::parseContent($token, $submission, ['outputContext' => ReferenceOutputContext::Html]);
}

it('outputs the Address country in email summaries', function (string $token, string $optionLabel, string $optionValue, string $summaryValue, string $submitted, string $expected): void {
    $rows = subFieldSummaryRows((new Address())->getSubFields(), 'country', [
        'label' => 'Country label',
        'optionLabel' => $optionLabel,
        'optionValue' => $optionValue,
        'emailFieldSummaryValue' => $summaryValue,
    ]);
    $form = formie()->form()->addressField('address', ['rows' => $rows])->create();

    $html = subFieldSummaryHtml($form, ['address' => ['address1' => '1 Main St', 'country' => $submitted]], $token);
    $text = preg_replace('/\s+/', ' ', strip_tags($html));

    expect($text)->toContain('1 Main St')
        ->and($text)->toContain('Country label: ' . $expected . ' ');
})->with([
    'non-empty, full label, short value, label summary' => ['{allContentFields}', 'full', 'short', 'label', 'AU', 'Australia'],
    'non-empty, full label, short value, value summary' => ['{allContentFields}', 'full', 'short', 'value', 'AU', 'AU'],
    'non-empty, full label, full value, label summary' => ['{allContentFields}', 'full', 'full', 'label', 'Australia', 'Australia'],
    'non-empty, full label, full value, value summary' => ['{allContentFields}', 'full', 'full', 'value', 'Australia', 'Australia'],
    'non-empty, short label, short value, label summary' => ['{allContentFields}', 'short', 'short', 'label', 'AU', 'AU'],
    'non-empty, short label, full value, label summary' => ['{allContentFields}', 'short', 'full', 'label', 'Australia', 'AU'],
    'non-empty, short label, full value, value summary' => ['{allContentFields}', 'short', 'full', 'value', 'Australia', 'Australia'],
    'all fields, full label, short value, label summary' => ['{allFields}', 'full', 'short', 'label', 'AU', 'Australia'],
    'all visible fields, full label, short value, value summary' => ['{allVisibleFields}', 'full', 'short', 'value', 'AU', 'AU'],
]);

it('outputs the Address country in a rendered notification email', function (string $summaryValue, string $expected): void {
    $rows = subFieldSummaryRows((new Address())->getSubFields(), 'country', [
        'label' => 'Country label',
        'emailFieldSummaryValue' => $summaryValue,
    ]);
    $form = formie()->form()->addressField('address', ['rows' => $rows])->create();

    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $form = Form::find()->id($form->id)->one();
    $saved = formie()->submission($form)->with(['address' => ['address1' => '1 Main St', 'country' => 'AU']])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $notification = new Notification([
        'name' => 'Address notice',
        'handle' => 'addressNotice',
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Address',
        'content' => '{allContentFields}',
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

    expect($result)->not->toHaveKey('error')
        ->and(preg_replace('/\s+/', ' ', strip_tags((string)$result['email']->getSymfonyEmail()->getHtmlBody())))->toContain('Country label: ' . $expected . ' ');
})->with([
    'label summary' => ['label', 'Australia'],
    'value summary' => ['value', 'AU'],
]);

it('keeps a stored country visible after the option value format changes', function (): void {
    $rows = subFieldSummaryRows((new Address())->getSubFields(), 'country', ['label' => 'Country label', 'optionValue' => 'full']);
    $form = formie()->form()->addressField('address', ['rows' => $rows])->create();
    $saved = formie()->submission($form)->with(['address' => ['address1' => '1 Main St', 'country' => 'Australia']])->save();

    // Model content saved before the field changed from ISO codes to full names.
    $saved->setFieldValue('address', ['address1' => '1 Main St', 'country' => 'AU']);
    expect(Craft::$app->getElements()->saveElement($saved, false))->toBeTrue();

    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $html = (string)References::parseContent('{allContentFields}', $submission, ['outputContext' => ReferenceOutputContext::Html]);

    expect(preg_replace('/\s+/', ' ', strip_tags($html)))->toContain('Country label: AU ');
});

it('outputs the country of an Address inside a Group in email summaries', function (): void {
    $rows = subFieldSummaryRows((new Address())->getSubFields(), 'country', ['label' => 'Country label']);
    $form = formie()->form()->groupField('details', [
        'rows' => [['fields' => [
            ['type' => Address::class, 'handle' => 'address', 'label' => 'Postal address', 'rows' => $rows],
        ]]],
    ])->create();

    $html = subFieldSummaryHtml($form, ['details' => ['address' => ['address1' => '1 Main St', 'country' => 'AU']]], '{allContentFields}');

    expect(preg_replace('/\s+/', ' ', strip_tags($html)))->toContain('Country label: Australia ');
});

it('outputs the Name prefix in email summaries', function (string $token, string $summaryValue, string $expected): void {
    $rows = subFieldSummaryRows((new Name())->getSubFields(), 'prefix', [
        'label' => 'Prefix label',
        'emailFieldSummaryValue' => $summaryValue,
    ]);
    $form = formie()->form()->nameField('fullName', ['useMultipleFields' => true, 'rows' => $rows])->create();

    $html = subFieldSummaryHtml($form, ['fullName' => ['prefix' => 'dr', 'firstName' => 'Sam']], $token);
    $text = preg_replace('/\s+/', ' ', strip_tags($html));

    expect($text)->toContain('Sam')
        ->and($text)->toContain('Prefix label: ' . $expected . ' ');
})->with([
    'non-empty, label summary' => ['{allContentFields}', 'label', 'Dr.'],
    'non-empty, value summary' => ['{allContentFields}', 'value', 'dr'],
    'all fields, label summary' => ['{allFields}', 'label', 'Dr.'],
]);
