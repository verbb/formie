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

it('resolves country codes from common geo headers', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->getHeaders()->set('CF-IPCountry', 'au');

        expect(Formie::$plugin->getCountries()->getCountryCodeForRequest($request))->toBe('AU')
            ->and(Formie::$plugin->getCountries()->getCountryForRequest($request))->toMatchArray([
                'countryCode' => 'AU',
                'countryName' => 'Australia',
            ]);
    });
})->group('fields');

it('returns null when no geo country header is present', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        expect(Formie::$plugin->getCountries()->getCountryCodeForRequest($request))->toBeNull();
    });
})->group('fields');

it('returns country lookup json from the country-from-ip endpoint', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->getHeaders()->set('CF-IPCountry', 'US');

        $controller = new AddressController('formie-address-country-from-ip', Craft::$app);
        $response = $controller->actionCountryFromIp();

        expect($response->data)->toMatchArray([
            'countryCode' => 'US',
            'countryName' => 'United States',
        ]);
    });
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
