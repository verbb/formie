<?php

declare(strict_types=1);

use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\Db;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\fields\Name;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260913_000000_notification_field_references;
use verbb\formie\migrations\m260913_000001_integration_field_references;
use verbb\formie\migrations\m260928_010000_reference_contract;
use verbb\formie\migrations\m261121_000000_non_primary_form_references;
use verbb\formie\models\Notification;

/**
 * Formie 3 forms weren't localized, so a form can have rows only on a site that isn't primary any more.
 * Upgrading sets its source site to that site and, later, adds availability rows on every site.
 * Returns the form, its notification and the references the upgrade should write.
 */
function nonPrimaryReferenceForm(bool $availabilitySynced = false): array
{
    $form = formie()->form()
        ->emailField('email')
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->create();

    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
    $settings['integrations'] = ['fixture' => ['enabled' => false, 'fieldMapping' => [
        'EMAIL' => ['kind' => 'reference', 'value' => '{field:email}'],
        'FIRST' => ['kind' => 'reference', 'value' => '{field:person.firstName}'],
    ]]];
    Db::update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id]);

    $notification = new Notification(['formId' => $form->id, 'name' => 'Staff', 'handle' => 'staff',
        'subject' => 'Hello {field:email}', 'to' => 'migration@example.test', 'enabled' => false]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();

    $primarySiteId = (int)Craft::$app->getSites()->getPrimarySite()->id;
    $formerPrimarySiteId = (int)array_values(array_diff(Craft::$app->getSites()->getAllSiteIds(), [$primarySiteId]))[0];
    Db::update(Table::FORMIE_FORMS, ['sourceSiteId' => $formerPrimarySiteId], ['id' => $form->id]);

    if (!$availabilitySynced) {
        Db::delete(CraftTable::ELEMENTS_SITES, ['elementId' => $form->id, 'siteId' => $primarySiteId]);

        expect(Form::find()->id($form->id)->status(null)->one())->toBeNull()
            ->and(Form::find()->id($form->id)->site('*')->unique()->status(null)->one())->not->toBeNull();
    }

    return [$form, $notification, [
        'email' => '{field:' . $form->getFieldByHandle('email')->reference . '}',
        'firstName' => '{field:' . $form->getFieldByHandle('person')->reference . ':firstName}',
    ]];
}

function nonPrimaryMapping(Form $form): array
{
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());

    return array_map(fn(array $slot) => $slot['value'], $settings['integrations']['fixture']['fieldMapping']);
}

function nonPrimarySubject(Notification $notification): string
{
    return (string)(new Query())->select('subject')->from(Table::FORMIE_NOTIFICATIONS)->where(['id' => $notification->id])->scalar();
}

beforeEach(function(): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }
});

it('converts Formie 3 field handles on forms without a primary-site row', function(): void {
    [$form, $notification, $expected] = nonPrimaryReferenceForm();

    expect((new m260913_000000_notification_field_references())->safeUp())->toBeTrue()
        ->and((new m260913_000001_integration_field_references())->safeUp())->toBeTrue()
        ->and((new m260928_010000_reference_contract())->safeUp())->toBeTrue();

    expect(nonPrimarySubject($notification))->toBe('Hello ' . $expected['email'])
        ->and(nonPrimaryMapping($form))->toBe(['EMAIL' => $expected['email'], 'FIRST' => $expected['firstName']]);
});

it('converts forms an earlier upgrade skipped and leaves other forms alone', function(): void {
    [$form, $notification, $expected] = nonPrimaryReferenceForm(availabilitySynced: true);

    $other = formie()->form()->emailField('email')->create();
    $otherSettings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $other->id])->scalar());
    $otherSettings['integrations'] = ['fixture' => ['enabled' => false, 'fieldMapping' => ['EMAIL' => ['kind' => 'reference', 'value' => '{field:email}']]]];
    Db::update(Table::FORMIE_FORMS, ['settings' => Json::encode($otherSettings), 'sourceSiteId' => Craft::$app->getSites()->getPrimarySite()->id], ['id' => $other->id]);

    foreach ([1, 2] as $run) {
        expect((new m261121_000000_non_primary_form_references())->safeUp())->toBeTrue();

        expect(nonPrimarySubject($notification))->toBe('Hello ' . $expected['email'])
            ->and(nonPrimaryMapping($form))->toBe(['EMAIL' => $expected['email'], 'FIRST' => $expected['firstName']])
            ->and(nonPrimaryMapping($other))->toBe(['EMAIL' => '{field:email}']);
    }
});
