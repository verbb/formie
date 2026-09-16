<?php

use verbb\formie\helpers\SubmissionLimitHelper;

it('uses the current Sunday-to-Sunday limit window on every weekday', function (string $date): void {
    $now = new DateTime($date . ' 12:30:00', new DateTimeZone('Australia/Melbourne'));
    [$start, $end] = (new ReflectionMethod(SubmissionLimitHelper::class, '_periodBounds'))->invoke(null, 'week', $now);
    expect($start->format('Y-m-d H:i:s'))->toBe('2026-09-13 00:00:00')
        ->and($end->format('Y-m-d H:i:s'))->toBe('2026-09-20 00:00:00')
        ->and($now >= $start && $now < $end)->toBeTrue()
        ->and($now->format('H:i:s'))->toBe('12:30:00');
})->with(['2026-09-13', '2026-09-14', '2026-09-15', '2026-09-16', '2026-09-17', '2026-09-18', '2026-09-19']);

it('keeps weekly boundaries at local midnight across daylight saving', function (): void {
    $now = new DateTime('2026-10-04 12:30:00', new DateTimeZone('Australia/Melbourne'));
    [$start, $end] = (new ReflectionMethod(SubmissionLimitHelper::class, '_periodBounds'))->invoke(null, 'week', $now);
    expect($start->format('c'))->toBe('2026-10-04T00:00:00+10:00')
        ->and($end->format('c'))->toBe('2026-10-11T00:00:00+11:00');
});
