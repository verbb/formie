<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\Formie;

it('captures runtime cache sweep baselines', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Cache Sweep Perf'])
        ->singleLineTextField('fullName')
        ->singleLineTextField('company')
        ->create();

    $formsService = Formie::$plugin->getForms();
    $fieldsService = Formie::$plugin->getFields();
    $statusHandle = Formie::$plugin->getSubmissionStatuses()->getDefaultStatus()?->handle;
    $fieldConfig = $fieldsService->getAllFieldConfigsForForms([(int)$form->id])[(int)$form->id][0] ?? [];

    expect($statusHandle)->not->toBeEmpty()
        ->and($fieldConfig)->not->toBeEmpty();

    $formsLookup = measureRuntimeCacheSweepPhase(function () use ($formsService, $form): array {
        $result = [];

        for ($i = 0; $i < 200; $i++) {
            $result[] = $formsService->getFormById((int)$form->id);
            $result[] = $formsService->getFormByHandle((string)$form->handle);
            $result[] = $formsService->getFormByUid((string)$form->uid);
        }

        return $result;
    });

    $fieldSettings = measureRuntimeCacheSweepPhase(function () use ($fieldsService, $fieldConfig): array {
        $result = [];

        for ($i = 0; $i < 1000; $i++) {
            $result[] = $fieldsService->getFieldConfigSettings($fieldConfig);
        }

        return $result;
    });

    $statusLookup = measureRuntimeCacheSweepPhase(function () use ($statusHandle): array {
        $result = [];

        for ($i = 0; $i < 500; $i++) {
            $result[] = Submission::find()->status($statusHandle)->statusId;
        }

        return $result;
    });

    $moduleManifest = measureRuntimeCacheSweepPhase(function () use ($form): array {
        $result = [];

        for ($i = 0; $i < 200; $i++) {
            $result[] = Formie::$plugin->getClientModuleManifestBuilder()->buildCanonical($form);
        }

        return $result;
    });

    fwrite(STDOUT, sprintf(
        "RUNTIME_CACHE_SWEEP_PERF %s\n",
        json_encode([
            'formsLookupMs' => $formsLookup['elapsedMs'],
            'fieldSettingsMs' => $fieldSettings['elapsedMs'],
            'statusLookupMs' => $statusLookup['elapsedMs'],
            'moduleManifestMs' => $moduleManifest['elapsedMs'],
            'counts' => [
                'formsLookup' => count($formsLookup['result']),
                'fieldSettings' => count($fieldSettings['result']),
                'statusLookup' => count($statusLookup['result']),
                'moduleManifest' => count($moduleManifest['result']),
            ],
        ], JSON_UNESCAPED_SLASHES)
    ));

    expect(count($formsLookup['result']))->toBe(600)
        ->and(count($fieldSettings['result']))->toBe(1000)
        ->and(count($statusLookup['result']))->toBe(500)
        ->and(count($moduleManifest['result']))->toBe(200)
        ->and($statusLookup['result'][0])->not->toBeNull();
})->group('perf');

it('keeps existing fields cache variants isolated by excluded form', function (): void {
    $firstForm = formie()
        ->form(['title' => 'Existing Fields First'])
        ->singleLineTextField('firstName')
        ->create();

    $secondForm = formie()
        ->form(['title' => 'Existing Fields Second'])
        ->singleLineTextField('secondName')
        ->create();

    $fieldsService = Formie::$plugin->getFields();

    $allFields = $fieldsService->getExistingFields();
    $excluded = $fieldsService->getExistingFields($firstForm);

    expect(collectExistingFieldKeys($allFields))->toContain($firstForm->handle, $secondForm->handle)
        ->and(collectExistingFieldKeys($excluded))->not->toContain($firstForm->handle)
        ->and(collectExistingFieldKeys($excluded))->toContain($secondForm->handle);
});

function measureRuntimeCacheSweepPhase(callable $callback): array
{
    resetRuntimeCacheSweepState();

    $started = microtime(true);
    $result = $callback();

    return [
        'result' => $result,
        'elapsedMs' => (int)((microtime(true) - $started) * 1000),
    ];
}

function resetRuntimeCacheSweepState(): void
{
    Formie::$plugin->getForms()->invalidateFormCaches();

    $fieldsService = Formie::$plugin->getFields();
    $resetFieldCaches = new ReflectionMethod($fieldsService, '_resetFieldCaches');
    $resetFieldCaches->setAccessible(true);
    $resetFieldCaches->invoke($fieldsService);
}

function collectExistingFieldKeys(array $existingFields): array
{
    return array_values(array_filter(array_map(static fn(array $item): string => (string)($item['key'] ?? ''), $existingFields)));
}
