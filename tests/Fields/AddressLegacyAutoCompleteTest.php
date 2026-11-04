<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\Db;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\content\FieldStorageCodec;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\exporters\SubmissionExport;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\subfields\AddressAutoComplete;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\Notification;

const LEGACY_AUTOCOMPLETE_TEXT = '1 Legacy Way, Melbourne VIC 3000';

function legacyAddressValue(array $parts = []): array
{
    return array_merge(['autocomplete' => LEGACY_AUTOCOMPLETE_TEXT, 'address1' => null, 'address2' => null, 'address3' => null, 'city' => null, 'state' => null, 'zip' => null, 'country' => null], $parts);
}

function legacyAddressSubmission(Form $form, array $content): Submission
{
    $submission = formie()->submission($form)->save();
    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submission->id]);

    return legacyAddressReload($submission->id);
}

function legacyAddressReload(int $id): Submission
{
    return Submission::find()->id($id)->status(null)->isIncomplete(null)->one();
}

function legacyAddressStored(int $id): array
{
    return Json::decode((new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $id])->scalar()) ?: [];
}

function legacyAddressCpSave(Form $form, Submission $submission, array $values): Submission
{
    $response = WebRequestTestHelper::withWebRequestContext(function() {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());

        return (new SubmissionsController('formie-submissions-test', Craft::$app))->actionSaveAdminSubmission();
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => [
            'handle' => $form->handle,
            'submissionId' => $submission->id,
            'expectedVersion' => $submission->stateVersion,
            'siteId' => $form->siteId,
            'fields' => $values,
        ],
    ]);

    expect($response?->data['success'] ?? false)->toBeTrue(Json::encode($response?->data));

    return legacyAddressReload($submission->id);
}

function legacyAddressForm(array $settings = []): Form
{
    $form = formie()->form()->addressField('address', ['rows' => (new Address())->getSubFields(), ...$settings])->create();

    return Form::find()->id($form->id)->one();
}

function legacyAddressRows(array $autoComplete): array
{
    return [['fields' => [['type' => AddressAutoComplete::class, 'handle' => 'autoComplete', 'label' => 'Find address', ...$autoComplete]]], ...(new Address())->getSubFields()];
}

it('outputs stored auto-complete text for an address without that sub-field', function(array $stored): void {
    $form = legacyAddressForm();
    $field = $form->getFieldByHandle('address');
    expect($field->getFieldByHandle('autoComplete'))->toBeNull();

    $submission = legacyAddressSubmission($form, [$field->uid => $stored]);
    $value = $submission->getFieldValue('address');

    expect((string)$value)->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and($field->getValueAsString($value, $submission))->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and($field->getValueForReference($value, $submission))->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and($field->getValueForReport($value, $submission))->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and($field->getValueAsData($value, $submission)['autoComplete'])->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and((string)$field->getValueForSummary($value, $submission))->toBe('<strong>Auto-Complete</strong> ' . LEGACY_AUTOCOMPLETE_TEXT . '<br>')
        ->and($submission->getValuesForExport()['Address: Auto-Complete'])->toBe(LEGACY_AUTOCOMPLETE_TEXT);
})->with([
    'Formie 2 key' => [legacyAddressValue()],
    'only the text' => [['autocomplete' => LEGACY_AUTOCOMPLETE_TEXT]],
    'current key' => [['autoComplete' => LEGACY_AUTOCOMPLETE_TEXT]],
]);

