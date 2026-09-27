<?php

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260927_040000_integration_run_contexts;
use verbb\formie\models\IntegrationRunContext;
use verbb\formie\references\ReferenceContext;
use verbb\formie\services\IntegrationDispatcher;

it('keeps interleaved run results and reference scopes independent', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $other = formie()->submission($form)->save();
    $dispatcher = Formie::$plugin->getIntegrationDispatcher();
    foreach (['earlier' => 10, 'later' => 20] as $run => $id) {
        $context = new IntegrationRunContext();
        $context->record('contact', ['success' => true, 'elementId' => $id]);
        $dispatcher->saveContext($submission, $context, $run);
    }
    expect($dispatcher->loadContext($submission, 'earlier')->getResult('contact')['elementId'])->toBe(10)
        ->and($dispatcher->loadContext($submission, 'later')->getResult('contact')['elementId'])->toBe(20)
        ->and(ReferenceContext::forSubmission($submission)->dispatch)->toBe([]);
    $dispatcher->withRun($submission, 'earlier', function () use ($dispatcher, $submission, $other) {
        expect(ReferenceContext::forSubmission($submission)->dispatch['contact']['id'])->toBe(10)
            ->and(ReferenceContext::forSubmission($other)->dispatch)->toBe([]);
        try {
            $dispatcher->withRun($submission, 'later', function () use ($submission) {
                expect(ReferenceContext::forSubmission($submission)->dispatch['contact']['id'])->toBe(20);
                throw new RuntimeException('Synthetic nested run interruption');
            });
        } catch (RuntimeException) {
        }
        expect(ReferenceContext::forSubmission($submission)->dispatch['contact']['id'])->toBe(10);
    });
    expect(ReferenceContext::forSubmission($submission)->dispatch)->toBe([]);
});

it('merges independently completed bindings without sharing another run', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $first = new IntegrationDispatcher();
    $second = new IntegrationDispatcher();
    $one = $first->loadContext($submission, 'shared-run');
    $two = $second->loadContext($submission, 'shared-run');
    $one->record('first', ['success' => true]);
    $two->record('second', ['success' => false]);
    $first->saveContext($submission, $one, 'shared-run');
    $second->saveContext($submission, $two, 'shared-run');
    expect(array_keys($first->loadContext($submission, 'shared-run')->results))->toBe(['first', 'second'])
        ->and($first->loadContext($submission, 'another-run')->results)->toBe([]);
});

it('migrates attributed old contexts without inventing attribution or dispatching work', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $db = Craft::$app->getDb();
    $migration = new class extends Migration {};
    $migration->addColumn(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext', $migration->json());
    try {
        $db->createCommand()->update(Table::FORMIE_SUBMISSIONS, ['integrationDispatchContext' => Json::encode(['results' => [
            'first' => ['executionUid' => 'old-first', 'elementId' => 1],
            'second' => ['executionUid' => 'old-second', 'elementId' => 2],
            'unattributed' => ['elementId' => 3],
        ]])], ['id' => $submission->id])->execute();
        $attemptCount = (new Query())->from(\verbb\formie\services\DeliveryAttempts::TABLE)->count();
        expect((new m260927_040000_integration_run_contexts())->safeUp())->toBeTrue();
        $dispatcher = Formie::$plugin->getIntegrationDispatcher();
        expect(array_keys($dispatcher->loadContext($submission, 'old-first')->results))->toBe(['first'])
            ->and(array_keys($dispatcher->loadContext($submission, 'old-second')->results))->toBe(['second'])
            ->and($dispatcher->loadContext($submission, 'legacy-context:' . $submission->id)->getResult('unattributed')['elementId'])->toBe(3)
            ->and($dispatcher->loadContext($submission)->results)->toBe([])
            ->and($db->columnExists(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext'))->toBeFalse()
            ->and((new Query())->from(\verbb\formie\services\DeliveryAttempts::TABLE)->count())->toBe($attemptCount);
        expect((new m260927_040000_integration_run_contexts())->safeUp())->toBeTrue();
        expect($dispatcher->loadContext($submission, 'old-first')->getResult('first')['elementId'])->toBe(1);
    } finally {
        if ($db->columnExists(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext')) {
            $migration->dropColumn(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext');
        }
    }
});
