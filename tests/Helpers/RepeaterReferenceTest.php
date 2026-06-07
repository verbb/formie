<?php

declare(strict_types=1);

use verbb\formie\fields\Email;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\References;
use verbb\formie\helpers\RepeaterReferenceHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\services\Emails;

it('parses repeater scope metadata from reference tokens', function (): void {
    $expr = References::parseReferenceExpression('{field:attendees:email;scope=all}');

    expect($expr->isValid)->toBeTrue()
        ->and($expr->identifier)->toBe('attendees')
        ->and($expr->selector)->toBe('email')
        ->and($expr->transformerParams['scope'] ?? null)->toBe('all');
});

it('resolves repeater sub-field references by scope', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Repeater Reference Scopes'])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $repeaterField = $form->getFieldByHandle('lineItems');
    expect($repeaterField)->not->toBeNull();

    $submission = formie()->submission($form)->with([
        'lineItems' => [
            ['innerText' => 'Row One'],
            ['innerText' => 'Row Two'],
            ['innerText' => 'Row Three'],
        ],
    ])->save();

    $ref = (string)$repeaterField->reference;

    expect($submission->getFieldValue(References::field($ref, 'innerText', ['scope' => 'first'])))->toBe('Row One')
        ->and($submission->getFieldValue(References::field($ref, 'innerText', ['scope' => 'last'])))->toBe('Row Three')
        ->and($submission->getFieldValue(References::field($ref, 'innerText', ['scope' => 'all'])))->toBe(['Row One', 'Row Two', 'Row Three'])
        ->and($submission->getFieldValue(References::field($ref, 'innerText', ['scope' => 'count'])))->toBe(3)
        ->and($submission->getFieldValue('{field:' . $ref . ':0:innerText}'))->toBe('Row One');
});

it('applies array transforms to repeater scope=all values', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Repeater Array Transforms'])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $repeaterField = $form->getFieldByHandle('lineItems');
    $ref = (string)$repeaterField->reference;

    $submission = formie()->submission($form)->with([
        'lineItems' => [
            ['innerText' => 'Alpha'],
            ['innerText' => 'Beta'],
        ],
    ])->save();

    $token = '{field:' . $ref . ':innerText;scope=all;transform=join;separator=|}';

    expect($submission->getFieldValue($token))->toBe('Alpha|Beta');
});

it('expands repeater email scope=all into notification recipient lists', function (): void {
    $rows = [[
        'fields' => [[
            'type' => Email::class,
            'handle' => 'guestEmail',
            'label' => 'Guest Email',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Repeater Notification Recipients'])
        ->repeaterField('guests', ['rows' => $rows])
        ->create();

    $repeaterField = $form->getFieldByHandle('guests');
    $ref = (string)$repeaterField->reference;

    $submission = formie()->submission($form)->with([
        'guests' => [
            ['guestEmail' => 'one@example.test'],
            ['guestEmail' => 'two@example.test'],
        ],
    ])->save();

    $notification = new Notification(['name' => 'Guests', 'handle' => 'guests' . uniqid()]);
    $token = References::field($ref, 'guestEmail', ['scope' => 'all']);
    $parsed = References::parseListContent($token, $submission, ['notification' => $notification]);

    expect($parsed)->toBe('one@example.test, two@example.test');
});

it('maps repeater scope=all values through integrations', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'company',
            'label' => 'Company',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Repeater Integration Mapping'])
        ->repeaterField('attendees', ['rows' => $rows])
        ->create();

    $repeaterField = $form->getFieldByHandle('attendees');
    $ref = (string)$repeaterField->reference;

    $submission = formie()->submission($form)->with([
        'attendees' => [
            ['company' => 'Acme'],
            ['company' => 'Beta Corp'],
        ],
    ])->save();

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_STRING]);
    $token = References::field($ref, 'company', ['scope' => 'all', 'transform' => 'join']);

    expect($integration->getMappedFieldValue($token, $submission, $integrationField))->toBe('Acme, Beta Corp');
});

it('stringifies repeater scope=all and custom row values in notification content', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Repeater Notification Content'])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $repeaterField = $form->getFieldByHandle('lineItems');
    $ref = (string)$repeaterField->reference;

    $submission = formie()->submission($form)->with([
        'lineItems' => [
            ['innerText' => 'a'],
            ['innerText' => 'b'],
            ['innerText' => 'c'],
            ['innerText' => 'd'],
            ['innerText' => 'e'],
        ],
    ])->save();

    $allToken = References::field($ref, 'innerText', ['scope' => 'all']);
    $customToken = References::field($ref, 'innerText', ['scope' => 'rows', 'rows' => '1,3,5']);

    expect(References::parseContent("all: {$allToken}", $submission))->toBe('all: a, b, c, d, e')
        ->and(References::parseContent("custom: {$customToken}", $submission))->toBe('custom: a, c, e');
});

it('returns null for repeater sub-field tokens without scope metadata', function (): void {
    $rows = [[
        'fields' => [[
            'type' => SingleLineText::class,
            'handle' => 'innerText',
            'label' => 'Inner Text',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Repeater Missing Scope'])
        ->repeaterField('lineItems', ['rows' => $rows])
        ->create();

    $repeaterField = $form->getFieldByHandle('lineItems');
    $ref = (string)$repeaterField->reference;

    $submission = formie()->submission($form)->with([
        'lineItems' => [
            ['innerText' => 'Row One'],
        ],
    ])->save();

    expect(RepeaterReferenceHelper::requiresScope($submission, $ref, 'innerText'))->toBeTrue()
        ->and($submission->getFieldValue(References::field($ref, 'innerText')))->toBeNull();
});
