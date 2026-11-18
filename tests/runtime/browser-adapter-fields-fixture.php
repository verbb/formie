<?php
// Forms for tests/browser/adapter-fields.spec.ts: field types, choices and form-level feedback in the client adapters.
use verbb\formie\fields\Name;
use verbb\formie\fields\Address;
use verbb\formie\fields\Radio;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\RichText;
use verbb\formie\positions\Hidden as HiddenPosition;

\Tests\Support\UploadTestHelper::ensureUploadVolume();

$colours = [['label' => 'Red', 'value' => 'red'], ['label' => 'Green', 'value' => 'green'], ['label' => 'Blue', 'value' => 'blue']];
$defaultColours = [['label' => 'Red', 'value' => 'red', 'isDefault' => true], ['label' => 'Green', 'value' => 'green'], ['label' => 'Blue', 'value' => 'blue', 'isDefault' => true]];

$fields = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Adapter fields', 'handle' => 'adapterFields'])
    ->settings(['disableCaptchas' => true, 'submitMethod' => 'ajax'])
    ->singleLineTextField('visitorName', ['label' => 'Visitor name', 'required' => true])
    ->emailField('visitorEmail', ['label' => 'Visitor email'])
    ->numberField('quantity', ['label' => 'Quantity'])
    ->calculationsField('total', ['label' => 'Total'])
    ->dropdownField('colour', ['label' => 'Colour', 'options' => $colours])
    ->dropdownField('colours', ['label' => 'Colours', 'multi' => true, 'options' => $defaultColours])
    ->radioField('size', ['label' => 'Size', 'options' => [['label' => 'Small', 'value' => 'small'], ['label' => 'Large', 'value' => 'large']]])
    ->nameField('fullName', ['label' => 'Full name', 'useMultipleFields' => true, 'rows' => (new Name(['useMultipleFields' => true]))->getSubFields()])
    ->addressField('homeAddress', ['label' => 'Home address', 'rows' => (new Address())->getSubFields()])
    ->dateField('birthday', ['label' => 'Birthday', 'displayType' => 'dropdowns'])
    ->groupField('details', ['label' => 'Details', 'rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'company', 'label' => 'Company']]]]])
    ->tableField('schedule', ['label' => 'Schedule', 'columns' => [
        'task' => ['heading' => 'Task', 'handle' => 'task', 'type' => 'singleline'],
        'done' => ['heading' => 'Done', 'handle' => 'done', 'type' => 'checkbox'],
        'priority' => ['heading' => 'Priority', 'handle' => 'priority', 'type' => 'select', 'options' => [['label' => 'Low', 'value' => 'low'], ['label' => 'High', 'value' => 'high']]],
    ], 'defaults' => [['task' => 'Plan', 'done' => false]]])
    ->recipientsField('department', ['label' => 'Department', 'displayType' => 'dropdown', 'options' => [
        ['label' => 'Sales', 'value' => 'sales@example.test'], ['label' => 'Support', 'value' => 'support@example.test'],
    ]])
    ->entriesField('relatedEntry', ['label' => 'Related entry'])
    ->entriesField('featuredEntry', ['label' => 'Featured entry', 'displayType' => 'radio'])
    ->sectionField('divider', ['label' => 'Divider'])
    ->repeaterField('people', ['label' => 'People', 'minRows' => 2, 'rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'personName', 'label' => 'Person name'],
        ['type' => Radio::class, 'handle' => 'personRole', 'label' => 'Role', 'options' => [['label' => 'Lead', 'value' => 'lead'], ['label' => 'Member', 'value' => 'member']]],
        ['type' => SingleLineText::class, 'handle' => 'personSecret', 'label' => 'Person secret', 'visibility' => 'hidden'],
    ]]]])
    ->signatureField('signature', ['label' => 'Signature'])
    ->fileUploadField('attachment', ['label' => 'Attachment', 'restrictFiles' => false])
    ->singleLineTextField('nickname', ['label' => 'Nickname', 'labelPosition' => HiddenPosition::class])
    ->create();
$fields->getFieldByHandle('total')->formula = RichText::from('{field:' . $fields->getFieldByHandle('quantity')->reference . '} * 2');

if (!Craft::$app->getElements()->saveElement($fields)) {
    throw new RuntimeException('Cannot save adapter fields fixture.');
}

\verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Adapter required repeater', 'handle' => 'adapterRequiredRepeater'])
    ->settings(['disableCaptchas' => true, 'submitMethod' => 'ajax', 'displayCurrentPageTitle' => true])
    ->repeaterField('items', ['label' => 'Items', 'required' => true, 'minRows' => 0, 'rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'itemName', 'label' => 'Item name'],
    ]]]])
    ->create();

$pages = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Adapter pages', 'handle' => 'adapterPages'])
    ->settings(['disableCaptchas' => true, 'submitMethod' => 'ajax', 'completionBehavior' => 'message', 'hideFormAfterSubmit' => true, 'displayCurrentPageTitle' => true, 'displayPageProgress' => true, 'progressPosition' => 'start'])
    ->multiPage(2)->onPage(1)
    ->singleLineTextField('stepOne', ['label' => 'Step one', 'required' => true])
    ->onPage(2)
    ->singleLineTextField('stepTwo', ['label' => 'Step two'])
    ->create();
$pages->getPages()[1]->getPageSettings()->showBackButton = true;

if (!Craft::$app->getElements()->saveElement($pages)) {
    throw new RuntimeException('Cannot save adapter pages fixture.');
}

$nested = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Adapter nested calculations', 'handle' => 'adapterNestedCalculations'])
    ->settings(['disableCaptchas' => true, 'submitMethod' => 'ajax'])
    ->numberField('rate', ['label' => 'Rate'])
    ->groupField('details', ['label' => 'Details', 'rows' => [['fields' => [
        ['type' => \verbb\formie\fields\Number::class, 'handle' => 'hours', 'label' => 'Hours'],
        ['type' => \verbb\formie\fields\Calculations::class, 'handle' => 'groupTotal', 'label' => 'Group total'],
    ]]]])
    ->repeaterField('items', ['label' => 'Items', 'minRows' => 2, 'rows' => [['fields' => [
        ['type' => \verbb\formie\fields\Number::class, 'handle' => 'qty', 'label' => 'Qty'],
        ['type' => \verbb\formie\fields\Calculations::class, 'handle' => 'rowTotal', 'label' => 'Row total'],
    ]]]])
    ->create();
$rate = $nested->getFieldByHandle('rate')->reference;

foreach (['details' => 'hours', 'items' => 'qty'] as $parentHandle => $sourceHandle) {
    $children = $nested->getFieldByHandle($parentHandle)->getFields();
    $source = current(array_filter($children, fn($child) => $child->handle === $sourceHandle));

    foreach ($children as $child) {
        if ($child instanceof \verbb\formie\fields\Calculations) {
            $child->formula = RichText::from('{field:' . $rate . '} * {field:' . $source->reference . '}');
        }
    }
}

if (!Craft::$app->getElements()->saveElement($nested)) {
    throw new RuntimeException('Cannot save adapter nested calculations fixture.');
}
