<?php

use verbb\formie\Formie;
use verbb\formie\fields\{Entries, SingleLineText};
use verbb\formie\services\Fields;
use verbb\formie\options\ElementOptionSourceHelper;
use verbb\formie\conditions\{ConditionCompiler, ConditionRowEvaluator, ConditionRule, ConditionSet};
use yii\base\Event;

class DiscoveredEntriesField extends Entries
{
    protected static function defineOptionSource(): ?array { return ['handle' => 'discovered-entries', 'label' => 'Discovered entries']; }
}
class NumericConditionTextField extends SingleLineText
{
    public function getConditionValueType(): string { return 'number'; }
}

it('includes registered element fields when discovering option sources', function (): void {
    $fields = Formie::$plugin->getFields();
    $handler = function ($event): void { $event->fields[] = DiscoveredEntriesField::class; };
    Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, $handler);
    $fields->resetFieldRegistryCache();
    try {
        expect(ElementOptionSourceHelper::getProviderFieldClass('discovered-entries'))->toBe(DiscoveredEntriesField::class)
            ->and(ElementOptionSourceHelper::getProviderForFieldClass(DiscoveredEntriesField::class))->toBe('discovered-entries');
    } finally { Event::off(Fields::class, Fields::EVENT_REGISTER_FIELDS, $handler); $fields->resetFieldRegistryCache(); }
});

it('uses the field declared condition type in both compiled and evaluated conditions', function (): void {
    $fields = Formie::$plugin->getFields();
    $handler = function ($event): void { $event->fields[] = NumericConditionTextField::class; };
    Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, $handler);
    $fields->resetFieldRegistryCache();
    try {
        $form = formie()->form()->addField(NumericConditionTextField::class, 'amount')->create();
        $submission = formie()->submission($form)->with(['amount' => '10'])->save();
        $rule = new ConditionRule('amount', '>', '2');
        $set = ConditionSet::fromArray(['conditions' => [['field' => 'amount', 'condition' => '>', 'value' => '2']]]);
        expect((new ConditionRowEvaluator())->evaluate($rule, $submission)->matches())->toBeTrue();
        $compiled = (new ConditionCompiler())->compile($set, $form);
        expect($compiled['rules'][0]['valueType'])->toBe('number');
    } finally { Event::off(Fields::class, Fields::EVENT_REGISTER_FIELDS, $handler); $fields->resetFieldRegistryCache(); }
});
