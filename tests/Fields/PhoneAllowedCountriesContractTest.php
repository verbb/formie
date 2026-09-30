<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Type\Definition\ResolveInfo;
use verbb\formie\client\models\SubmitRequest;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\events\ModifyPhoneCountriesEvent;
use verbb\formie\Formie;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\resolvers\ClientFormResolver;
use verbb\formie\gql\resolvers\mutations\SubmissionResolver;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\services\Countries;
use Tests\Support\WebRequestTestHelper;
use yii\base\Event;

it('treats allowed countries as picker choices rather than server rejection rules', function (): void {
    $form = formie()
        ->form(['title' => 'Phone picker server contract ' . uniqid()])
        ->phoneField('phone', ['countryAllowed' => ['AU']])
        ->create();

    $examples = [
        'listed country' => [['number' => '0400 000 000', 'country' => 'AU'], '+61400000000', '+61'],
        'unlisted country' => [['number' => '(404) 555-1234', 'country' => 'US'], '+14045551234', '+1'],
        'international prefix differing from selected country' => [['number' => '+1 404 555 1234', 'country' => 'AU'], '+14045551234', '+1'],
        'malformed non-empty input' => [['number' => 'not a phone', 'country' => 'US'], null, null],
    ];

    foreach ($examples as $label => [$input, $canonicalNumber, $countryCode]) {
        $saved = formie()->submission($form)->with(['phone' => $input])->save();
        $value = $saved->getFieldValue('phone');

        expect($saved->id, $label)->not->toBeNull()
            ->and($value->number, $label)->toBe($input['number'])
            ->and($value->country, $label)->toBe($input['country'])
            ->and($value->canonicalNumber, $label)->toBe($canonicalNumber)
            ->and($value->countryCode, $label)->toBe($countryCode);
    }

    $optionalEmpty = formie()->submission($form)->with([
        'phone' => ['number' => '', 'country' => 'AU'],
    ])->save();
    expect($optionalEmpty->id)->not->toBeNull();

    $requiredForm = formie()
        ->form(['title' => 'Required phone picker contract ' . uniqid()])
        ->phoneField('phone', ['countryAllowed' => ['AU']])
        ->required('phone')
        ->create();
    $requiredEmpty = new Submission();
    $requiredEmpty->setForm($requiredForm);
    $requiredEmpty->setScenario(\craft\base\Element::SCENARIO_LIVE);
    $requiredEmpty->title = 'Required phone picker validation';
    $requiredEmpty->setFieldValueFromRequest('phone', ['number' => '', 'country' => 'AU']);
    $savedRequiredEmpty = Craft::$app->getElements()->saveElement($requiredEmpty);

    expect($savedRequiredEmpty)->toBeFalse()
        ->and($requiredEmpty->id)->toBeNull()
        ->and($requiredEmpty->hasErrors())->toBeTrue();
});

it('accepts an unlisted country through every public submission adapter', function (string $transport): void {
    $form = formie()
        ->form(['title' => 'Phone picker ' . $transport . ' ' . uniqid()])
        ->settings(['disableCaptchas' => true])
        ->phoneField('phone', ['countryAllowed' => ['AU']])
        ->create();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $transport): void {
        $session = Formie::$plugin->getClientSessionService()->issueInitialSession($form)->toArrayRecursive();
        $phone = ['number' => '(404) 555-1234', 'country' => 'US'];

        if (in_array($transport, ['html', 'ajax'], true)) {
            $request->setBodyParams([
                'formieHoneypot' => '',
                'formStartedAt' => (string)((int)(microtime(true) * 1000) - 60000),
                'handle' => $form->handle,
                'requestToken' => $session['tokens']['request'],
                'expectedVersion' => 0,
                'fields' => ['phone' => $phone],
            ]);
            $execution = Formie::$plugin->getSubmissionRequests()->executeManaged(new ManagedSubmissionRequest([
                'handle' => $form->handle,
                'requestToken' => $session['tokens']['request'],
                'expectedVersion' => 0,
            ]), SubmissionAuthorityType::VISITOR);
            $success = $execution->response->success;
        } else {
            $input = [
                'handle' => $form->handle,
                'session' => $session,
                'values' => ['phone' => $phone],
            ];

            if ($transport === 'graphql') {
                $gql = Craft::$app->getGql();
                try {
                    $previous = $gql->getActiveSchema();
                } catch (GqlException) {
                    $previous = null;
                }
                $gql->setActiveSchema(new GqlSchema([
                    'name' => 'Phone picker adapter parity',
                    'scope' => [
                        'formieForms.' . $form->uid . ':read',
                        'formieSubmissions.' . $form->uid . ':create',
                    ],
                ]));
                try {
                    $result = ClientFormResolver::submitForm(null, ['input' => $input]);
                } finally {
                    $gql->setActiveSchema($previous);
                }
            } else {
                $result = runClientSubmission(new SubmitRequest($input))->toArrayRecursive();
            }

            $success = $result['success'];
        }

        $saved = Submission::find()
            ->formId($form->id)
            ->isIncomplete(false)
            ->isSpam(false)
            ->status(null)
            ->one();

        expect($success)->toBeTrue()
            ->and($saved)->not->toBeNull()
            ->and($saved->getFieldValue('phone')->country)->toBe('US')
            ->and($saved->getFieldValue('phone')->canonicalNumber)->toBe('+14045551234');
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => $transport === 'html' ? 'text/html' : 'application/json'],
    ]);
})->with(['html', 'ajax', 'rest', 'graphql']);

