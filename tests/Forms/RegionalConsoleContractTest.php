<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\elements\{Form, Submission, SentNotification};
use verbb\formie\console\controllers\{FormsController, SubmissionsController, SentNotificationsController};

it('exports and cleans up regional forms by handle from the primary console context', function (): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
    $group = new FormGroup(['name' => 'Console region', 'handle' => 'consoleRegion' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $siteId, 'sourceSiteId' => $siteId])->singleLineTextField('message')->create();
    $control = formie()->form()->singleLineTextField('message')->create();
    $submission = new Submission(['siteId' => $siteId, 'title' => 'Regional console']);
    $submission->setForm($form);
    $submission->setFieldValue('message', 'Keep until cleanup');
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    $controlSubmission = formie()->submission($control)->with(['message' => 'Keep control'])->save();
    $notice = new SentNotification(['title' => 'Regional notice', 'formId' => (string)$form->id, 'success' => true]);
    $controlNotice = new SentNotification(['title' => 'Control notice', 'formId' => (string)$control->id, 'success' => true]);
    expect(Craft::$app->getElements()->saveElement($notice))->toBeTrue();
    expect(Craft::$app->getElements()->saveElement($controlNotice))->toBeTrue();
    Formie::$plugin->getSettings()->defaultExportFolder = '@storage/runtime/regional-console-' . bin2hex(random_bytes(5));
    $folder = Formie::$plugin->getSettings()->getAbsoluteDefaultExportFolder();
    $path = $folder . '/formie-' . $form->handle . '.json';
    $controller = new class('forms', Formie::$plugin) extends FormsController {
        public function stdout($string): int { return strlen($string); }
        public function stderr($string): int { return strlen($string); }
    };
    try {
        expect($controller->actionExport($form->handle))->toBe(0);
        expect(is_file($path))->toBeTrue();
        expect(json_decode(file_get_contents($path), true)['handle'])->toBe($form->handle);
        $delete = new class('submissions', Formie::$plugin) extends SubmissionsController {
            public function stdout($string): int { return strlen($string); }
            public function stderr($string): int { return strlen($string); }
        };
        $delete->formHandle = $form->handle;
        $delete->spamOnly = false;
        $delete->incompleteOnly = false;
        expect($delete->actionDelete())->toBe(0);
        expect(Submission::find()->id($submission->id)->one())->toBeNull();
        expect(Submission::find()->id($controlSubmission->id)->one())->not->toBeNull();
        $notifications = new class('sent-notifications', Formie::$plugin) extends SentNotificationsController {
            public function stdout($string): int { return strlen($string); }
            public function stderr($string): int { return strlen($string); }
        };
        $notifications->formHandle = $form->handle;
        expect($notifications->actionDelete())->toBe(0);
        expect(SentNotification::find()->id($notice->id)->one())->toBeNull();
        expect(SentNotification::find()->id($controlNotice->id)->one())->not->toBeNull();
        $again = new SentNotification(['title' => 'Regional notice again', 'formId' => (string)$form->id, 'success' => true]);
        expect(Craft::$app->getElements()->saveElement($again))->toBeTrue();
        $notifications->formHandle = null;
        $notifications->all = true;
        expect($notifications->actionDelete())->toBe(0);
        expect(SentNotification::find()->id($again->id)->one())->toBeNull();
        expect(SentNotification::find()->id($controlNotice->id)->one())->toBeNull();
        $controller->formHandle = $form->handle;
        expect($controller->actionDelete())->toBe(0);
        expect(Form::find()->id($form->id)->site('*')->one())->toBeNull();
        expect(Form::find()->id($control->id)->one())->not->toBeNull();
    } finally {
        \craft\helpers\FileHelper::removeDirectory($folder);
    }
});
