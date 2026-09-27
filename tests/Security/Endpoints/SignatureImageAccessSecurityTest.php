<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FieldsController;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Signature;
use verbb\formie\Formie;
use verbb\formie\helpers\SignatureAccess;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260927_030000_signature_access;

use craft\db\Query;
use craft\helpers\Json;

function signatureImageResponse(array $params): ?string
{
    return WebRequestTestHelper::withWebRequestContext(function () {
        return (new FieldsController('formie-signature-access-security', Craft::$app))->actionGetSignatureImage()?->data;
    }, ['queryParams' => $params]);
}

function signatureTokenParams(string $url): array
{
    parse_str((string)parse_url($url, PHP_URL_QUERY), $params);

    return $params;
}

it('issues a non-expiring exact-value Signature capability and rejects tampering or later value changes', function (): void {
    $form = formie()->form()->signatureField('signature')->create();
    $firstBytes = 'first signature bytes';
    $secondBytes = 'replacement signature bytes';
    $submission = formie()->submission($form)->with([
        'signature' => 'data:image/png;base64,' . base64_encode($firstBytes),
    ])->save();
    $field = $form->getFieldByHandle('signature');
    $url = $field->getDownloadUrl($submission);
    $params = signatureTokenParams($url);
    $token = $params['accessToken'] ?? '';

    [$encodedPayload] = explode('.', $token, 2);
    $encodedPayload .= str_repeat('=', (4 - strlen($encodedPayload) % 4) % 4);
    $payload = Json::decode(base64_decode(strtr($encodedPayload, '-_', '+/'), true));

    expect($params)->toHaveKey('accessToken')
        ->not->toHaveKeys(['submissionUid', 'fieldId', 'fieldKey', 'siteId'])
        ->and($payload)->toMatchArray([
            'version' => 2,
            'purpose' => 'signature-image',
            'submissionUid' => $submission->uid,
            'formId' => (int)$submission->formId,
            'siteId' => (int)$submission->siteId,
            'fieldId' => (int)$field->id,
            'fieldKey' => 'signature',
            'valueHash' => hash('sha256', 'data:image/png;base64,' . base64_encode($firstBytes)),
        ])
        ->not->toHaveKey('expiresAt')
        ->and(signatureImageResponse($params))->toBe($firstBytes);

    $offset = intdiv(strlen($token), 2);
    $tampered = substr_replace($token, $token[$offset] === 'a' ? 'b' : 'a', $offset, 1);
    expect(signatureImageResponse(['accessToken' => $tampered]))->toBeNull();

    $submission->setFieldValue('signature', 'data:image/png;base64,' . base64_encode($secondBytes));
    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue()
        ->and(signatureImageResponse($params))->toBeNull();

    $replacementUrl = $field->getDownloadUrl($submission);
    expect(signatureImageResponse(signatureTokenParams($replacementUrl)))->toBe($secondBytes);
})->group('security');

it('preserves Formie 3 signed Signature URLs without allowing token downgrade', function (): void {
    $form = formie()->form()->signatureField('signature')->create();
    $bytes = 'formie three signature';
    $submission = formie()->submission($form)->with([
        'signature' => 'data:image/png;base64,' . base64_encode($bytes),
    ])->save();
    $field = $form->getFieldByHandle('signature');
    $accessKey = (new Query())
        ->select('signatureAccessKey')
        ->from(Table::FORMIE_SUBMISSIONS)
        ->where(['id' => $submission->id])
        ->scalar();
    $params = [
        'submissionUid' => $submission->uid,
        'siteId' => $submission->siteId,
        'fieldId' => $field->id,
        'fieldKey' => $field->valueKey(),
    ];
    $params['accessToken'] = hash_hmac('sha256', Json::encode([
        'purpose' => 'formie-signature-v1',
        'submissionUid' => $submission->uid,
        'formId' => (int)$submission->formId,
        'siteId' => (int)$submission->siteId,
        'fieldId' => (int)$field->id,
        'fieldKey' => $field->valueKey(),
    ]), $accessKey);

    expect(signatureImageResponse($params))->toBe($bytes);

    $params['accessToken'] = str_repeat('0', 64);
    expect(signatureImageResponse($params))->toBeNull();
})->group('security');

