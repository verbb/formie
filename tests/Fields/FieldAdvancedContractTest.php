<?php

declare(strict_types=1);

use verbb\formie\fields\Address;
use verbb\formie\fields\Name;

it('supports advanced field families at layout level', function (): void {
    $form = formie()
        ->form(['title' => 'Advanced Fields'])
        ->nameField('name', [
            'useMultipleFields' => true,
            'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
        ])
        ->addressField('address', [
            'rows' => (new Address())->getSubFields(),
        ])
        ->phoneField('phone')
        ->passwordField('password')
        ->agreeField('terms')
        ->tableField('tableData')
        ->signatureField('signature')
        ->create();

    expect($form->getFieldByHandle('name'))->not->toBeNull()
        ->and($form->getFieldByHandle('address'))->not->toBeNull()
        ->and($form->getFieldByHandle('phone'))->not->toBeNull()
        ->and($form->getFieldByHandle('password'))->not->toBeNull()
        ->and($form->getFieldByHandle('terms'))->not->toBeNull()
        ->and($form->getFieldByHandle('tableData'))->not->toBeNull()
        ->and($form->getFieldByHandle('signature'))->not->toBeNull();

    $submission = formie()
        ->submission($form)
        ->with([
            'name' => [
                'firstName' => 'Ada',
                'lastName' => 'Lovelace',
            ],
            'address' => [
                'address1' => '123 Main St',
                'city' => 'Melbourne',
                'state' => 'VIC',
                'zip' => '3000',
                'country' => 'AU',
            ],
            'phone' => '0400000001',
            'password' => 'S3cretPass',
            'terms' => true,
        ])
        ->save();

    expect($submission->id)->not->toBeNull()
        ->and($submission->getFieldValue('name.firstName'))->toBe('Ada')
        ->and($submission->getFieldValue('name.lastName'))->toBe('Lovelace')
        ->and($submission->getFieldValue('address.city'))->toBe('Melbourne')
        ->and($submission->getFieldValueForSummary('phone'))->not->toBeEmpty()
        ->and($submission->getFieldValueForExport('password'))->not->toBe('S3cretPass');
});
