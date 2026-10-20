<?php

use craft\base\ElementInterface;
use verbb\formie\fields\Date;

it('keeps visitor prefills out of persisted date child defaults', function(string $displayType): void {
    $field = new class([
        'handle' => 'eventDate',
        'displayType' => $displayType,
        'defaultOption' => 'date',
        'defaultValue' => '2024-06-15 14:30:00',
    ]) extends Date {
        public int $prefillLookups = 0;

        public function getPrefillValue(?ElementInterface $element = null, ?bool &$found = null): mixed
        {
            $this->prefillLookups++;
            $found = true;

            return $this->normalizeValue('2040-12-31 23:45:00', $element);
        }
    };

    expect($field->beforeSave(true))->toBeTrue()
        ->and($field->prefillLookups)->toBe(0);

    $date = $field->getFieldByHandle('date');
    $time = $field->getFieldByHandle('time');

    expect($date->defaultValue)->toBe('2024-06-15')
        ->and($time->defaultValue)->toBe('14:30');
})->with(['calendar', 'datePicker']);