it('exports stored auto-complete text alongside ordinary answers in one set of columns', function(): void {
    $form = legacyAddressForm();
    $field = $form->getFieldByHandle('address');
    legacyAddressSubmission($form, [$field->uid => legacyAddressValue(['autocomplete' => null, 'city' => 'Bendigo'])]);
    legacyAddressSubmission($form, [$field->uid => legacyAddressValue()]);

    $rows = (new SubmissionExport())->export(Submission::find()->formId($form->id)->status(null)->orderBy(['elements.id' => SORT_ASC]));

    expect($rows)->toHaveCount(2)
        ->and(array_keys($rows[0]))->toEqual(array_keys($rows[1]))
        ->and($rows[0]['Address: City'])->toBe('Bendigo')
        ->and($rows[0]['Address: Auto-Complete'])->toBe('')
        ->and($rows[1]['Address: City'])->toBe('')
        ->and($rows[1]['Address: Auto-Complete'])->toBe(LEGACY_AUTOCOMPLETE_TEXT);
});

it('escapes stored auto-complete text in summaries', function(): void {
    $form = legacyAddressForm();
    $field = $form->getFieldByHandle('address');
    $submission = legacyAddressSubmission($form, [$field->uid => ['autocomplete' => '<b>1 Way</b>']]);

    expect((string)$field->getValueForSummary($submission->getFieldValue('address'), $submission))->toBe('<strong>Auto-Complete</strong> &lt;b&gt;1 Way&lt;/b&gt;<br>');
});

it('retains stored auto-complete text across repeated saves without changing the form', function(): void {
    $form = legacyAddressForm();
    $field = $form->getFieldByHandle('address');
    $submission = legacyAddressSubmission($form, [$field->uid => legacyAddressValue()]);

    foreach ([1, 2] as $run) {
        expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
        $submission = legacyAddressReload($submission->id);

        expect((string)$submission->getFieldValue('address'))->toBe(LEGACY_AUTOCOMPLETE_TEXT)
            ->and(legacyAddressStored($submission->id)[$field->uid]['autoComplete'])->toBe(LEGACY_AUTOCOMPLETE_TEXT);
    }

    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded->getFieldByHandle('address')->getFieldByHandle('autoComplete'))->toBeNull()
        ->and((string)Formie::$plugin->getRendering()->renderField($reloaded, $reloaded->getFieldByHandle('address')))->not->toContain('autoComplete');
});

it('retains stored auto-complete text when a submission is edited in the control panel', function(): void {
    $form = formie()->form()
        ->addressField('address', ['rows' => (new Address())->getSubFields()])
        ->groupField('details', ['rows' => [['fields' => [['type' => Address::class, 'handle' => 'nested', 'label' => 'Nested', 'rows' => (new Address())->getSubFields()]]]]])
        ->create();
    $form = Form::find()->id($form->id)->one();
    $field = $form->getFieldByHandle('address');
    $group = $form->getFieldByHandle('details');
    $nested = $group->getFieldByHandle('nested');
    $submission = legacyAddressSubmission($form, [
        $field->uid => legacyAddressValue(),
        $group->uid => [$nested->uid => legacyAddressValue()],
    ]);

    $saved = legacyAddressCpSave($form, $submission, ['address' => ['city' => 'Geelong'], 'details' => ['nested' => ['city' => '']]]);

    expect($saved->getFieldValue('address')->toArray())->toMatchArray(['autoComplete' => LEGACY_AUTOCOMPLETE_TEXT, 'city' => 'Geelong'])
        ->and((string)$saved->getFieldValue('details.nested'))->toBe(LEGACY_AUTOCOMPLETE_TEXT);

    // With the city in place the address reads from its enabled sub-fields, as for any other answer.
    expect($field->getValueAsString($saved->getFieldValue('address'), $saved))->toBe('Geelong');
});

it('keeps stored auto-complete text in a repeater row', function(): void {
    $form = formie()->form()->repeaterField('places', ['rows' => [['fields' => [['type' => Address::class, 'handle' => 'place', 'label' => 'Place', 'rows' => (new Address())->getSubFields()]]]]])->create();
    $form = Form::find()->id($form->id)->one();
    $repeater = $form->getFieldByHandle('places');
    $place = $repeater->getFieldByHandle('place');
    $submission = legacyAddressSubmission($form, [$repeater->uid => [[$place->uid => legacyAddressValue()], [$place->uid => legacyAddressValue(['autocomplete' => null, 'city' => 'Ballarat'])]]]);

    expect((string)$submission->getFieldValue('places.0.place'))->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and((string)$submission->getFieldValue('places.1.place'))->toBe('Ballarat');

    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    $saved = legacyAddressReload($submission->id);

    expect((string)$saved->getFieldValue('places.0.place'))->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and((string)$saved->getFieldValue('places.1.place'))->toBe('Ballarat');
});

