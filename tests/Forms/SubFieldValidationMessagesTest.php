<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Date;
use verbb\formie\fields\Name;
use verbb\formie\fields\subfields\Address1;
use verbb\formie\fields\subfields\DateDayNumber;
use verbb\formie\fields\subfields\NameFirst;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\ValidationMessagesHelper;
use craft\base\Element;
use craft\helpers\Json;

function subFieldMessagesForm(string $parentType): Form
{
    return match ($parentType) {
        Name::class => formie()->form()->nameField('person', [
            'label' => 'Name',
            'useMultipleFields' => true,
            'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
        ])->create(),
        Address::class => formie()->form()->addressField('place', [
            'label' => 'Address',
            'rows' => (new Address())->getSubFields(),
        ])->create(),
        Date::class => formie()->form()->dateField('birthday', [
            'label' => 'Birthday',
            'displayType' => 'inputs',
        ])->create(),
    };
}

// The builder config entry for a sub-field, by parent and sub-field handle.
function subFieldBuilderConfig(array $pages, string $parentHandle, string $childHandle): array
{
    foreach ($pages[0]['rows'] as $row) {
        foreach ($row['fields'] as $parent) {
            if (($parent['handle'] ?? null) !== $parentHandle) {
                continue;
            }

            foreach ($parent['rows'] as $childRow) {
                foreach ($childRow['fields'] as $child) {
                    if (($child['handle'] ?? null) === $childHandle) {
                        return $child;
                    }
                }
            }
        }
    }

    throw new RuntimeException("Sub-field {$parentHandle}.{$childHandle} not found in the builder config.");
}

// Edit a sub-field as the builder does. Date edits the active `layouts` variant; Name and Address edit `rows`.
function editSubFieldBuilderConfig(array &$pages, string $parentHandle, string $childHandle, array $settings): void
{
    $edited = false;

    $editRows = static function (array &$rows) use ($childHandle, $settings, &$edited): void {
        foreach ($rows as &$childRow) {
            foreach ($childRow['fields'] as &$child) {
                if (($child['handle'] ?? null) === $childHandle) {
                    $child = array_merge($child, $settings);
                    $edited = true;
                }
            }
        }
    };

    foreach ($pages[0]['rows'] as &$row) {
        foreach ($row['fields'] as &$parent) {
            if (($parent['handle'] ?? null) !== $parentHandle) {
                continue;
            }

            $editRows($parent['rows']);

            foreach ($parent['layouts'] ?? [] as $key => $rows) {
                if (is_array($rows)) {
                    $editRows($parent['layouts'][$key]);
                }
            }
        }
    }

    if (!$edited) {
        throw new RuntimeException("Sub-field {$parentHandle}.{$childHandle} not found in the builder config.");
    }
}

it('keeps a sub-field required message through builder saves and database reloads', function (string $parentType, string $parentHandle, string $childHandle, string $childType, string $label): void {
    $form = subFieldMessagesForm($parentType);
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    editSubFieldBuilderConfig($pages, $parentHandle, $childHandle, [
        'required' => true,
        'validationMessages' => ['required' => 'Enter your {label}.'],
    ]);

    for ($save = 0; $save < 2; $save++) {
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder(Json::decode(Json::encode($pages)), $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue(Json::encode($form->getErrors()));

        Formie::$plugin->getFields()->resetFieldRegistryCache();
        Formie::$plugin->getForms()->invalidateFormCaches();
        $form = Form::find()->id($form->id)->one();
        $subField = $form->getFieldByHandle($parentHandle)->getFieldByHandle($childHandle);

        expect($subField)->toBeInstanceOf($childType)
            ->and($subField->required)->toBeTrue()
            ->and($subField->validationMessages)->toBe(['required' => 'Enter your {label}.'])
            ->and($subField->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))->toBe("Enter your {$label}.")
            ->and($subField->getValidationMessageBrowserAttribute(ValidationMessagesHelper::KEY_REQUIRED))->toBe("Enter your {$label}.");

        // Reopening the sub-field in the builder shows the saved message.
        $pages = $form->getFormLayout()->getFormBuilderConfig();
        expect(subFieldBuilderConfig($pages, $parentHandle, $childHandle)['validationMessages'] ?? null)->toBe(['required' => 'Enter your {label}.']);
    }
})->with([
    'Name First Name' => [Name::class, 'person', 'firstName', NameFirst::class, 'First Name'],
    'Address line 1' => [Address::class, 'place', 'address1', Address1::class, 'Address 1'],
    'Date day' => [Date::class, 'birthday', 'day', DateDayNumber::class, 'Day'],
]);

it('shows the sub-field required message when a submission leaves it blank', function (): void {
    $form = subFieldMessagesForm(Name::class);
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    editSubFieldBuilderConfig($pages, 'person', 'firstName', [
        'required' => true,
        'validationMessages' => ['required' => 'Enter your first name'],
    ]);

    $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder(Json::decode(Json::encode($pages)), $form));
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue(Json::encode($form->getErrors()));

    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Formie::$plugin->getForms()->invalidateFormCaches();
    $form = Form::find()->id($form->id)->one();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setScenario(Element::SCENARIO_LIVE);
    $submission->setFieldValue('person', ['firstName' => '', 'lastName' => 'Smith']);

    expect($submission->validate())->toBeFalse()
        ->and($submission->getErrorSummary(true))->toContain('Enter your first name')
        ->and($submission->getErrorSummary(true))->not->toContain('First Name cannot be blank.');
});

it('reads a sub-field message stored in the legacy errorMessage setting', function (): void {
    $name = new Name(['handle' => 'person', 'useMultipleFields' => true]);
    $first = new NameFirst(['handle' => 'firstName', 'label' => 'First Name', 'errorMessage' => 'Legacy {label} message.']);
    $first->setParentField($name);

    expect($first->validationMessages)->toBe(['required' => 'Legacy {label} message.'])
        ->and($first->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))->toBe('Legacy First Name message.');
});

it('uses the parent message for a sub-field without its own message', function (): void {
    $date = new Date([
        'handle' => 'birthday',
        'displayType' => 'inputs',
        'validationMessages' => ['required' => 'Please provide {label}.', 'invalid' => 'Fix the {label} part.'],
    ]);
    $day = new DateDayNumber(['handle' => 'day', 'label' => 'Day', 'validationMessages' => ['required' => '']]);
    $day->setParentField($date);
    $month = new DateDayNumber(['handle' => 'month', 'label' => 'Month', 'validationMessages' => ['invalid' => 'Month {label} is wrong.']]);
    $month->setParentField($date);

    expect($day->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))->toBe('Please provide Day.')
        ->and($day->getValidationMessage(ValidationMessagesHelper::KEY_INVALID))->toBe('Fix the Day part.')
        ->and($month->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))->toBe('Please provide Month.')
        ->and($month->getValidationMessage(ValidationMessagesHelper::KEY_INVALID))->toBe('Month Month is wrong.');
});
