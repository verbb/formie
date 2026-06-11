<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\AddressController;

use yii\web\BadRequestHttpException;

it('returns subdivisions for a valid country code', function (): void {
    $metadata = Formie::$plugin->getCountries()->getAddressFormatMetadata('US');

    expect($metadata['administrativeAreaUsed'])->toBeTrue()
        ->and($metadata['administrativeAreaType'])->toBe('state');

    $subdivisions = Formie::$plugin->getCountries()->getAddressSubdivisions('US');

    expect($subdivisions)->not->toBeEmpty()
        ->and($subdivisions[0])->toHaveKeys(['label', 'value', 'name', 'short']);
})->group('fields');

it('hides administrative area metadata for countries without subdivisions in the address format', function (): void {
    $metadata = Formie::$plugin->getCountries()->getAddressFormatMetadata('GB');

    expect($metadata['administrativeAreaUsed'])->toBeFalse();
})->group('fields');

it('resolves full country names to iso codes', function (): void {
    expect(Formie::$plugin->getCountries()->resolveCountryCode('United States'))->toBe('US')
        ->and(Formie::$plugin->getCountries()->resolveCountryCode('AU'))->toBe('AU');
})->group('fields');

it('rejects invalid country subdivision requests', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setQueryParams([
            'country' => 'Not A Real Country',
        ]);

        $controller = new AddressController('formie-address-subdivisions', Craft::$app);

        expect(fn() => $controller->actionSubdivisions())
            ->toThrow(BadRequestHttpException::class);
    });
})->group('fields');
