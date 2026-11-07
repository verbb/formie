<?php

use craft\elements\Entry;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Entries;
use verbb\formie\fields\SingleLineText;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\types\generators\SubmissionGenerator;

function graphqlElementSelectionForm(bool $required = false, bool $section = false): array
{
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    expect($entry)->not->toBeNull();
    $settings = ['required' => $required] + ($section
        ? ['sourceType' => 'groups', 'sources' => ['section:' . $entry->getSection()->uid]]
        : ['sourceType' => 'elements', 'sourceElements' => [['id' => $entry->id]]]);
    $children = ['rows' => [['fields' => [
        ['type' => Entries::class, 'handle' => 'course', 'label' => 'Course'] + $settings,
        ['type' => SingleLineText::class, 'handle' => 'note', 'label' => 'Note'],
    ]]]];
    $form = formie()->form()->settings(['disableCaptchas' => true])
        ->entriesField('course', $settings)->groupField('details', $children)->repeaterField('items', $children)->create();

    return [$form, (int)$entry->id];
}

/** Execute parsed GraphQL documents so both typed arguments and the generic map are exercised. */
function graphqlElementSelectionRunner($form, bool $generic): Closure
{
    $mutation = $generic ? SubmissionMutation::createGenericSaveMutation() : SubmissionMutation::createSaveMutation($form);
    $definition = FieldDefinition::create($mutation);
    $name = $mutation['name'];
    $schema = new Schema([
        'query' => new ObjectType(['name' => 'ElementSelectionQuery', 'fields' => ['ping' => Type::string()]]),
        'mutation' => new ObjectType(['name' => 'ElementSelectionMutation', 'fields' => [$name => $mutation]]),
        'types' => [SubmissionGenerator::generateType($form)],
    ]);

    return function(array $fields, array $attributes = []) use ($schema, $definition, $name, $form, $generic): array {
        $variables = $attributes + ($generic ? ['formHandle' => $form->handle, 'fields' => $fields] : $fields);
        $types = [];
        $arguments = [];
        foreach ($variables as $key => $value) {
            $types[] = '$' . $key . ': ' . $definition->getArg($key)->getType();
            $arguments[] = $key . ': $' . $key;
        }
        $result = GraphQL::executeQuery($schema, 'mutation (' . implode(', ', $types) . ') { ' . $name . '(' . implode(', ', $arguments) . ') { id } }', null, null, $variables);

        return [array_map(fn($error) => $error->getMessage(), $result->errors), $result->data[$name]['id'] ?? null];
    };
}

it('preserves valid GraphQL element selections through creation partial edits and clearing', function(bool $generic, bool $section): void {
    [$form, $entryId] = graphqlElementSelectionForm(section: $section);
    withMutationDelivery($form, function() use ($form, $entryId, $generic): void {
        $save = graphqlElementSelectionRunner($form, $generic);
        [$errors, $id] = $save(['course' => [$entryId], 'details' => ['course' => [$entryId], 'note' => 'Original'], 'items' => ['rows' => [['course' => [$entryId], 'note' => 'Original']]]]);
        expect($errors)->toBe([]);
        $load = fn() => Submission::find()->id($id)->status(null)->isIncomplete(null)->isSpam(null)->one();
        $saved = $load();
        expect($saved->getFieldValue('course')->ids())->toBe([$entryId])
            ->and($saved->getFieldValue('details')['course']->ids())->toBe([$entryId])
            ->and($saved->getFieldValue('items')[0]['course']->ids())->toBe([$entryId]);

        [$errors] = $save(['details' => ['note' => 'Edited'], 'items' => ['rows' => [['note' => 'Edited']]]], ['id' => (int)$id, 'expectedVersion' => $saved->stateVersion]);
        expect($errors)->toBe([]);
        $edited = $load();
        expect($edited->getFieldValue('course')->ids())->toBe([$entryId])
            ->and($edited->getFieldValue('details')['course']->ids())->toBe([$entryId])
            ->and($edited->getFieldValue('details')['note'])->toBe('Edited')
            ->and($edited->getFieldValue('items')[0]['course']->ids())->toBe([$entryId])
            ->and($edited->getFieldValue('items')[0]['note'])->toBe('Edited');

        [$errors] = $save(['course' => [], 'details' => ['course' => []], 'items' => ['rows' => [['course' => []]]]], ['id' => (int)$id, 'expectedVersion' => $edited->stateVersion]);
        expect($errors)->toBe([]);
        $cleared = $load();
        expect($cleared->getFieldValue('course')->ids())->toBe([])
            ->and($cleared->getFieldValue('details')['course']->ids())->toBe([])
            ->and($cleared->getFieldValue('items')[0]['course']->ids())->toBe([])
            ->and($cleared->getFieldValue('details')['note'])->toBe('Edited');
    }, []);
})->with(['typed' => false, 'generic' => true])->with(['explicit choices' => false, 'section' => true]);

it('validates required GraphQL element selections at every nesting level', function(bool $generic, string $location, bool $empty): void {
    [$form, $entryId] = graphqlElementSelectionForm(true);
    withMutationDelivery($form, function() use ($form, $entryId, $generic, $location, $empty): void {
        $save = graphqlElementSelectionRunner($form, $generic);
        $fields = ['course' => [$entryId], 'details' => ['course' => [$entryId]], 'items' => ['rows' => [['course' => [$entryId]]]]];
        $invalid = $empty ? [] : [2147483647];
        if ($location === 'top') {
            $fields['course'] = $invalid;
        } elseif ($location === 'group') {
            $fields['details']['course'] = $invalid;
        } else {
            $fields['items']['rows'][0]['course'] = $invalid;
        }
        [$errors, $id] = $save($fields);
        expect($id)->toBeNull()->and(implode(' ', $errors))->toContain($empty ? 'cannot be blank' : 'Select only valid options');
        expect((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->count())->toBe(0);

        [$errors, $id] = $save(['course' => [$entryId], 'details' => ['course' => [$entryId]], 'items' => ['rows' => [['course' => [$entryId]]]]]);
        expect($errors)->toBe([])->and($id)->not->toBeNull();
    }, []);
})->with(['typed' => false, 'generic' => true])->with(['top', 'group', 'repeater'])->with(['invalid' => false, 'empty' => true]);
