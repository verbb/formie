<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\fields\SingleLineText;
use yii\db\Expression;

class DecimalQueryContractField extends SingleLineText
{
    public static function dbType(): string { return 'decimal(12,2)'; }
}

class WholeDecimalQueryContractField extends SingleLineText
{
    public static function dbType(): string { return 'decimal(20,0)'; }
}

class PrecisionDecimalQueryContractField extends SingleLineText
{
    public static function dbType(): string { return 'decimal(20)'; }
}

it('preserves custom decimal precision when selecting and filtering field values', function (string $class, string $amount, string $expected): void {
    $field = new $class(['uid' => 'decimal-field', 'handle' => 'amount']);
    $row = (new Query())->select(['content' => new Expression(':content', [':content' => Json::encode([$field->uid => $amount])])]);
    $query = (new Query())->select(['amount' => new Expression($field->getValueSql())])->from(['formie_submissions' => $row]);
    expect((string)$query->scalar())->toBe($expected);
    $params = [];
    $condition = $class::queryCondition([$field], '>= ' . $amount, $params);
    expect((clone $query)->andWhere($condition, $params)->exists())->toBeTrue();
    $condition = $class::queryCondition([$field], '> ' . $amount, $params);
    expect((clone $query)->andWhere($condition, $params)->exists())->toBeFalse();
})->with([
    [DecimalQueryContractField::class, '12.50', '12.50'],
    [WholeDecimalQueryContractField::class, '12345678901', '12345678901'],
    [PrecisionDecimalQueryContractField::class, '12345678901', '12345678901'],
]);
