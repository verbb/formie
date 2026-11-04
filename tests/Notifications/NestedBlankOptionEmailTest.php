<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Agree;
use verbb\formie\fields\Categories;
use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Date;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Entries;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Name;
use verbb\formie\fields\Number;
use verbb\formie\fields\Phone;
use verbb\formie\fields\Radio;
use verbb\formie\fields\Recipients;
use verbb\formie\fields\Signature;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Table;
use verbb\formie\fields\Tags;
use verbb\formie\fields\Users;
use verbb\formie\models\Notification;

function nestedEmailOptions(): array
{
    return [
        ['label' => 'Alpha', 'value' => 'alpha'],
        ['label' => 'Beta', 'value' => 'beta'],
    ];
}

function nestedEmailRecipients(): array
{
    return [
        ['label' => 'Alpha', 'value' => 'alpha@example.test'],
        ['label' => 'Beta', 'value' => 'beta@example.test'],
    ];
}

function nestedEmailForm(string $container, array $child): Form
{
    $form = formie()->form(['title' => 'Nested Email ' . uniqid()])->{$container}('details', [
        'rows' => [['fields' => [
            ['type' => SingleLineText::class, 'handle' => 'note', 'label' => 'Note'],
            ['handle' => 'choices', 'label' => 'Choices'] + $child,
        ]]],
    ])->create();

    Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($form->id)->one();
}

function renderNestedEmail(Form $form, string $container, ?array $row): array
{
    $value = $row === null ? ($container === 'repeaterField' ? [] : null) : ($container === 'repeaterField' ? [$row] : $row);
    $saved = formie()->submission($form)->with(['details' => $value])->save();

    return renderNestedSubmission($form, $saved);
}

function renderNestedSubmission(Form $form, Submission $saved): array
{
    $notification = new Notification([
        'formId' => $form->id,
        'name' => 'Nested Notice',
        'handle' => 'nestedNotice' . uniqid(),
        'enabled' => true,
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Nested',
        'content' => '{allFields}',
    ]);

    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();

    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

    return [$result, (string)($result['email']?->getSymfonyEmail()->getHtmlBody() ?? ''), $notification->getPlaceholder()];
}

it('keeps rendering a notification around a blank nested upload field', function (string $container): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = nestedEmailForm($container, ['type' => FileUpload::class, 'uploadLocationSource' => 'volume:' . $volume->uid]);
    [$result, $body] = renderNestedEmail($form, $container, ['note' => 'Present', 'choices' => null]);

    expect($result['error'] ?? null)->toBeNull()
        ->and($result['success'] ?? false)->toBeTrue()
        ->and($body)->toContain('Note', 'Present');
})->with(['groupField', 'repeaterField']);

it('keeps rendering a notification around a blank nested option field', function (string $container, array $child): void {
    $form = nestedEmailForm($container, $child);
    [$result, $body] = renderNestedEmail($form, $container, ['note' => 'Present', 'choices' => null]);

    expect($result['error'] ?? null)->toBeNull()
        ->and($result['success'] ?? false)->toBeTrue()
        ->and($body)->toContain('Note', 'Present');
})->with(['groupField', 'repeaterField'])->with([
    'multi dropdown' => [['type' => Dropdown::class, 'multi' => true, 'options' => nestedEmailOptions()]],
    'single dropdown' => [['type' => Dropdown::class, 'options' => nestedEmailOptions()]],
    'checkboxes' => [['type' => Checkboxes::class, 'options' => nestedEmailOptions()]],
    'radio' => [['type' => Radio::class, 'options' => nestedEmailOptions()]],
    'recipients checkboxes' => [['type' => Recipients::class, 'displayType' => 'checkboxes', 'options' => nestedEmailRecipients()]],
    'entries' => [['type' => Entries::class]],
    'categories' => [['type' => Categories::class]],
    'tags' => [['type' => Tags::class]],
    'users' => [['type' => Users::class]],
    'table' => [['type' => Table::class, 'columns' => [['heading' => 'A', 'handle' => 'a', 'type' => 'singleline']]]],
    'signature' => [['type' => Signature::class]],
    'agree' => [['type' => Agree::class]],
    'date' => [['type' => Date::class]],
    'number' => [['type' => Number::class]],
    'phone' => [['type' => Phone::class]],
    'name' => [['type' => Name::class]],
    'name multiple' => [['type' => Name::class, 'useMultipleFields' => true]],
    'address' => [['type' => Address::class]],
]);

