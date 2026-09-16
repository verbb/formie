<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\FormStatus;

it('resolves numeric status criteria without losing handle and negation filters', function (): void {
    $status = new FormStatus(['name' => 'Numeric status', 'handle' => 'numericStatus' . bin2hex(random_bytes(5)), 'color' => 'blue']);
    expect(Formie::$plugin->getFormStatuses()->saveStatus($status))->toBeTrue();
    $matched = formie()->form(['formStatusId' => $status->id])->singleLineTextField('message')->create();
    $other = formie()->form()->singleLineTextField('message')->create();
    $ids = [$matched->id, $other->id];
    foreach ([$status->handle, $status->id, (string)$status->id, [$status->id]] as $criterion) {
        expect(array_map('intval', Form::find()->id($ids)->formStatusId($criterion)->status(null)->ids()))->toBe([(int)$matched->id]);
    }
    expect(array_map('intval', Form::find()->id($ids)->formStatusId('not ' . $status->id)->status(null)->ids()))->toBe([(int)$other->id]);
    expect(Form::find()->id($ids)->formStatusId(999999)->status(null)->ids())->toBe([]);
});
