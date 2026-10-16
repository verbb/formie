<?php

it('maps Events dates without Carbon and preserves their timezone and null values', function() {
    $process = proc_open([PHP_BINARY, __DIR__ . '/../Support/StaticAnalysis/events-date-probe.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    expect(proc_close($process))->toBe(0, $output . $error);
    $lines = explode("\n", trim($output));
    $result = json_decode(end($lines), true, 512, JSON_THROW_ON_ERROR);
    expect($result)->toBe([
        'startDate' => '2026-10-17T09:30:00+11:00',
        'timezone' => 'Australia/Melbourne',
        'endDate' => null,
        'title' => 'Mapped event',
        'captured' => true,
    ]);
});