it('keeps auto-complete text with its own repeater row when rows change in the control panel', function(): void {
    $form = formie()->form()->repeaterField('places', ['rows' => [['fields' => [['type' => Address::class, 'handle' => 'place', 'label' => 'Place', 'rows' => (new Address())->getSubFields()]]]]])->create();
    $form = Form::find()->id($form->id)->one();
    $repeater = $form->getFieldByHandle('places');
    $place = $repeater->getFieldByHandle('place');
    $submission = legacyAddressSubmission($form, [$repeater->uid => [
        [$place->uid => legacyAddressValue(['autocomplete' => 'First row text'])],
        [$place->uid => legacyAddressValue(['autocomplete' => 'Second row text'])],
    ]]);

    $saved = legacyAddressCpSave($form, $submission, ['places' => [
        1 => ['place' => ['city' => '']],
        'new1' => ['place' => ['city' => 'Ballarat']],
    ]]);
    $rows = $saved->getFieldValue('places');

    expect($rows)->toHaveCount(2)
        ->and((string)$saved->getFieldValue('places.0.place'))->toBe('Second row text')
        ->and((string)$saved->getFieldValue('places.1.place'))->toBe('Ballarat');
});

it('reads the Formie 2 key through an enabled auto-complete sub-field', function(): void {
    $form = legacyAddressForm(['rows' => legacyAddressRows(['enabled' => true])]);
    $field = $form->getFieldByHandle('address');
    $child = $field->getFieldByHandle('autoComplete');
    $submission = legacyAddressSubmission($form, [$field->uid => legacyAddressValue()]);

    expect($child)->not->toBeNull()
        ->and($submission->getFieldValue('address.autoComplete'))->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and($field->getValueAsString($submission->getFieldValue('address'), $submission))->toBe(LEGACY_AUTOCOMPLETE_TEXT);

    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();

    expect(legacyAddressStored($submission->id)[$field->uid][$child->uid])->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and((string)legacyAddressReload($submission->id)->getFieldValue('address'))->toBe(LEGACY_AUTOCOMPLETE_TEXT);
});

it('outputs auto-complete text from a disabled sub-field only when nothing else was answered', function(): void {
    $form = legacyAddressForm(['rows' => legacyAddressRows(['enabled' => false])]);
    $field = $form->getFieldByHandle('address');
    $only = legacyAddressSubmission($form, [$field->uid => legacyAddressValue()]);
    $both = legacyAddressSubmission($form, [$field->uid => legacyAddressValue(['address1' => '9 Current St', 'city' => 'Bendigo'])]);

    expect($field->getValueAsString($only->getFieldValue('address'), $only))->toBe(LEGACY_AUTOCOMPLETE_TEXT)
        ->and($field->getValueAsString($both->getFieldValue('address'), $both))->toBe('9 Current St, Bendigo')
        ->and($field->getValueForReport($both->getFieldValue('address'), $both))->toBe('9 Current St, Bendigo')
        ->and($both->getValuesForExport())->not->toHaveKey('Address: Auto-Complete');
});

