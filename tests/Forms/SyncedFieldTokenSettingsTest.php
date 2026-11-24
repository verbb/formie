<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use Tests\Support\UploadTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Payment;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\payments\Mollie;
use verbb\formie\migrations\m261124_000000_per_form_field_settings;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

/**
 * Form A has a Payment amount, a Hidden default value and File Upload paths that all point at its own Amount
 * field, and a stencil of synced placements of A's fields, as the stencil builder saves them. Issue #3159.
 */
function syncedTokensSource(): array
{
    UploadTestHelper::ensureUploadVolume();
    $suffix = uniqid();
    $integration = new Mollie(['name' => 'Synced tokens', 'handle' => 'syncedTokens' . bin2hex(random_bytes(3))]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    $form = formie()->form(['title' => 'Synced Tokens A', 'handle' => 'syncedTokensA' . $suffix])
        ->numberField('amount')
        ->singleLineTextField('note')
        ->paymentField('payment', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => get_class($integration)])
        ->hiddenField('tracking')
        ->fileUploadField('upload', ['restrictFiles' => false])
        ->create();

    $form = syncedTokensReload($form->id);
    syncedTokensPoint($form, $integration->handle);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $pages = StencilData::getSerializedLayout(syncedTokensReload($form->id)->getFormLayout());

    foreach ($pages as &$page) {
        foreach ($page['rows'] as &$row) {
            foreach ($row['fields'] as &$field) {
                $field['syncedDefinitionUid'] = $field['definitionUid'];
            }
        }
    }
    unset($page, $row, $field);

    $stencil = new Stencil(['name' => 'Synced Tokens', 'handle' => 'syncedTokens' . $suffix, 'scope' => StencilsService::SCOPE_SITE]);
    $stencil->data = new StencilData(['pages' => $pages]);

    return [syncedTokensReload($form->id), $stencil, $integration->handle];
}

/** Point every token setting at this form's Amount field, in the shape the builder saves. */
function syncedTokensPoint(Form $form, string $provider, string $prefix = 'file'): void
{
    $token = '{field:' . $form->getFieldByHandle('amount')->reference . '}';
    $form->getFieldByHandle('payment')->providerSettings = [$provider => [
        'amountType' => 'dynamic',
        'amountVariable' => $token,
        'currencyType' => 'fixed',
        'currencyFixed' => 'AUD',
        'paymentDescription' => 'Order ' . $token,
    ]];
    $form->getFieldByHandle('tracking')->defaultValue = 'ref-' . $token;
    $form->getFieldByHandle('upload')->filenameFormat = $prefix . '-' . $token;
    $form->getFieldByHandle('upload')->uploadLocationSubpath = 'uploads/' . $token;
}

function syncedTokensFromStencil(Stencil $stencil, string $label): Form
{
    $form = new Form(['title' => 'Synced Tokens ' . $label, 'handle' => 'syncedTokens' . $label . uniqid()]);
    $stencil->applyStencilToForm($form, true);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    return syncedTokensReload($form->id);
}

function syncedTokensReload(int $id): Form
{
    Craft::$app->getElements()->invalidateAllCaches();
    Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($id)->status(null)->one();
}

/** Which settings point at the form's own Amount field, and what the payment resolves for an amount of 42. */
function syncedTokensState(Form $form, string $provider): array
{
    $own = '{field:' . $form->getFieldByHandle('amount')->reference . '}';
    $payment = $form->getFieldByHandle('payment');
    $settings = $payment->providerSettings[$provider] ?? [];
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('amount', 42);

    try {
        $amount = (float)$payment->getPaymentIntegration()->getPaymentAmount($submission);
    } catch (Throwable $e) {
        $amount = get_class($e);
    }

    return [
        'amountVariable' => ($settings['amountVariable'] ?? null) === $own,
        'paymentDescription' => ($settings['paymentDescription'] ?? null) === 'Order ' . $own,
        'hiddenDefault' => $form->getFieldByHandle('tracking')->defaultValue === 'ref-' . $own,
        'filenameFormat' => str_ends_with((string)$form->getFieldByHandle('upload')->filenameFormat, '-' . $own),
        'uploadSubpath' => $form->getFieldByHandle('upload')->uploadLocationSubpath === 'uploads/' . $own,
        'amount' => $amount,
    ];
}

function syncedTokensOwn(): array
{
    return ['amountVariable' => true, 'paymentDescription' => true, 'hiddenDefault' => true, 'filenameFormat' => true, 'uploadSubpath' => true, 'amount' => 42.0];
}

it('keeps each form’s Payment, Hidden and File Upload tokens on its own fields when a stencil of synced fields is used again', function(): void {
    [$a, $stencil, $provider] = syncedTokensSource();
    $b = syncedTokensFromStencil($stencil, 'B');
    $c = syncedTokensFromStencil($stencil, 'C');

    foreach (['payment', 'tracking', 'upload'] as $handle) {
        expect($c->getFieldByHandle($handle)->definitionId)->toBe($a->getFieldByHandle($handle)->definitionId);
    }

    foreach ([$a, $b, $c] as $form) {
        expect(syncedTokensState(syncedTokensReload($form->id), $provider))->toBe(syncedTokensOwn());
    }
});

