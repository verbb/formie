<?php
// Forms for tests/browser/client-parity.spec.ts: every field type and display variant, filled in the same way in the
// server-rendered form and each client adapter, so the saved submissions can be compared.
use verbb\formie\fields;
use verbb\formie\models\RichText;

\Tests\Support\UploadTestHelper::ensureUploadVolume();

$options = [['label' => 'Alpha', 'value' => 'alpha'], ['label' => 'Beta', 'value' => 'beta'], ['label' => 'Gamma', 'value' => 'gamma']];
$recipients = [['label' => 'Sales', 'value' => 'sales@example.test'], ['label' => 'Support', 'value' => 'support@example.test']];
$text = fn(string $handle, string $label) => ['type' => fields\SingleLineText::class, 'handle' => $handle, 'label' => $label];

$factory = \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Parity fields', 'handle' => 'parityFields'])
    ->settings(['disableCaptchas' => true, 'submitMethod' => 'ajax'])
    ->singleLineTextField('pSingleLine', ['label' => 'P single line'])
    ->multiLineTextField('pMultiLine', ['label' => 'P multi line'])
    ->multiLineTextField('pRichText', ['label' => 'P rich text', 'useRichText' => true])
    ->emailField('pEmail', ['label' => 'P email'])
    ->numberField('pNumber', ['label' => 'P number'])
    ->phoneField('pPhoneCountry', ['label' => 'P phone country', 'countryEnabled' => true, 'countryDefaultValue' => 'AU'])
    ->phoneField('pPhonePlain', ['label' => 'P phone plain', 'countryEnabled' => false])
    ->passwordField('pPassword', ['label' => 'P password'])
    ->hiddenField('pHidden', ['label' => 'P hidden', 'defaultValue' => 'hidden-value'])
    ->dropdownField('pDropdown', ['label' => 'P dropdown', 'options' => $options])
    ->dropdownField('pDropdownMulti', ['label' => 'P dropdown multi', 'multi' => true, 'options' => $options])
    ->dropdownField('pDropdownSearch', ['label' => 'P dropdown searchable', 'useSearchable' => true, 'options' => $options])
    ->dropdownField('pDropdownSearchMulti', ['label' => 'P dropdown searchable multi', 'useSearchable' => true, 'multi' => true, 'options' => $options])
    ->entriesField('pEntriesSearch', ['label' => 'P entries searchable', 'useSearchable' => true])
    ->radioField('pRadio', ['label' => 'P radio', 'options' => $options])
    ->checkboxesField('pCheckboxes', ['label' => 'P checkboxes', 'options' => $options])
    ->checkboxesField('pCheckboxesToggle', ['label' => 'P checkboxes toggle', 'toggleCheckbox' => 'top', 'toggleCheckboxLabel' => 'Select all', 'options' => $options])
    ->agreeField('pAgree', ['label' => 'P agree', 'description' => RichText::from('I agree')])
    ->dateField('pDateCalendar', ['label' => 'P date calendar', 'displayType' => 'calendar'])
    ->dateField('pDatePicker', ['label' => 'P date picker', 'displayType' => 'datePicker'])
    ->dateField('pDateDropdowns', ['label' => 'P date dropdowns', 'displayType' => 'dropdowns'])
    ->dateField('pDateInputs', ['label' => 'P date inputs', 'displayType' => 'inputs'])
    ->nameField('pNameSingle', ['label' => 'P name single'])
    ->nameField('pNameMulti', ['label' => 'P name multi', 'useMultipleFields' => true, 'rows' => (new fields\Name(['useMultipleFields' => true]))->getSubFields()])
    ->addressField('pAddress', ['label' => 'P address', 'rows' => (new fields\Address())->getSubFields()])
    ->groupField('pGroup', ['label' => 'P group', 'rows' => [['fields' => [$text('pGroupText', 'P group text')]]]])
    ->repeaterField('pRepeater', ['label' => 'P repeater', 'minRows' => 1, 'rows' => [['fields' => [$text('pRepeaterText', 'P repeater text')]]]])
    ->repeaterField('pRepeaterUpload', ['label' => 'P repeater upload', 'minRows' => 2, 'rows' => [['fields' => [
        ['type' => fields\FileUpload::class, 'handle' => 'pRowFile', 'label' => 'P row file', 'restrictFiles' => false, 'displayType' => 'uploadManager'],
    ]]]])
    ->tableField('pTable', ['label' => 'P table', 'columns' => [
        'col1' => ['heading' => 'Col text', 'handle' => 'colText', 'type' => 'singleline'],
        'col2' => ['heading' => 'Col check', 'handle' => 'colCheck', 'type' => 'checkbox'],
    ], 'defaults' => [['colText' => 'row one']]])
    ->signatureField('pSignature', ['label' => 'P signature'])
    ->fileUploadField('pFileInput', ['label' => 'P file input', 'restrictFiles' => false, 'displayType' => 'fileInput'])
    ->fileUploadField('pFileManager', ['label' => 'P file manager', 'restrictFiles' => false, 'displayType' => 'uploadManager'])
    ->numberField('pCalcSource', ['label' => 'P calc source'])
    ->calculationsField('pCalculations', ['label' => 'P calculations'])
    ->recipientsField('pRecipientsDropdown', ['label' => 'P recipients dropdown', 'displayType' => 'dropdown', 'options' => $recipients])
    ->recipientsField('pRecipientsSearch', ['label' => 'P recipients searchable', 'displayType' => 'dropdown', 'useSearchable' => true, 'options' => $recipients])
    ->recipientsField('pRecipientsRadio', ['label' => 'P recipients radio', 'displayType' => 'radio', 'options' => $recipients])
    ->recipientsField('pRecipientsCheckboxes', ['label' => 'P recipients checkboxes', 'displayType' => 'checkboxes', 'options' => $recipients])
    ->recipientsField('pRecipientsHidden', ['label' => 'P recipients hidden', 'displayType' => 'hidden', 'options' => [['label' => 'Sales', 'value' => 'sales@example.test', 'isDefault' => true]]])
    ->entriesField('pEntries', ['label' => 'P entries'])
    ->entriesField('pEntriesCheckboxes', ['label' => 'P entries checkboxes', 'displayType' => 'checkboxes'])
    ->categoriesField('pCategories', ['label' => 'P categories'])
    ->tagsField('pTags', ['label' => 'P tags'])
    ->usersField('pUsers', ['label' => 'P users'])
    ->headingField('pHeading', ['label' => 'P heading'])
    ->htmlField('pHtml', ['label' => 'P html', 'htmlContent' => '<p>P html content</p>'])
    ->sectionField('pSection', ['label' => 'P section'])
    ->summaryField('pSummary', ['label' => 'P summary']);

