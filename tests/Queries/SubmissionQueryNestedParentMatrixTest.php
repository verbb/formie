<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\fields\subfields\NameFirst;
use verbb\formie\fields\subfields\NameLast;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Number;

it('queries fixed parent, group, and repeater fields via nested criteria', function (): void {
    $groupRows = [[
        'fields' => [
            [
                'type' => SingleLineText::class,
                'handle' => 'innerText',
                'label' => 'Inner Text',
            ],
            [
                'type' => Number::class,
                'handle' => 'innerScore',
                'label' => 'Inner Score',
            ],
        ],
    ]];

    $nameRows = [[
        'fields' => [
            [
                'type' => NameFirst::class,
                'handle' => 'firstName',
                'label' => 'First Name',
                'enabled' => true,
            ],
            [
                'type' => NameLast::class,
                'handle' => 'lastName',
                'label' => 'Last Name',
                'enabled' => true,
            ],
        ],
    ]];

    $form = formie()
        ->form(['title' => 'Nested Query Matrix'])
        ->nameField('multiName', [
            'useMultipleFields' => true,
            'rows' => $nameRows,
        ])
        ->addressField('shippingAddress', [
            'rows' => (new Address())->getSubFields(),
        ])
        ->groupField('groupContent', [
            'rows' => $groupRows,
        ])
        ->repeaterField('lineItems', [
            'rows' => $groupRows,
        ])
        ->create();

    $matchingSubmission = formie()->submission($form)->with([
        'multiName' => [
            'firstName' => 'Alice',
            'lastName' => 'Smith',
        ],
        'shippingAddress' => [
            'address1' => '123 Main St',
            'city' => 'Melbourne',
            'state' => 'VIC',
            'zip' => '3000',
            'country' => 'AU',
        ],
        'groupContent' => [
            'innerText' => 'Needle',
            'innerScore' => '11',
        ],
        'lineItems' => [
            ['innerText' => 'Needle', 'innerScore' => '21'],
            ['innerText' => 'Other', 'innerScore' => '22'],
        ],
    ])->save();

    formie()->submission($form)->with([
        'multiName' => [
            'firstName' => 'Bob',
            'lastName' => 'Jones',
        ],
        'shippingAddress' => [
            'address1' => '999 Other St',
            'city' => 'Sydney',
            'state' => 'NSW',
            'zip' => '2000',
            'country' => 'AU',
        ],
        'groupContent' => [
            'innerText' => 'Haystack',
            'innerScore' => '31',
        ],
        'lineItems' => [
            ['innerText' => 'Haystack', 'innerScore' => '41'],
        ],
    ])->save();

    $criteria = [
        ['multiName', ['firstName' => 'Alice'], 'exact'],
        ['shippingAddress', ['address1' => '123 Main St'], 'contains'],
        ['groupContent', ['innerText' => 'Needle'], 'exact'],
        ['lineItems', ['innerText' => 'Needle'], 'contains'],
    ];

    foreach ($criteria as [$handle, $value, $expectation]) {
        $results = Submission::find()
            ->formId($form->id)
            ->field((string)$handle, $value)
            ->all();

        if ($expectation === 'contains') {
            $ids = array_map(static fn($submission) => $submission->id, $results);
            if (!in_array($matchingSubmission->id, $ids, true)) {
                throw new RuntimeException("Expected matching submission to be included for handle '{$handle}' with query value '" . json_encode($value) . "'");
            }
            continue;
        }

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe($matchingSubmission->id);
    }
});

it('queries nested parent fields via dynamic handle methods', function (): void {
    $form = formie()
        ->form(['title' => 'Nested Dynamic Handle Matrix'])
        ->singleLineTextField('fullName')
        ->nameField('multiName', [
            'useMultipleFields' => true,
            'rows' => [[
                'fields' => [
                    ['type' => NameFirst::class, 'handle' => 'firstName', 'label' => 'First Name', 'enabled' => true],
                    ['type' => NameLast::class, 'handle' => 'lastName', 'label' => 'Last Name', 'enabled' => true],
                ],
            ]],
        ])
        ->groupField('group', [
            'rows' => [[
                'fields' => [
                    ['type' => SingleLineText::class, 'handle' => 'groupTest', 'label' => 'Group Test'],
                ],
            ]],
        ])
        ->repeaterField('repeater', [
            'rows' => [[
                'fields' => [
                    ['type' => SingleLineText::class, 'handle' => 'repeaterText', 'label' => 'Repeater Text'],
                ],
            ]],
        ])
        ->create();

    $matchingSubmission = formie()->submission($form)->with([
        'fullName' => 'Baseline 41',
        'multiName' => [
            'firstName' => 'aaaa',
            'lastName' => 'bbbb',
        ],
        'group' => [
            'groupTest' => '111',
        ],
        'repeater' => [
            ['repeaterText' => '333'],
            ['repeaterText' => 'other'],
        ],
    ])->save();

    formie()->submission($form)->with([
        'fullName' => 'Baseline 99',
        'multiName' => [
            'firstName' => 'zzzz',
            'lastName' => 'yyyy',
        ],
        'group' => [
            'groupTest' => '222',
        ],
        'repeater' => [
            ['repeaterText' => '444'],
        ],
    ])->save();

    $criteria = [
        ['fullName', 'Baseline 41', 'exact'],
        ['multiName', ['firstName' => 'aaaa'], 'exact'],
        ['group', ['groupTest' => '111'], 'exact'],
        ['repeater', ['repeaterText' => '333'], 'contains'],
    ];

    foreach ($criteria as [$handle, $value, $expectation]) {
        $results = Submission::find()
            ->formId($form->id)
            ->{$handle}($value)
            ->all();

        if ($expectation === 'contains') {
            $ids = array_map(static fn($submission) => $submission->id, $results);
            if (!in_array($matchingSubmission->id, $ids, true)) {
                throw new RuntimeException("Expected matching submission to be included for dynamic handle '{$handle}'");
            }

            continue;
        }

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe($matchingSubmission->id);
    }
});

