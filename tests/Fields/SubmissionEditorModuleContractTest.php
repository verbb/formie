<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\fields\{Address, Calculations, Date, Group, MultiLineText, Name, Phone, Repeater, Signature, SingleLineText, Survey};
use verbb\formie\fields\subfields\AddressAutoComplete;
use verbb\formie\integrations\addressproviders\{AddressFinder, Google, Loqate, PlaceKit};
use verbb\formie\models\{BrowserModule, BrowserModuleContext};

it('renders usable targets for every declared submission editor field module', function (string $placement, string $conditions): void {
    $addressRows = (new Address())->getSubFields();
    array_unshift($addressRows, ['fields' => [[
        'type' => AddressAutoComplete::class, 'handle' => 'autoComplete', 'label' => 'Find address', 'enabled' => true,
    ]]]);
    $configs = [
        ['type' => SingleLineText::class, 'handle' => 'text', 'limit' => true, 'max' => 20],
        ['type' => MultiLineText::class, 'handle' => 'rich', 'useRichText' => true, 'limit' => true, 'max' => 100],
        ['type' => Survey::class, 'handle' => 'surveyText', 'displayType' => 'singleLineText', 'limit' => true, 'max' => 30],
        ['type' => Survey::class, 'handle' => 'surveyRich', 'displayType' => 'multiLineText', 'useRichText' => true],
        ['type' => Name::class, 'handle' => 'name', 'useMultipleFields' => true, 'rows' => (new Name())->getSubFields()],
        ['type' => Phone::class, 'handle' => 'phone', 'countryEnabled' => true],
        ['type' => Calculations::class, 'handle' => 'total', 'formula' => '1 + 2'],
        ['type' => Signature::class, 'handle' => 'signature'],
        ['type' => Address::class, 'handle' => 'address', 'rows' => $addressRows, 'countryPreselectFromIp' => true],
        ['type' => Date::class, 'handle' => 'date', 'displayType' => 'datePicker'],
        ['type' => Date::class, 'handle' => 'time', 'displayType' => 'datePicker'],
        ['type' => Date::class, 'handle' => 'range', 'displayType' => 'datePicker', 'collectMode' => Date::COLLECT_RANGE],
    ];
    $configs = array_map(fn($config) => $config + ['label' => ucfirst($config['handle'])], $configs);
    $factory = formie()->form();
    if ($placement === 'top') {
        $factory->addFields($configs);
    } else {
        $factory->addField($placement === 'group' ? Group::class : Repeater::class, 'details', ['rows' => [['fields' => $configs]]]);
    }
    $form = $factory->create();
    $form->settings->cpSubmissionFieldConditions = $conditions;
    $form->settings->setAttributes($form->settings->getAttributes());
    $parent = $placement === 'top' ? $form : $form->getFieldByHandle('details');
    foreach ($parent->getFieldByHandle('name')->getFields() as $subfield) {
        if ($subfield instanceof SingleLineText && $subfield->enabled) {
            $subfield->limit = true;
            $subfield->max = 50;
        }
    }
    $parent->getFieldByHandle('date')->getFieldByHandle('time')->enabled = false;
    $parent->getFieldByHandle('time')->getFieldByHandle('date')->enabled = false;
    foreach ($parent->getFieldByHandle('address')->getFields() as $subfield) {
        $subfield->enabled = true;
    }
    $parent->getFieldByHandle('address')->getFieldByHandle('state')->inputMode = 'dropdownWhenAvailable';

    // These are the controls required by the browser modules' match/setup contracts.
    $controls = [
        'formie:text-limit' => ['.//input[@data-formie-single-line-text-input] | .//textarea[@data-formie-multi-line-text-input]'],
        'formie:rich-text' => ['.//*[@data-formie-rich-text]', './/textarea[@data-formie-multi-line-text-input]'],
        'formie:phone-country' => ['.//input[@type="tel" and @data-formie-phone-input]', './/input[@data-formie-phone-country-input]'],
        'formie:calculations' => ['.//input[@data-formie-calculation-input]'],
        'formie:signature' => ['.//canvas[@data-formie-signature-canvas]', './/input[@data-formie-signature-input]', './/button[@data-formie-signature-clear]'],
        'formie:address-country' => ['.//select[@data-formie-address-country-input]'],
        'formie:address-state' => ['.//input[@data-formie-address-state-input and @data-formie-address-state-dynamic]'],
        'formie:date-picker' => ['.//input[@data-formie-date-datepicker-input]'],
        'formie:repeater' => ['.//*[@data-formie-repeater-field-layout]', './/*[@data-formie-repeater-container]', './/button[@data-formie-repeater-add]'],
    ];

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $parent, $placement, $controls): void {
        Craft::$app->getRequest()->setIsCpRequest(true);
        $submission = new Submission();
        $submission->setForm($form);
        $values = ['text' => 'First', 'address' => ['address1' => '24 Test Road', 'city' => 'Melbourne', 'zip' => '3000', 'state' => 'Victoria', 'country' => 'AU']];
        if ($placement === 'top') {
            $submission->setFieldValues($values);
        } else {
            $submission->setFieldValue('details', $placement === 'repeater' ? [$values, $values] : $values);
        }
        $html = '';
        foreach ($form->getFields() as $field) {
            $html .= $field->getSubmissionHtml($submission->getFieldValue($field->handle), $submission);
        }
        $document = new DOMDocument();
        @$document->loadHTML('<div>' . $html . '</div>');
        $xpath = new DOMXPath($document);
        $entries = $form->getCpEditConfig()['modules']['entries'];
        $ids = array_column($entries, 'moduleId');
        foreach (array_keys($controls) as $moduleId) {
            if ($moduleId !== 'formie:repeater' || $placement === 'repeater') {
                expect($ids)->toContain($moduleId);
            }
        }
        foreach ($entries as $entry) {
            if ($entry['moduleId'] === 'formie:conditions') {
                continue;
            }
            expect($controls)->toHaveKey($entry['moduleId']);
            foreach ($entry['targets'] as $target) {
                $targets = $xpath->query('//*[@data-formie-field-uid="' . $target['uid'] . '"]');
                expect($targets->length)->toBeGreaterThan(0, $entry['moduleId'] . ' target');
                foreach ($targets as $node) {
                    foreach ($controls[$entry['moduleId']] as $selector) {
                        expect($xpath->query($selector, $node)->length)->toBeGreaterThan(0, $entry['moduleId'] . ': ' . $selector);
                    }
                }
            }
        }

        // All address providers use the same autocomplete and destination controls.
        $address = $parent->getFieldByHandle('address');
        $addressTargets = $xpath->query('//*[@data-formie-field-uid="' . $address->uid . '"]');
        foreach ($addressTargets as $target) {
            $isRowTemplate = str_contains($target->getAttribute('data-formie-field-handle'), '__ROW__');
            expect($xpath->query('.//input[@data-formie-address-line1-input]', $target)->item(0)->getAttribute('value'))
                ->toBe($isRowTemplate ? '' : '24 Test Road');
            foreach (['autocomplete', 'line1', 'line2', 'line3', 'city', 'zip', 'state', 'country'] as $part) {
                expect($xpath->query('.//*[@data-formie-address-' . $part . '-input]', $target)->length)->toBe(1, $part);
            }
        }
        foreach ([Google::class, AddressFinder::class, Loqate::class, PlaceKit::class] as $providerClass) {
            $provider = new $providerClass(['apiKey' => 'test-key']);
            if ($provider instanceof AddressFinder) {
                $provider->countryCode = 'AU';
            }
            $module = $provider->getBrowserModule(new BrowserModuleContext(['form' => $form, 'field' => $address, 'surface' => BrowserModule::SURFACE_CP_EDIT]));
            expect($module)->not->toBeNull()
                ->and($module->supportsSurface(BrowserModule::SURFACE_CP_EDIT))->toBeTrue();
        }
    });
})->with(['top', 'group', 'repeater'])->with(['show-all', 'hide', 'muted']);