foreach (['likert', 'rank', 'rating', 'dropdown', 'radio', 'checkboxes', 'singleLineText', 'multiLineText'] as $display) {
    $factory->addField(fields\Survey::class, 'pSurvey' . ucfirst($display), [
        'label' => 'P survey ' . $display, 'displayType' => $display, 'options' => $options,
    ]);
}

$factory->addField(fields\Survey::class, 'pSurveyLikertRows', [
    'label' => 'P survey likert rows', 'displayType' => 'likert', 'options' => $options, 'multipleRowsEnabled' => true,
    'likertRows' => [['label' => 'Row one', 'value' => 'rowOne'], ['label' => 'Row two', 'value' => 'rowTwo']],
]);

foreach (['dropdown', 'radio', 'checkboxes'] as $display) {
    $factory->addField(fields\Quiz::class, 'pQuiz' . ucfirst($display), [
        'label' => 'P quiz ' . $display, 'displayType' => $display,
        'options' => [['label' => 'Right', 'value' => 'right', 'isCorrect' => true], ['label' => 'Wrong', 'value' => 'wrong']],
    ]);
}

$parity = $factory->create();
$parity->getFieldByHandle('pCalculations')->formula = RichText::from('{field:' . $parity->getFieldByHandle('pCalcSource')->reference . '} * 2');

