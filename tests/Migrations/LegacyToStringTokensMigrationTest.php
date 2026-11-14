<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261115_000000_legacy_to_string_tokens;
use verbb\formie\models\Notification;
use verbb\formie\references\ReferenceMigration;

function toStringTokenForm(): Form
{
    return formie()->form()
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->dateField('when', ['displayType' => 'dropdowns'])
        ->addressField('place', ['rows' => (new Address())->getSubFields()])
        ->groupField('contact', ['rows' => [['fields' => [['type' => Address::class, 'handle' => 'home', 'label' => 'Home', 'settings' => ['rows' => (new Address())->getSubFields()]]]]]])
        ->create();
}

function toStringTokenNotification(Form $form, array $stored): int
{
    $notification = new Notification(['formId' => $form->id, 'name' => 'Legacy', 'handle' => 'legacy' . uniqid(), 'to' => 'saved@example.test', 'subject' => 'Saved', 'enabled' => false]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();

    // The value a site that upgraded from Formie 3 still holds, written directly as saving would reject it.
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_NOTIFICATIONS, $stored, ['id' => $notification->id])->execute();

    return (int)$notification->id;
}

it('reads the string form Formie 3 offered for fields with sub-fields as the whole field', function (): void {
    $form = toStringTokenForm();
    $person = $form->getFieldByHandle('person')->reference;
    $place = $form->getFieldByHandle('place')->reference;
    $home = $form->getFieldByHandle('contact')->getFieldByHandle('home')->reference;

    expect(ReferenceMigration::canonicalFieldTokens($form, 'New Contact Form Submission | {field:person.__toString}'))->toBe('New Contact Form Submission | {field:' . $person . '}')
        ->and(ReferenceMigration::canonicalFieldTokens($form, '{field:person.__toString|Friend}'))->toBe('{field:' . $person . '|Friend}')
        ->and(ReferenceMigration::canonicalFieldTokens($form, '{field:place.__toString} {field:contact.home.__toString}'))->toBe('{field:' . $place . '} {field:' . $home . '}')
        ->and(ReferenceMigration::canonicalFieldTokens($form, '{field:when.__toString}'))->toBe('{field:' . $form->getFieldByHandle('when')->reference . '}')
        ->and(ReferenceMigration::canonicalFieldTokens($form, '{field:person.firstName}'))->toBe('{field:' . $person . ':firstName}');
});

it('leaves unknown string form paths as they were written', function (): void {
    $form = toStringTokenForm();
    $value = '{field:nope.__toString} {field:person.bogus} {field:person.__toString.extra}';

    expect(ReferenceMigration::canonicalFieldTokens($form, $value))->toBe($value);
});

it('repairs notifications and form settings on sites that already upgraded', function (): void {
    $form = toStringTokenForm();
    $person = '{field:' . $form->getFieldByHandle('person')->reference . '}';
    $id = toStringTokenNotification($form, [
        'subject' => 'New Contact Form Submission | {field:person.__toString}',
        'content' => '[{"type":"paragraph","content":[{"type":"text","text":"From {field:person.__toString} at {field:place.__toString}"}]}]',
    ]);

    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
    $settings['submissionTitleFormat'] = '{field:person.__toString}';
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();

    $migration = new m261115_000000_legacy_to_string_tokens();

    expect($migration->safeUp())->toBeTrue();

    $row = (new Query())->from(Table::FORMIE_NOTIFICATIONS)->where(['id' => $id])->one();
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());

    expect($row['subject'])->toBe('New Contact Form Submission | ' . $person)
        ->and($row['content'])->toBe('[{"type":"paragraph","content":[{"type":"text","text":"From ' . $person . ' at {field:' . $form->getFieldByHandle('place')->reference . '}"}]}]')
        ->and($settings['submissionTitleFormat'])->toBe($person);

    $migration->safeUp();

    expect((new Query())->from(Table::FORMIE_NOTIFICATIONS)->where(['id' => $id])->one())->toBe($row);
});

it('sends a repaired notification with the full name in the subject', function (): void {
    $form = toStringTokenForm();
    $id = toStringTokenNotification($form, [
        'subject' => 'New Contact Form Submission | {field:person.__toString}',
        'content' => '[{"type":"paragraph","content":[{"type":"text","text":"From {field:person.__toString}"}]}]',
    ]);
    $submission = formie()->submission($form)->with(['person' => ['firstName' => 'Ada', 'lastName' => 'Example']])->save();

    (new m261115_000000_legacy_to_string_tokens())->safeUp();

    $notification = Formie::$plugin->getNotifications()->getNotificationById($id);
    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

    expect($result['error'] ?? null)->toBeNull();

    $email = $result['email']->getSymfonyEmail();

    expect($email->getSubject())->toBe('New Contact Form Submission | Ada Example')
        ->and((string)$email->getHtmlBody())->toContain('From Ada Example');
});
