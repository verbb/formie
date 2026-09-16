<?php

declare(strict_types=1);

use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\tasks\dispatch\DispatchState;

it('records delivery only after success and permits retries after failures', function (?string $token): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Recovery'])->save();
    $state = new DispatchState(new SubmissionRequest([
        'form' => $form,
        'submission' => $submission,
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'requestToken' => $token,
    ]), true);

    expect(fn() => $state->runOnce('recovery', function (): void {
        throw new RuntimeException('Temporary delivery failure');
    }))->toThrow(RuntimeException::class);
    expect($state->hasMarker('recovery'))->toBeFalse();
    expect($state->runOnce('recovery', fn() => false))->toBeFalse();
    expect($state->hasMarker('recovery'))->toBeFalse();

    $calls = 0;
    $deliver = function () use (&$calls): void { $calls++; };
    expect($state->runOnce('recovery', $deliver))->toBeTrue();
    expect($state->hasMarker('recovery'))->toBeTrue();
    expect($state->runOnce('recovery', $deliver))->toBeFalse();
    expect($calls)->toBe(1);
})->with([null, 'recovery-token']);

it('serializes concurrent delivery attempts without acknowledging unfinished work', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Concurrent'])->save();
    $state = new DispatchState(new SubmissionRequest([
        'form' => $form,
        'submission' => $submission,
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'requestToken' => 'concurrent-delivery',
    ]), true);
    $log = tempnam(sys_get_temp_dir(), 'formie-delivery-');
    $workers = [];
    try {
        for ($i = 0; $i < 2; $i++) {
            $process = proc_open([PHP_BINARY, dirname(__DIR__) . '/Support/dispatch-worker.php', (string)$submission->id, $log], [
                0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
            ], $pipes);
            if (!is_resource($process)) {
                throw new RuntimeException('Unable to start delivery worker.');
            }
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }

        foreach ($workers as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($process), $output);
        }
        expect(file_get_contents($log))->toBe("delivered\n");
        expect($state->hasMarker('concurrent'))->toBeTrue();
    } finally {
        foreach ($workers as [$process, $pipes]) {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
        }
        unlink($log);
    }
});
