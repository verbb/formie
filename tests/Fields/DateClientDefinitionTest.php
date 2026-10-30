<?php

declare(strict_types=1);

use craft\models\GqlSchema;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Date;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\values\SingleOptionFieldValue;

it('loads a saved dropdown date through GraphQL and submits its returned session', function(string $initial): void {
    $form = formie()->form()->dateField('eventDate', [
        'displayType' => 'dropdowns',
        'defaultOption' => $initial === 'default' ? 'date' : '',
        'defaultValue' => $initial === 'default' ? '2026-10-15 00:00:00' : null,
        'prefillQueryParam' => 'eventDate',
    ])->settings(['disableCaptchas' => true])->create();

    try {
        WebRequestTestHelper::withWebRequestContext(function() use ($form, $initial): void {
            $gql = Craft::$app->getGql();
            $schema = new GqlSchema(['name' => 'Date client definition', 'scope' => [
                'formieForms.' . $form->uid . ':read',
                'formieSubmissions.' . $form->uid . ':create',
            ]]);
            $loaded = $gql->executeQuery($schema, 'query($handle: String!, $query: Json) {
                formieClientForm(handle: $handle, query: $query) {
                    definition
                    session { id version currentPageId tokens continuation }
                }
            }', [
                'handle' => $form->handle,
                'query' => $initial === 'prefill' ? ['eventDate' => '2026-10-15 00:00:00'] : [],
            ]);

            expect($loaded)->not->toHaveKey('errors');
            $bootstrap = $loaded['data']['formieClientForm'];
            $parts = $bootstrap['definition']['pages'][0]['rows'][0]['fields'][0]['input']['parts'];
            $defaults = [];

            foreach ($parts as $part) {
                $defaults[$part['handle']] = $part['input']['defaultValue'];
            }

            $zero = $initial === 'prefill' ? '0' : '00';

            expect($defaults)->toBe($initial === 'blank'
                ? ['year' => null, 'month' => null, 'day' => null, 'hour' => null, 'minute' => null, 'second' => null]
                : ['year' => '2026', 'month' => '10', 'day' => '15', 'hour' => '0', 'minute' => $zero, 'second' => $zero]);

            $submitted = $gql->executeQuery($schema, 'mutation($input: FormieClientSubmitInput!) {
                submitFormieClientForm(input: $input) { outcome success errors }
            }', ['input' => [
                'handle' => $form->handle,
                'session' => $bootstrap['session'],
                'values' => ['eventDate' => ['year' => '2026', 'month' => '10', 'day' => '15', 'hour' => '0', 'minute' => '0', 'second' => '0']],
            ]]);

            expect($submitted)->not->toHaveKey('errors')
                ->and($submitted['data']['submitFormieClientForm']['outcome'])->toBe('completed')
                ->and($submitted['data']['submitFormieClientForm']['success'])->toBeTrue();

            $submission = Submission::find()->formId($form->id)->one();
            expect($submission)->not->toBeNull()
                ->and($submission->getFieldValue('eventDate')->getParts())->toMatchArray([
                    'year' => '2026', 'month' => '10', 'day' => '15', 'hour' => '0', 'minute' => '0', 'second' => '0',
                ]);
        }, ['method' => 'POST']);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
})->with(['blank', 'default', 'prefill']);

it('includes an enabled AM/PM dropdown in an inputs date client definition', function(bool $populated): void {
    $field = new Date([
        'handle' => 'eventDate',
        'displayType' => 'inputs',
        'timeFormat' => 'h:i A',
        'defaultOption' => $populated ? 'date' : '',
        'defaultValue' => $populated ? '2026-10-15 00:00:00' : null,
    ]);
    $field->setRows($field->getSubFields());
    $field->getFieldByHandle('ampm')->enabled = true;
    $parts = array_column($field->getClientRenderedInput()['parts'], null, 'handle');

    expect($parts['ampm']['input']['defaultValue'])->toBe($populated ? 'AM' : null);
})->with([false, true]);

it('preserves scalar zero and normalized option values for client inputs', function(): void {
    $field = new Dropdown(['handle' => 'size']);

    expect($field->serializeValueForClientInput(''))->toBeNull()
        ->and($field->serializeValueForClientInput(null))->toBeNull()
        ->and($field->serializeValueForClientInput('0'))->toBe('0')
        ->and($field->serializeValueForClientInput(0))->toBe(0)
        ->and($field->serializeValueForClientInput(new SingleOptionFieldValue('Large', 'l', true)))->toBe('l')
        ->and($field->serializeValueForClientInput(new OptionValue('Zero', '0', true)))->toBe('0')
        ->and($field->serializeValueForClientInput(new MultiOptionFieldValue([
            new OptionValue('Large', 'l', true), new OptionValue('Small', 's', true),
        ])))->toBe(['l', 's'])
        ->and($field->serializeValueForClientInput(new MultiOptionFieldValue()))->toBe([]);
});