it('shows the placeholder beside populated siblings for each blank multi-select shape', function (string $container, array $row): void {
    $form = nestedEmailForm($container, ['type' => Dropdown::class, 'multi' => true, 'options' => nestedEmailOptions()]);
    [$result, $body, $placeholder] = renderNestedEmail($form, $container, ['note' => 'Present'] + $row);

    expect($result['error'] ?? null)->toBeNull()
        ->and($result['success'] ?? false)->toBeTrue()
        ->and($body)->toContain('Present', 'Choices', $placeholder)
        ->and($body)->not->toContain('Alpha');
})->with(['groupField', 'repeaterField'])->with([
    'null' => [['choices' => null]],
    'absent' => [[]],
    'empty string' => [['choices' => '']],
    'empty array' => [['choices' => []]],
    'empty member' => [['choices' => ['']]],
]);

it('keeps populated nested selections in the notification', function (string $container, array $child, mixed $selection): void {
    $form = nestedEmailForm($container, $child);
    [$result, $body, $placeholder] = renderNestedEmail($form, $container, ['note' => 'Present', 'choices' => $selection]);

    expect($result['error'] ?? null)->toBeNull()
        ->and($result['success'] ?? false)->toBeTrue()
        ->and($body)->toContain('Present', 'Alpha')
        ->and($body)->not->toContain($placeholder);
})->with(['groupField', 'repeaterField'])->with([
    'multi dropdown' => [['type' => Dropdown::class, 'multi' => true, 'options' => nestedEmailOptions()], ['alpha']],
    'single dropdown' => [['type' => Dropdown::class, 'options' => nestedEmailOptions()], 'alpha'],
    'checkboxes' => [['type' => Checkboxes::class, 'options' => nestedEmailOptions()], ['alpha']],
]);

it('renders blank and populated repeater rows side by side', function (): void {
    $form = nestedEmailForm('repeaterField', ['type' => Dropdown::class, 'multi' => true, 'options' => nestedEmailOptions()]);
    $saved = formie()->submission($form)->with(['details' => [
        ['note' => 'First row', 'choices' => null],
        ['note' => 'Second row', 'choices' => ['beta']],
    ]])->save();
    [$result, $body, $placeholder] = renderNestedSubmission($form, $saved);

    expect($result['error'] ?? null)->toBeNull()
        ->and($body)->toContain('First row', $placeholder, 'Second row', 'Beta')
        ->and($body)->not->toContain('Alpha');
});

it('renders a notification for an entirely empty container', function (string $container): void {
    $form = nestedEmailForm($container, ['type' => Dropdown::class, 'multi' => true, 'options' => nestedEmailOptions()]);
    [$result, $body] = renderNestedEmail($form, $container, null);

    expect($result['error'] ?? null)->toBeNull()
        ->and($result['success'] ?? false)->toBeTrue()
        ->and($body)->not->toContain('Alpha');
})->with(['groupField', 'repeaterField']);

it('renders the placeholder for a blank top-level multi-select', function (): void {
    $form = formie()->form(['title' => 'Top Level Email ' . uniqid()])
        ->singleLineTextField('note', ['label' => 'Note'])
        ->dropdownField('choices', ['label' => 'Choices', 'multi' => true, 'options' => nestedEmailOptions()])
        ->create();
    $saved = formie()->submission($form)->with(['note' => 'Present'])->save();
    [$result, $body, $placeholder] = renderNestedSubmission($form, $saved);

    expect($result['error'] ?? null)->toBeNull()
        ->and($result['success'] ?? false)->toBeTrue()
        ->and($body)->toContain('Present', $placeholder);
});

it('renders a selected dropdown recipient in a notification', function (string $placement): void {
    $recipients = ['type' => Recipients::class, 'displayType' => 'dropdown', 'options' => nestedEmailRecipients()];

    if ($placement === 'top') {
        $form = formie()->form(['title' => 'Recipient Email ' . uniqid()])
            ->singleLineTextField('note', ['label' => 'Note'])
            ->recipientsField('choices', ['label' => 'Choices'] + $recipients)
            ->create();
        $saved = formie()->submission($form)->with(['note' => 'Present', 'choices' => 'alpha@example.test'])->save();
    } else {
        $form = nestedEmailForm($placement, $recipients);
        $row = ['note' => 'Present', 'choices' => 'alpha@example.test'];
        $saved = formie()->submission($form)->with(['details' => $placement === 'repeaterField' ? [$row] : $row])->save();
    }

    [$result, $body] = renderNestedSubmission($form, $saved);

    expect($result['error'] ?? null)->toBeNull()
        ->and($result['success'] ?? false)->toBeTrue()
        ->and($body)->toContain('Present', 'alpha@example.test');
})->with(['top', 'groupField', 'repeaterField']);
