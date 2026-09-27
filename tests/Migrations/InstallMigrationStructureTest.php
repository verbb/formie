<?php

it('keeps fresh installation independent of timestamped upgrade migrations', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/migrations/Install.php');

    expect($source)->toBeString()
        ->and(preg_match('/new\s+m\d{6}_\d{6}_[A-Za-z0-9_]+\s*\(/', $source))->toBe(0);
});