if (!Craft::$app->getElements()->saveElement($parity)) {
    throw new RuntimeException('Cannot save parity fields fixture: ' . json_encode($parity->getErrors()));
}

// Payment fields for tests/browser/client-payments.spec.ts, using the Stripe integration from browser-fixture.php.
\verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Parity Stripe', 'handle' => 'parityPayStripe'])
    ->settings(['disableCaptchas' => true, 'submitMethod' => 'ajax'])
    ->singleLineTextField('payerName', ['label' => 'Payer name'])
    ->paymentField('pay', ['label' => 'Pay', 'paymentIntegration' => 'browserStripe', 'paymentIntegrationType' => \verbb\formie\integrations\payments\Stripe::class, 'providerSettings' => ['browserStripe' => [
        'amountType' => 'fixed', 'amountFixed' => '10', 'currencyType' => 'fixed', 'currencyFixed' => 'USD',
    ]]])
    ->create();

// Every other payment provider, with fake credentials. The browser test fakes their SDKs and the server's decisions.
$paymentProviders = [
    'PayPal' => ['clientId' => 'parity-client', 'clientSecret' => 'parity-secret'],
    'Square' => ['applicationId' => 'sandbox-sq0idb-parity', 'accessToken' => 'parity-token', 'locationId' => 'parity-location'],
    'PayWay' => ['publishableKey' => 'T11111_PUB_parity', 'secretKey' => 'T11111_SEC_parity', 'merchantId' => 'TEST'],
    'Moneris' => ['storeId' => 'parity-store', 'apiToken' => 'parity-token', 'profileId' => 'parity-profile'],
    'Opayo' => ['vendorName' => 'parity', 'integrationKey' => 'parity-key', 'integrationPassword' => 'parity-password'],
    'Eway' => ['apiKey' => 'parity-key', 'apiPassword' => 'parity-password', 'clientSideEncryptionKey' => 'parity-cse'],
    'Bpoint' => ['username' => 'parity', 'password' => 'parity-password', 'merchantNumber' => '5353109000000000'],
    'GoCardless' => ['accessToken' => 'parity-token'],
    'Mollie' => ['apiKey' => 'test_parity'],
    'Paddle' => ['apiKey' => 'parity-key', 'clientSideToken' => 'test_parity'],
];

foreach ($paymentProviders as $class => $settings) {
    $integrationClass = 'verbb\\formie\\integrations\\payments\\' . $class;
    $handle = 'parity' . $class;
    $integration = new $integrationClass(array_merge(['name' => 'Parity ' . $class, 'handle' => $handle, 'enabled' => true], $settings));

    if (!\verbb\formie\Formie::$plugin->getIntegrations()->saveIntegration($integration, false)) {
        throw new RuntimeException('Cannot save parity ' . $class . ' integration.');
    }

    \verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Parity ' . $class, 'handle' => 'parityPay' . $class])
        ->settings(['disableCaptchas' => true, 'submitMethod' => 'ajax'])
        ->singleLineTextField('payerName', ['label' => 'Payer name'])
        ->paymentField('pay', ['label' => 'Pay', 'paymentIntegration' => $handle, 'paymentIntegrationType' => $integrationClass, 'providerSettings' => [$handle => [
            'amountType' => 'fixed', 'amountFixed' => '10', 'currencyType' => 'fixed', 'currencyFixed' => 'AUD',
        ]]])
        ->create();
}

// A Summary field after one page of answers, for the summary parity test.
\verbb\formie\Formie::$plugin->getFactories()->form(['title' => 'Parity summary', 'handle' => 'paritySummary'])
    ->settings(['disableCaptchas' => true, 'submitMethod' => 'ajax'])
    ->multiPage(2)->onPage(1)
    ->singleLineTextField('visitorName', ['label' => 'Visitor name'])
    ->emailField('visitorEmail', ['label' => 'Visitor email'])
    ->dropdownField('topic', ['label' => 'Topic', 'options' => [['label' => 'Sales', 'value' => 'sales'], ['label' => 'Support', 'value' => 'support']]])
    ->onPage(2)
    ->summaryField('review', ['label' => 'Review'])
    ->create();
