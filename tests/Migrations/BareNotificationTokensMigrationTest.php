<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261104_050000_bare_notification_tokens;
use verbb\formie\models\Notification;

function bareTokenNotification(Form $form, array $stored): int
{
    $notification = new Notification(['formId' => $form->id, 'name' => 'Bare', 'handle' => 'bare' . uniqid(), 'to' => 'saved@example.test', 'subject' => 'Saved', 'enabled' => false]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();

    // These are the values a Formie 3 upgrade leaves behind, written directly as saving would reject them.
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_NOTIFICATIONS, $stored, ['id' => $notification->id])->execute();

    return (int)$notification->id;
}

function bareTokenRow(int $id): array
{
    return (new Query())->from(Table::FORMIE_NOTIFICATIONS)->where(['id' => $id])->one();
}

function bareTokenForm(): Form
{
    return formie()->form()
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->groupField('details', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'city', 'label' => 'City']]]]])
        ->create();
}

it('converts bare field tokens in every notification setting that resolves references', function (): void {
    $form = bareTokenForm();
    $email = '{field:' . $form->getFieldByHandle('email')->reference . '}';
    $name = '{field:' . $form->getFieldByHandle('fullName')->reference . '}';
    $id = bareTokenNotification($form, [
        'to' => '{email}',
        'cc' => '{email}, copy@example.test',
        'bcc' => '{email}',
        'replyTo' => '{email}',
        'replyToName' => '{fullName}',
        'fromName' => '{fullName}',
        'subject' => 'Hello {fullName}',
        'content' => '[{"type":"paragraph","content":[{"type":"text","text":"Saved {fullName}"}]}]',
    ]);

    (new m261104_050000_bare_notification_tokens())->safeUp();
    $row = bareTokenRow($id);

    expect($row['to'])->toBe($email)
        ->and($row['cc'])->toBe($email . ', copy@example.test')
        ->and($row['bcc'])->toBe($email)
        ->and($row['replyTo'])->toBe($email)
        ->and($row['replyToName'])->toBe($name)
        ->and($row['fromName'])->toBe($name)
        ->and($row['subject'])->toBe('Hello ' . $name)
        ->and($row['content'])->toBe('[{"type":"paragraph","content":[{"type":"text","text":"Saved ' . $name . '"}]}]');
});

it('renders a converted notification with the submitted values', function (): void {
    $form = bareTokenForm();
    $id = bareTokenNotification($form, [
        'to' => '{email}',
        'subject' => 'Hello {fullName}',
        'content' => '[{"type":"paragraph","content":[{"type":"text","text":"Saved {fullName} in {details.city}"}]}]',
    ]);
    $submission = formie()->submission($form)->with(['fullName' => 'Ada Example', 'email' => 'ada@example.test', 'details' => ['city' => 'Geelong']])->save();

    (new m261104_050000_bare_notification_tokens())->safeUp();

    $notification = Formie::$plugin->getNotifications()->getNotificationById($id);
    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

    expect($result['error'] ?? null)->toBeNull();

    $email = $result['email']->getSymfonyEmail();

    expect(array_map(fn($address) => $address->getAddress(), $email->getTo()))->toBe(['ada@example.test'])
        ->and($email->getSubject())->toBe('Hello Ada Example')
        ->and((string)$email->getHtmlBody())->toContain('Saved Ada Example in Geelong');
});

it('leaves anything that is not an exact match on this form alone', function (): void {
    $form = bareTokenForm();
    formie()->form()->singleLineTextField('otherForm')->create();
    $reference = $form->getFieldByHandle('email')->reference;
    $value = '{unknown} {otherForm} {email|upper} {{ email }} {details.missing} {form:name} {field:' . $reference . '} {formName} no braces';
    $id = bareTokenNotification($form, ['subject' => $value]);

    (new m261104_050000_bare_notification_tokens())->safeUp();

    expect(bareTokenRow($id)['subject'])->toBe($value);
});

it('leaves settings that are not reference text alone', function (): void {
    $form = bareTokenForm();
    $conditions = '{"conditionRules":[{"field":"{email}","condition":"=","value":"x"}]}';
    $id = bareTokenNotification($form, ['conditions' => $conditions, 'name' => '{email}']);

    (new m261104_050000_bare_notification_tokens())->safeUp();

    expect(bareTokenRow($id)['conditions'])->toBe($conditions)
        ->and(bareTokenRow($id)['name'])->toBe('{email}');
});

it('converts qualified tokens with a path into a Group or sub-field', function (): void {
    $form = formie()->form()
        ->nameField('person', ['useMultipleFields' => true])
        ->groupField('details', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'city', 'label' => 'City']]]]])
        ->create();
    $city = $form->getFieldByHandle('details')->getFieldByHandle('city')->reference;
    $id = bareTokenNotification($form, ['subject' => 'From {field:details.city} and {details.city} and {field:unknown.path}']);

    (new m261104_050000_bare_notification_tokens())->safeUp();

    expect(bareTokenRow($id)['subject'])->toBe('From {field:' . $city . '} and {field:' . $city . '} and {field:unknown.path}');
});

it('converts submission attributes that Formie 3 resolved without a prefix', function (): void {
    $id = bareTokenNotification(bareTokenForm(), ['subject' => '{title} {id}']);

    (new m261104_050000_bare_notification_tokens())->safeUp();

    expect(bareTokenRow($id)['subject'])->toBe('{submission:title} {submission:id}');
});

it('changes nothing when run again', function (): void {
    $id = bareTokenNotification(bareTokenForm(), ['to' => '{email}', 'subject' => 'Hello {fullName}']);
    $migration = new m261104_050000_bare_notification_tokens();

    $migration->safeUp();
    $first = bareTokenRow($id);
    $migration->safeUp();

    expect(bareTokenRow($id))->toBe($first);
});