it('leaves ordinary and empty addresses unchanged', function(): void {
    $form = legacyAddressForm();
    $field = $form->getFieldByHandle('address');
    $ordinary = legacyAddressSubmission($form, [$field->uid => legacyAddressValue(['autocomplete' => null, 'address1' => '9 Current St', 'city' => 'Bendigo'])]);
    $empty = legacyAddressSubmission($form, [$field->uid => legacyAddressValue(['autocomplete' => '   '])]);

    expect($field->getValueAsString($ordinary->getFieldValue('address'), $ordinary))->toBe('9 Current St, Bendigo')
        ->and($ordinary->getValuesForExport())->not->toHaveKey('Address: Auto-Complete')
        ->and($empty->getFieldValue('address'))->toBeNull();

    expect(Craft::$app->getElements()->saveElement($ordinary, false))->toBeTrue();

    expect(legacyAddressStored($ordinary->id)[$field->uid])->not->toHaveKey('autoComplete');
});

it('does not accept auto-complete text from a request when the field has no such sub-field', function(array $payload): void {
    $form = legacyAddressForm();
    $field = $form->getFieldByHandle('address');
    $submission = new Submission();
    $submission->setForm($form);

    $value = $field->normalizeValueFromRequest(['address1' => '9 Current St', ...$payload], $submission);

    expect($value->toArray()['autoComplete'])->toBeNull()
        ->and((string)$value)->toBe('9 Current St');
})->with([
    'current key' => [['autoComplete' => 'Injected']],
    'Formie 2 key' => [['autocomplete' => 'Injected']],
]);

it('reads and keeps auto-complete text inside an encrypted address', function(): void {
    $form = legacyAddressForm(['enableContentEncryption' => true]);
    $field = $form->getFieldByHandle('address');
    $submission = legacyAddressSubmission($form, [$field->uid => FieldStorageCodec::encode(legacyAddressValue(), true)]);

    expect($field->getValueAsString($submission->getFieldValue('address'), $submission))->toBe(LEGACY_AUTOCOMPLETE_TEXT);

    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();

    expect(json_encode(legacyAddressStored($submission->id)))->not->toContain('Legacy Way')
        ->and((string)legacyAddressReload($submission->id)->getFieldValue('address'))->toBe(LEGACY_AUTOCOMPLETE_TEXT);
});

function renderAddressNotification(Form $form, array $content): string
{
    $notification = new Notification([
        'formId' => $form->id,
        'name' => 'Address Notice',
        'handle' => 'addressNotice' . uniqid(),
        'enabled' => true,
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Address',
        'content' => '{allFields}',
    ]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();

    $submission = legacyAddressSubmission($form, $content);
    $result = Formie::$plugin->getEmails()->renderEmail($notification, legacyAddressReload($submission->id));

    expect($result['error'] ?? null)->toBeNull();

    return (string)$result['email']->getSymfonyEmail()->getHtmlBody();
}

it('shows stored auto-complete text in a notification for an address without that sub-field', function(): void {
    $form = legacyAddressForm();
    $html = renderAddressNotification($form, [$form->getFieldByHandle('address')->uid => legacyAddressValue()]);

    expect($html)->toContain(LEGACY_AUTOCOMPLETE_TEXT)->toContain('Auto-Complete');
});

it('shows the address parts rather than the stored text when parts were answered', function(): void {
    $form = legacyAddressForm();
    $html = renderAddressNotification($form, [$form->getFieldByHandle('address')->uid => legacyAddressValue(['city' => 'Geelong'])]);

    expect($html)->toContain('Geelong')->not->toContain(LEGACY_AUTOCOMPLETE_TEXT);
});

it('shows auto-complete text once in a notification when the sub-field is enabled', function(): void {
    $form = legacyAddressForm(['rows' => legacyAddressRows(['enabled' => true])]);
    $field = $form->getFieldByHandle('address');
    $html = renderAddressNotification($form, [$field->uid => legacyAddressValue()]);

    expect(substr_count($html, LEGACY_AUTOCOMPLETE_TEXT))->toBe(1);
});

it('keeps the placeholders in a notification for an address with no answer', function(): void {
    $form = legacyAddressForm();
    $html = renderAddressNotification($form, [$form->getFieldByHandle('address')->uid => ['address1' => null]]);

    expect($html)->not->toContain('Auto-Complete')->toContain('No response');
});
