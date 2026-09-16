<?php
require __DIR__ . '/verify.php';
$form = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Browser contract', 'handle' => 'browserContract'])
    ->singleLineTextField('visitorName', ['label' => 'Visitor name', 'required' => true])
    ->emailField('visitorEmail', ['label' => 'Visitor email', 'required' => true])
    ->create();
$form->setNotifications([new \verbb\formie\models\Notification([
    'name' => 'Browser notification', 'handle' => 'browserNotification',
    'subject' => 'Browser notification subject', 'to' => 'admin@example.test', 'enabled' => false,
])]);
if (!Craft::$app->getElements()->saveElement($form)) {
    throw new RuntimeException('Cannot save browser notification picker fixture.');
}
\Tests\Support\UploadTestHelper::ensureUploadVolume();
$journey = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Browser journey', 'handle' => 'browserJourney'])
    ->multiPage(2)->onPage(1)
    ->singleLineTextField('visitorName', ['label' => 'Visitor name', 'required' => true])
    ->onPage(2)->repeaterField('items', ['rows' => [['fields' => [[
        'type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'itemName', 'label' => 'Item name',
    ]]]]])->fileUploadField('attachment', ['label' => 'Attachment', 'restrictFiles' => false])->create();
$journey->getPages()[1]->getPageSettings()->showBackButton = true;
if (!Craft::$app->getElements()->saveElement($journey)) {
    throw new RuntimeException('Cannot save browser journey navigation settings.');
}
function createRenderedBrowserFixture(string $handle, string $method): \verbb\formie\elements\Form {
    $rendered = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Rendered contract', 'handle' => $handle])
        ->settings(['disableCaptchas' => true, 'submitMethod' => $method])
        ->singleLineTextField('visitorName', ['label' => 'Visitor name', 'required' => true])
        ->dropdownField('enquiry', ['label' => 'Enquiry', 'options' => [['label' => 'General', 'value' => 'general'], ['label' => 'Other', 'value' => 'other']]])
        ->singleLineTextField('details', ['label' => 'Details', 'enableConditions' => true, 'conditions' => [
            'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['field' => 'enquiry', 'condition' => '=', 'value' => 'other']],
        ]])
        ->numberField('quantity', ['label' => 'Quantity'])
        ->numberField('price', ['label' => 'Price'])
        ->calculationsField('total', ['label' => 'Total'])->create();
    $rendered->getFieldByHandle('total')->formula = \verbb\formie\models\RichText::from('{field:' . $rendered->getFieldByHandle('quantity')->reference . '} * {field:' . $rendered->getFieldByHandle('price')->reference . '}');
    if (!Craft::$app->getElements()->saveElement($rendered)) {
        throw new RuntimeException('Cannot save rendered browser fixture.');
    }
    return $rendered;
}
$rendered = createRenderedBrowserFixture('renderedContract', 'ajax');
$native = createRenderedBrowserFixture('renderedNativeContract', 'page-reload');
$widgetForm = \verbb\formie\Formie::$plugin->getFactories()->form([
    'title' => 'Widget <img src=x onerror="window.widgetInjection=true">',
    'handle' => 'widgetContract',
])->singleLineTextField('message')->create();
\verbb\formie\Formie::$plugin->getFactories()->submission($widgetForm)->save();
Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->username('admin')->one());
$widget = new \verbb\formie\widgets\RecentSubmissions([
    'title' => 'Browser widget contract',
    'displayType' => 'pie',
    'formIds' => [$widgetForm->id],
]);
if (!Craft::$app->getDashboard()->saveWidget($widget)) {
    throw new RuntimeException('Cannot save browser dashboard widget.');
}
$scopedForm = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Scoped role form', 'handle' => 'scopedRoleForm'])
    ->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
$otherForm = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Other role form', 'handle' => 'otherRoleForm'])
    ->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
$scopedUser = new \craft\elements\User(['username' => 'browserScopedEditor', 'email' => 'scoped-editor@example.test', 'newPassword' => 'testing-only-password']);
if (!Craft::$app->getElements()->saveElement($scopedUser)) {
    throw new RuntimeException('Cannot save browser scoped editor.');
}
Craft::$app->getUsers()->activateUser($scopedUser);
Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
if (!Craft::$app->getUserPermissions()->saveUserPermissions($scopedUser->id, [
    'accessCp', 'accessPlugin-formie', 'formie-accessForms', 'formie-accessSubmissions',
    'formie-manageForms:' . $scopedForm->uid,
    'formie-viewSubmissions:' . $scopedForm->uid,
    'formie-createSubmissions:' . $scopedForm->uid,
    ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
])) {
    throw new RuntimeException('Cannot save browser scoped permissions.');
}
Craft::$app->getProjectConfig()->saveModifiedConfigData();
file_put_contents(dirname(__DIR__, 2) . '/.cache/verbb-tests/browser-enabled.json', json_encode(['formId' => $form->id, 'journeyId' => $journey->id, 'renderedId' => $rendered->id, 'renderedNativeId' => $native->id]));