it('allows unsigned historical URLs only for explicitly grandfathered submissions', function (): void {
    $form = formie()->form()->singleLineTextField('name')->signatureField('signature')->create();
    $bytes = 'legacy signature';
    $protectedSubmission = formie()->submission($form)->with([
        'name' => base64_encode('not a signature'),
        'signature' => 'data:image/png;base64,' . base64_encode($bytes),
    ])->save();
    $field = $form->getFieldByHandle('signature');
    $params = ['submissionUid' => $protectedSubmission->uid, 'fieldId' => $field->id];

    expect(signatureImageResponse($params))->toBeNull();

    $submission = formie()->submission($form)->with([
        'name' => base64_encode('not a signature'),
        'signature' => 'data:image/png;base64,' . base64_encode($bytes),
    ])->save();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
        'signatureAccessKey' => null,
        'legacySignatureAccess' => true,
    ], ['id' => $submission->id])->execute();
    $legacySubmission = Submission::find()->id($submission->id)->siteId($submission->siteId)->status(null)->one();
    $legacyParams = ['submissionUid' => $legacySubmission->uid, 'fieldId' => $field->id];

    expect(SignatureAccess::usesLegacyAccess($legacySubmission))->toBeTrue()
        ->and(signatureImageResponse($legacyParams))->toBe($bytes)
        ->and(signatureImageResponse($legacyParams + ['accessToken' => '']))->toBeNull()
        ->and(signatureImageResponse([
            'submissionUid' => $legacySubmission->uid,
            'fieldId' => $form->getFieldByHandle('name')->id,
        ]))->toBeNull();

    $settings = Formie::$plugin->getSettings();
    $previous = $settings->allowLegacySignatureImageUrls;
    $settings->allowLegacySignatureImageUrls = false;

    try {
        expect(signatureImageResponse($legacyParams))->toBeNull();
    } finally {
        $settings->allowLegacySignatureImageUrls = $previous;
    }
})->group('security');

it('does not guess nested Signature values for unsigned historical URLs', function (): void {
    $form = formie()->form()->repeaterField('signers', ['rows' => [['fields' => [[
        'type' => Signature::class,
        'label' => 'Signature',
        'handle' => 'signature',
    ]]]]])->create();
    $submission = formie()->submission($form)->with(['signers' => [[
        'signature' => 'data:image/png;base64,' . base64_encode('nested legacy signature'),
    ]]])->save();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
        'signatureAccessKey' => null,
        'legacySignatureAccess' => true,
    ], ['id' => $submission->id])->execute();
    $legacySubmission = Submission::find()->id($submission->id)->siteId($submission->siteId)->status(null)->one();
    $nestedField = $form->getFieldByHandle('signers')->getFields(0)[0];

    expect(SignatureAccess::usesLegacyAccess($legacySubmission))->toBeTrue()
        ->and(signatureImageResponse([
            'submissionUid' => $legacySubmission->uid,
            'fieldId' => $nestedField->id,
        ]))->toBeNull();
})->group('security');

it('binds Repeater Signature capabilities to the exact row path and value', function (): void {
    $form = formie()->form()->repeaterField('signers', ['rows' => [['fields' => [[
        'type' => Signature::class,
        'label' => 'Signature',
        'handle' => 'signature',
    ]]]]])->create();
    $firstBytes = 'first repeater signature';
    $secondBytes = 'second repeater signature';
    $submission = formie()->submission($form)->with(['signers' => [
        ['signature' => 'data:image/png;base64,' . base64_encode($firstBytes)],
        ['signature' => 'data:image/png;base64,' . base64_encode($secondBytes)],
    ]])->save();
    $repeater = $form->getFieldByHandle('signers');
    $firstField = $repeater->getFields(0)[0];
    $secondField = $repeater->getFields(1)[0];
    $firstUrl = $firstField->getDownloadUrl($submission);
    $secondUrl = $secondField->getDownloadUrl($submission);
    $firstParams = signatureTokenParams($firstUrl);
    $secondParams = signatureTokenParams($secondUrl);

    expect($firstField->id)->toBe($secondField->id)
        ->and($firstField->valueKey())->toBe('signers.0.signature')
        ->and($secondField->valueKey())->toBe('signers.1.signature')
        ->and($firstParams['accessToken'])->not->toBe($secondParams['accessToken'])
        ->and(signatureImageResponse($firstParams))->toBe($firstBytes)
        ->and(signatureImageResponse($secondParams))->toBe($secondBytes);
})->group('security');

it('keeps new access keys stable when an existing submission is resaved', function (): void {
    $form = formie()->form()->signatureField('signature')->create();
    $submission = formie()->submission($form)->with([
        'signature' => 'data:image/png;base64,' . base64_encode('stable key signature'),
    ])->save();
    $query = fn() => (new Query())->select(['signatureAccessKey', 'legacySignatureAccess'])
        ->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->one();
    $before = $query();

    expect($before['signatureAccessKey'])->toBeString()->not->toBe('')
        ->and((bool)$before['legacySignatureAccess'])->toBeFalse()
        ->and(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue()
        ->and($query())->toBe($before);
})->group('security');

it('does not grant unsigned legacy access when the migration runs from Formie 4', function (): void {
    $form = formie()->form()->signatureField('signature')->create();
    $submission = formie()->submission($form)->with([
        'signature' => 'data:image/png;base64,' . base64_encode('native formie four signature'),
    ])->save();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
        'signatureAccessKey' => null,
        'legacySignatureAccess' => false,
    ], ['id' => $submission->id])->execute();

    expect((new m260927_030000_signature_access())->safeUp())->toBeTrue();

    $state = (new Query())->select(['signatureAccessKey', 'legacySignatureAccess'])
        ->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->one();

    expect($state['signatureAccessKey'])->toBeNull()
        ->and((bool)$state['legacySignatureAccess'])->toBeFalse();
})->group('security');
