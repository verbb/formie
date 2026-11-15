<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\Notification;

function recipientsSummaryEmail(array $fieldConfig, mixed $value): array
{
    $form = formie()->form()->recipientsField('team', $fieldConfig)->create();
    $saved = formie()->submission($form)->with(['team' => $value])->save();

    return Formie::$plugin->getEmails()->renderEmail(new Notification([
        'name' => 'Summary',
        'handle' => 'summary',
        'to' => 'staff@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Enquiry',
        'content' => '{allFields}',
    ]), Submission::find()->id($saved->id)->status(null)->one());
}

it('renders a Recipients field in the email summary with strict Twig variables', function (string $displayType, mixed $value, array $expected, array $missing): void {
    $twig = Craft::$app->getView()->getTwig();
    $strict = $twig->isStrictVariables();
    $twig->enableStrictVariables();

    try {
        $result = recipientsSummaryEmail(['displayType' => $displayType, 'options' => [
            ['label' => 'Sales', 'value' => 'sales@example.test'],
            ['label' => 'Support', 'value' => 'support@example.test'],
        ]], $value);
    } finally {
        $strict ? $twig->enableStrictVariables() : $twig->disableStrictVariables();
    }

    expect($result)->not->toHaveKey('error');

    $text = $result['email']->getSymfonyEmail()->getTextBody();

    foreach ($expected as $string) {
        expect($text)->toContain($string);
    }

    foreach ($missing as $string) {
        expect($text)->not->toContain($string);
    }
})->with([
    'dropdown' => ['dropdown', 'support@example.test', ['support@example.test'], ['sales@example.test']],
    'radio' => ['radio', 'support@example.test', ['support@example.test'], ['sales@example.test']],
    'checkboxes' => ['checkboxes', ['support@example.test'], ['support@example.test'], ['sales@example.test']],
]);

it('renders an empty multi-select Dropdown inside a Group in the email summary', function (): void {
    $form = formie()->form()->groupField('details', ['rows' => [['fields' => [[
        'type' => Dropdown::class,
        'handle' => 'choices',
        'label' => 'Choices',
        'multi' => true,
        'options' => [['label' => 'One', 'value' => 'one']],
    ], [
        'type' => SingleLineText::class,
        'handle' => 'note',
        'label' => 'Note',
    ]]]]])->create();
    $saved = formie()->submission($form)->with(['details' => ['note' => 'S Group', 'choices' => null]])->save();

    $result = Formie::$plugin->getEmails()->renderEmail(new Notification([
        'name' => 'Summary',
        'handle' => 'summary',
        'to' => 'staff@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Enquiry',
        'content' => '{allFields}',
    ]), Submission::find()->id($saved->id)->status(null)->one());

    expect($result)->not->toHaveKey('error')
        ->and($result['email']->getSymfonyEmail()->getTextBody())->toContain('S Group');
});