it('saves a synced field’s token settings for one form without changing the others', function(): void {
    [$a, $stencil, $provider] = syncedTokensSource();
    $b = syncedTokensFromStencil($stencil, 'B');

    syncedTokensPoint($b, $provider, 'changed');
    expect(Craft::$app->getElements()->saveElement($b))->toBeTrue();

    expect(syncedTokensReload($b->id)->getFieldByHandle('upload')->filenameFormat)->toStartWith('changed-')
        ->and(syncedTokensReload($a->id)->getFieldByHandle('upload')->filenameFormat)->toStartWith('file-')
        ->and(syncedTokensState(syncedTokensReload($a->id), $provider))->toBe(syncedTokensOwn());
});

it('switches a synced Payment field’s provider for one form without leaving the others without settings', function(): void {
    [$a, $stencil, $provider] = syncedTokensSource();
    $b = syncedTokensFromStencil($stencil, 'B');
    $other = new Mollie(['name' => 'Synced tokens other', 'handle' => 'syncedTokensOther' . bin2hex(random_bytes(3))]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($other, false))->toBeTrue();

    // The provider's settings are kept per form, so the provider they belong to has to be too.
    $payment = $b->getFieldByHandle('payment');
    $payment->paymentIntegration = $other->handle;
    $payment->providerSettings = [$other->handle => ['amountType' => 'fixed', 'amountFixed' => '10', 'currencyType' => 'fixed', 'currencyFixed' => 'AUD']];
    expect(Craft::$app->getElements()->saveElement($b))->toBeTrue();

    expect(syncedTokensReload($b->id)->getFieldByHandle('payment')->paymentIntegration)->toBe($other->handle)
        ->and(syncedTokensReload($a->id)->getFieldByHandle('payment')->paymentIntegration)->toBe($provider)
        ->and(syncedTokensState(syncedTokensReload($a->id), $provider))->toBe(syncedTokensOwn());
});

it('reads Payment, Hidden and File Upload token settings back per placement', function(): void {
    $settings = [
        Payment::class => ['paymentIntegration' => 'stripe', 'providerSettings' => ['stripe' => ['amountVariable' => '{field:abc}']]],
        Hidden::class => ['defaultValue' => '{field:abc}'],
        FileUpload::class => ['filenameFormat' => '{field:abc}', 'uploadLocationSubpath' => 'a/{field:abc}'],
    ];

    foreach ($settings as $type => $values) {
        $field = new $type();
        $field->applyInstanceSettings($values);

        expect($field->getInstanceSettings())->toMatchArray($values)
            ->and(array_intersect_key($field->getDefinitionSettings(), $values))->toBe([]);
    }

    // Other fields' default values have no field tokens and stay shared.
    expect((new \verbb\formie\fields\SingleLineText())->getInstanceSettings())->not->toHaveKey('defaultValue');
});

it('moves Payment, Hidden and File Upload token settings to each form, pointing at that form’s fields', function(): void {
    [$a, $stencil, $provider] = syncedTokensSource();
    $b = syncedTokensFromStencil($stencil, 'B');
    $note = $a->getFieldByHandle('note');

    // The stored shape before this change: settings on the definition, holding the last saved form's references.
    $legacy = syncedTokensReload($b->id);
    $moved = [
        'payment' => [
            'paymentIntegration' => $legacy->getFieldByHandle('payment')->paymentIntegration,
            'paymentIntegrationType' => $legacy->getFieldByHandle('payment')->paymentIntegrationType,
            'providerSettings' => $legacy->getFieldByHandle('payment')->providerSettings,
        ],
        'tracking' => ['defaultValue' => $legacy->getFieldByHandle('tracking')->defaultValue],
        'upload' => ['filenameFormat' => $legacy->getFieldByHandle('upload')->filenameFormat, 'uploadLocationSubpath' => $legacy->getFieldByHandle('upload')->uploadLocationSubpath],
    ];

    foreach ($moved as $handle => $values) {
        $definitionId = $a->getFieldByHandle($handle)->definitionId;
        $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $definitionId])->scalar());
        Db::update(Table::FORMIE_FIELDS, ['settings' => Json::encode(array_merge($settings, $values))], ['id' => $definitionId]);

        foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORM_FIELDS)->where(['fieldId' => $definitionId])->all() as $row) {
            Db::update(Table::FORMIE_FORM_FIELDS, ['settings' => Json::encode(array_diff_key(Json::decode($row['settings']), $values))], ['id' => $row['id']]);
        }
    }

    // A shared default value on another field type stays on the definition.
    $noteSettings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $note->definitionId])->scalar());
    Db::update(Table::FORMIE_FIELDS, ['settings' => Json::encode(array_merge($noteSettings, ['defaultValue' => 'Shared note']))], ['id' => $note->definitionId]);

    expect(syncedTokensState(syncedTokensReload($a->id), $provider))->not->toBe(syncedTokensOwn());

    foreach ([1, 2] as $run) {
        expect((new m261124_000000_per_form_field_settings())->safeUp())->toBeTrue();

        foreach ([$a, $b] as $form) {
            expect(syncedTokensState(syncedTokensReload($form->id), $provider))->toBe(syncedTokensOwn());
        }

        foreach (['payment' => 'providerSettings', 'tracking' => 'defaultValue', 'upload' => 'filenameFormat'] as $handle => $key) {
            $definition = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $a->getFieldByHandle($handle)->definitionId])->scalar());
            expect($definition)->not->toHaveKey($key);
        }

        expect(Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $note->definitionId])->scalar())['defaultValue'] ?? null)->toBe('Shared note')
            ->and(syncedTokensReload($a->id)->getFieldByHandle('note')->defaultValue)->toBe('Shared note');
    }
});
