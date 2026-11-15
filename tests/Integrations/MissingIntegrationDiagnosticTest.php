<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

function missingIntegrationResetCaches(): void
{
    (new ReflectionMethod(Formie::$plugin->getIntegrations(), '_resetIntegrationCaches'))->invoke(Formie::$plugin->getIntegrations());
}

function missingIntegrationSubmission(string $handle, bool $enabledOnForm): Submission
{
    Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_INTEGRATIONS, [
        'name' => 'Autopilot Account',
        'handle' => $handle,
        'type' => 'verbb\\formie\\integrations\\emailmarketing\\Autopilot',
        'sortOrder' => 99,
        'enabled' => '1',
        'settings' => Json::encode(['apiKey' => 'private-api-key']),
        'uid' => StringHelper::UUID(),
    ])->execute();
    missingIntegrationResetCaches();

    $form = formie()->form(['title' => 'Missing provider'])->singleLineTextField('answer')->create();
    $stored = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
    $stored['integrations'][$handle] = ['enabled' => $enabledOnForm ? '1' : '', 'listId' => 'list'];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($stored)], ['id' => $form->id])->execute();
    $saved = formie()->submission($form)->with(['answer' => 'Private answer'])->save();

    return Submission::find()->id($saved->id)->status(null)->one();
}

function missingIntegrationLogs(callable $callback): string
{
    $offset = count(Craft::getLogger()->messages);
    $callback();

    return implode("\n", array_filter(array_column(array_slice(Craft::getLogger()->messages, $offset), 0), 'is_string'));
}

beforeEach(function(): void {
    $this->missingTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function(): void {
    $this->missingTransaction->rollBack();
    missingIntegrationResetCaches();
});

it('logs that a form sent nothing to a provider Formie no longer has', function(): void {
    $submission = missingIntegrationSubmission('missingEnabled', true);

    $logs = missingIntegrationLogs(fn() => Formie::$plugin->getIntegrations()->triggerIntegrations($submission));

    expect($logs)->toContain('Autopilot Account')
        ->and($logs)->toContain('verbb\\formie\\integrations\\emailmarketing\\Autopilot')
        ->and($logs)->toContain('Nothing was sent')
        ->and($logs)->toContain($submission->getForm()->handle)
        ->and($logs)->not->toContain('private-api-key')
        ->and($logs)->not->toContain('Private answer');
});

it('stays quiet when the form has that provider turned off', function(): void {
    $submission = missingIntegrationSubmission('missingDisabled', false);

    $logs = missingIntegrationLogs(fn() => Formie::$plugin->getIntegrations()->triggerIntegrations($submission));

    expect($logs)->not->toContain('Autopilot Account');
});