it('accepts an unlisted country through administrative GraphQL persistence', function (): void {
    $form = formie()
        ->form(['title' => 'Phone picker administrative GraphQL ' . uniqid()])
        ->settings(['disableCaptchas' => true])
        ->phoneField('phone', ['countryAllowed' => ['AU']])
        ->create();
    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());
    $gql = Craft::$app->getGql();

    try {
        $previous = $gql->getActiveSchema();
    } catch (GqlException) {
        $previous = null;
    }

    $gql->setActiveSchema(new GqlSchema([
        'name' => 'Phone picker administrative save',
        'scope' => ['formieSubmissions.' . $form->uid . ':create'],
    ]));
    try {
        $saved = $resolver->saveSubmissionByHandle(null, [
            'formHandle' => $form->handle,
            'fields' => [
                'phone' => \craft\helpers\Json::encode(['number' => '(404) 555-1234', 'country' => 'US']),
            ],
        ], null, $resolveInfo);
    } finally {
        $gql->setActiveSchema($previous);
    }

    expect($saved->id)->not->toBeNull()
        ->and($saved->getFieldValue('phone')->country)->toBe('US')
        ->and($saved->getFieldValue('phone')->canonicalNumber)->toBe('+14045551234');
});

it('projects explicit and event-modified picker choices to both browser manifests', function (): void {
    $explicit = formie()
        ->form(['title' => 'Explicit phone picker ' . uniqid()])
        ->phoneField('phone', [
            'countryAllowed' => ['au'],
            'countryDefaultValue' => 'NZ',
        ])
        ->create();

    foreach ([BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED] as $surface) {
        $config = f01PhoneCountryModuleConfig($explicit, $surface);
        expect($config['countryAllowed'])->toBe(['AU'])
            ->and($config['countryDefaultValue'])->toBe('NZ');
    }

    $eventModified = formie()
        ->form(['title' => 'Event-modified phone picker ' . uniqid()])
        ->phoneField('phone')
        ->create();
    $field = $eventModified->getFieldByHandle('phone');
    $listener = static function (ModifyPhoneCountriesEvent $event) use ($field): void {
        if ($event->field?->uid === $field->uid) {
            $event->countries = [
                ['label' => 'Australia', 'value' => 'au', 'code' => '+61'],
                ['label' => 'New Zealand', 'value' => 'NZ', 'code' => '+64'],
                ['label' => 'Duplicate Australia', 'value' => 'AU', 'code' => '+61'],
            ];
        }
    };
    Event::on(Countries::class, Countries::EVENT_MODIFY_PHONE_COUNTRIES, $listener);

    try {
        foreach ([BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED] as $surface) {
            expect(f01PhoneCountryModuleConfig($eventModified, $surface)['countryAllowed'])->toBe(['AU', 'NZ']);
        }
    } finally {
        Event::off(Countries::class, Countries::EVENT_MODIFY_PHONE_COUNTRIES, $listener);
    }

    $disabled = formie()
        ->form(['title' => 'Disabled phone picker ' . uniqid()])
        ->phoneField('phone', ['countryEnabled' => false, 'countryAllowed' => ['AU']])
        ->create();
    expect(f01PhoneCountryModuleConfig($disabled, BrowserModule::SURFACE_SERVER_RENDERED))->toBeNull()
        ->and(f01PhoneCountryModuleConfig($disabled, BrowserModule::SURFACE_CLIENT_RENDERED))->toBeNull();
});

function f01PhoneCountryModuleConfig(\verbb\formie\elements\Form $form, string $surface): ?array
{
    $entries = Formie::$plugin->getBrowserModuleManifestBuilder()->buildForSurface($form, $surface)->toArray()['entries'];

    foreach ($entries as $entry) {
        if ($entry['moduleId'] === 'formie:phone-country') {
            return $entry['config'];
        }
    }

    return null;
}
