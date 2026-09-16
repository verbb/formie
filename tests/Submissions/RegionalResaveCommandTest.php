<?php

declare(strict_types=1);

use craft\console\controllers\ResaveController;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\elements\Submission;

it('dispatches regional forms during all-form submission resaves and returns the host result', function (int $result): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
    $group = new FormGroup(['name' => 'Resave region', 'handle' => 'resaveRegion' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $siteId, 'sourceSiteId' => $siteId])->singleLineTextField('message')->create();
    $controller = new class('resave', Craft::$app) extends ResaveController {
        public ?string $formId = null;
        public bool $updateTitle = true;
        public array $scopes = [];
        public int $result = 0;
        public function resaveElements(string $elementType, array $criteria = []): int {
            $this->scopes[] = [$elementType, $criteria];
            return $this->result;
        }
    };
    $controller->result = $result;
    $original = Craft::$app->controller;
    Craft::$app->controller = $controller;
    try {
        $exit = $controller->runAction('formie-submissions');
        if ($result === 0) {
            expect(array_column(array_column($controller->scopes, 1), 'formId'))->toContain($form->id);
        }
        expect($exit)->toBe($result);
    } finally { Craft::$app->controller = $original; }
})->with(['success' => 0, 'host failure' => 65]);

it('resaves an explicitly selected regional submission through Crafts real console service', function (): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
    $group = new FormGroup(['name' => 'Real resave region', 'handle' => 'realResaveRegion' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $siteId, 'sourceSiteId' => $siteId])
        ->singleLineTextField('message')->create();
    $form->getSettings()->submissionTitleFormat = 'Updated {field:' . $form->getFieldByHandle('message')->reference . '}';
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $submission = new Submission(['siteId' => $siteId, 'title' => 'Before resave']);
    $submission->setForm($form);
    $submission->setFieldValue('message', 'regional value');
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    $controller = new class('resave', Craft::$app) extends ResaveController {
        public ?string $formId = null;
        public bool $updateTitle = true;
        public function stdout($string): int { return strlen($string); }
        public function stderr($string): int { return strlen($string); }
    };
    $controller->formId = (string)$form->id;
    $controller->interactive = false;
    $original = Craft::$app->controller;
    Craft::$app->controller = $controller;
    try {
        expect($controller->runAction('formie-submissions'))->toBe(0);
        expect(Submission::find()->id($submission->id)->one()?->title)->toBe('Updated regional value');
    } finally { Craft::$app->controller = $original; }
});
