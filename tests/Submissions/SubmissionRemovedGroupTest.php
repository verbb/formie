<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\{Db, Json};
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\elements\{Form, Submission};
use verbb\formie\Formie;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\UpdateSubmissionContent;

it('preserves a moved child when the last group is removed in the same builder save', function (mixed $value): void {
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($value): void {
        $request->setIsCpRequest(true);
        $request->getHeaders()->set('Accept', 'application/json');
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $transaction = Craft::$app->getDb()->beginTransaction();
        $queue = Craft::$app->getQueue();
        $jobs = [];
        $capture = static function (\yii\queue\PushEvent $event) use (&$jobs): void {
            if ($event->job instanceof UpdateSubmissionContent) {
                $jobs[] = $event->job;
                // Capture the real enqueue decision without running unrelated queue jobs.
                $event->handled = true;
            }
        };
        $queue->on(\yii\queue\Queue::EVENT_BEFORE_PUSH, $capture);
        try {
            $form = formie()->form(['title' => 'Move final group ' . uniqid()])->groupField('oldGroup', ['rows' => [['fields' => [
                ['type' => SingleLineText::class, 'handle' => 'answer', 'label' => 'Answer'],
            ]]]])->create();
            $group = $form->getFieldByHandle('oldGroup');
            $child = $group->getFieldByHandle('answer');
            $submission = formie()->submission($form)->save();
            Db::update(Table::FORMIE_SUBMISSIONS, ['content' => [$group->uid => [$child->uid => $value]]], ['id' => $submission->id]);
            $pages = $form->getFormLayout()->getFormBuilderConfig();
            $childConfig = $pages[0]['rows'][0]['fields'][0]['rows'][0]['fields'][0];
            // This is the builder payload after moving its child out and deleting the empty Group.
            $pages[0]['rows'][0]['fields'] = [$childConfig];
            $jobs = [];
            $request->setBodyParams(['id' => $form->id, 'siteId' => $form->siteId, 'title' => $form->title,
                'handle' => $form->handle, 'pages' => $pages, 'notifications' => []]);
            $response = (new FormsController('forms', Formie::$plugin))->actionSave();
            expect($response->data['errors'] ?? [])->toBe([]);
            Formie::$plugin->getForms()->invalidateFormCaches();
            Formie::$plugin->getFields()->resetFieldRegistryCache();
            $reloadedForm = Form::find()->withoutCpIndexScope()->id($form->id)->siteId($form->siteId)->status(null)->one();
            expect($reloadedForm->getFieldByHandle('oldGroup'))->toBeNull();
            expect($reloadedForm->getFieldByHandle('answer')->id)->toBe($child->id);
            expect($reloadedForm->getFieldByHandle('answer')->uid)->toBe($child->uid);
            // Must enqueue despite the current layout no longer containing a Group.
            expect($jobs)->not->toBeEmpty();
            foreach ($jobs as $job) { $job->execute($queue); }
            $read = fn() => Json::decode((new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar());
            $content = $read();
            expect(array_key_exists($child->uid, $content))->toBeTrue();
            expect($content[$child->uid])->toBe($value);
            foreach ($jobs as $job) { $job->execute($queue); }
            expect($read())->toBe($content);
            if ($value === 'Retained') {
                expect(Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->isSpam(null)->one()->getFieldValue('answer'))->toBe('Retained');
            }
        } finally {
            $queue->off(\yii\queue\Queue::EVENT_BEFORE_PUSH, $capture);
            $transaction->rollBack();
            Formie::$plugin->getForms()->invalidateFormCaches();
            Formie::$plugin->getFields()->resetFieldRegistryCache();
        }
    }, ['method' => 'POST']);
})->with(['text' => ['Retained'], 'zero' => [0], 'false' => [false], 'empty text' => [''], 'null' => [null], 'empty array' => [[]]]);
