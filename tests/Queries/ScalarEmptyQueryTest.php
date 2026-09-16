<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\{Number, SingleLineText};
use verbb\formie\query\FieldValueQueryHelper;

it('matches persisted null scalar answers as empty without treating zero or literal null text as empty', function (string $fieldClass): void {
    $form = formie()->form()->addField($fieldClass, 'answer')->create();
    $ids = [];
    $values = ['null' => null, 'blank' => '', 'zero' => '0', 'filled' => '12'];
    if ($fieldClass === SingleLineText::class) { $values['nullText'] = 'null'; }
    foreach ($values as $name => $value) { $ids[$name] = formie()->submission($form)->with(['answer' => $value])->save()->id; }
    $query = fn() => Submission::find()->formId($form->id)->orderBy(['elements.id' => SORT_ASC]);
    expect($query()->answer(':empty:')->ids())->toBe([$ids['null'], $ids['blank']]);
    expect($query()->answer(':notempty:')->ids())->toBe(array_values(array_diff($ids, [$ids['null'], $ids['blank']])));
    expect($query()->answer('0')->ids())->toBe([$ids['zero']]);
    if (isset($ids['nullText'])) { expect($query()->answer('null')->ids())->toBe([$ids['nullText']]); }
})->with([Number::class, SingleLineText::class]);

it('preserves null values before casting scalar and keyed query projections', function (string|array $type, ?string $key): void {
    $uid = 'nullable-field';
    $content = [$uid => $key === null ? null : [$key => null]];
    $contentSql = Craft::$app->getDb()->getIsPgsql() ? 'CAST(:content AS JSONB)' : ':content';
    $source = (new \craft\db\Query())->select(['content' => new \yii\db\Expression($contentSql, [':content' => \craft\helpers\Json::encode($content)])]);
    $sql = FieldValueQueryHelper::buildValueSql(SingleLineText::class, $uid, $type, $key);
    $query = (new \craft\db\Query())->select(['answer' => new \yii\db\Expression($sql)])->from(['formie_submissions' => $source]);
    expect($query->one())->toBe(['answer' => null]);
})->with([
    ['string', null],
    ['decimal(12,2)', null],
    ['boolean', null],
    [['value' => 'string'], 'value'],
]);
