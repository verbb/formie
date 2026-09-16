<?php

declare(strict_types=1);

use craft\helpers\Db;
use verbb\formie\Formie;
use verbb\formie\console\controllers\SubmissionsController;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

function deleteDateController(): SubmissionsController
{
    return new class('submissions', Formie::$plugin) extends SubmissionsController {
        public string $errors = '';
        public function stdout($string) { return strlen($string); }
        public function stderr($string) { $this->errors .= $string; return strlen($string); }
    };
}

it('rejects an invalid deletion date before changing any selected form submissions', function (string $bound, string $value): void {
    $forms = [formie()->form()->create(), formie()->form()->create()];
    $ids = [];
    foreach ($forms as $form) { $ids[] = formie()->submission($form)->save()->id; }
    $controller = deleteDateController();
    $controller->formId = implode(',', array_map(fn($form) => $form->id, $forms));
    $controller->{$bound} = $value;
    expect($controller->actionDelete())->not->toBe(0);
    expect($controller->errors)->toContain('--' . $bound);
    expect((int)Submission::find()->id($ids)->status(null)->count())->toBe(2);
    expect((int)Submission::find()->id($ids)->status(null)->trashed(true)->count())->toBe(0);
})->with(['before', 'after'])->with(['not-a-date', '', '0', '   ']);

it('applies valid inclusive after and exclusive before deletion bounds', function (): void {
    $form = formie()->form()->create();
    $ids = [];
    foreach (['2025-12-31 12:00:00', '2026-01-15 12:00:00', '2026-02-01 00:00:00'] as $date) {
        $submission = formie()->submission($form)->save();
        $ids[] = $submission->id;
        Db::update(Table::FORMIE_SUBMISSIONS, ['dateCreated' => $date], ['id' => $submission->id]);
    }
    $controller = deleteDateController();
    $controller->formId = (string)$form->id;
    $controller->after = '2026-01-01';
    $controller->before = '2026-02-01';
    expect($controller->actionDelete())->toBe(0);
    expect(Submission::find()->formId($form->id)->status(null)->orderBy(['elements.id' => SORT_ASC])->ids())->toBe([$ids[0], $ids[2]]);
    expect(Submission::find()->formId($form->id)->status(null)->trashed(true)->ids())->toBe([$ids[1]]);
});
