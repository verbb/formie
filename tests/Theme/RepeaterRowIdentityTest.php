<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\fields\Address;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;

it('renders populated repeater row identities that match their input names', function (string $theme): void {
    $form = formie()->form()->multiPage(2)->onPage(1)
        ->repeaterField('people', ['rows' => [['fields' => [
            ['type' => SingleLineText::class, 'handle' => 'name', 'label' => 'Name', 'required' => true],
            ['type' => Address::class, 'handle' => 'address', 'label' => 'Address', 'rows' => (new Address())->getSubFields()],
        ]]]])
        ->onPage(2)->singleLineTextField('notes')
        ->settings(['disableCaptchas' => true])->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $theme): void {
        $rendering = Formie::$plugin->getRendering();
        $rendering->populateFormValues($form, ['people' => [
            ['name' => 'First', 'address' => ['address1' => '1 Test Street']],
            ['name' => 'Second', 'address' => ['address1' => '2 Test Street']],
        ]]);
        $view = Craft::$app->getView();
        ob_start();
        $view->beginPage();
        $view->head();
        $view->beginBody();
        echo $rendering->renderForm($form, ['theme' => $theme]);
        $view->endBody();
        $view->endPage();
        $html = ob_get_clean();
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $rows = $xpath->query('//*[@data-formie-repeater-item]');

        expect($rows->length)->toBe(2);

        foreach ($rows as $index => $row) {
            expect($row->getAttribute('data-formie-repeater-item'))->toBe((string)$index)
                ->and($row->getAttribute('data-formie-repeater-item-id'))->toBe((string)$index);
            expect($xpath->query('.//input[@name="fields[people][' . $index . '][name]"]', $row)->length)->toBe(1)
                ->and($xpath->query('.//input[@name="fields[people][' . $index . '][address][address1]"]', $row)->length)->toBe(1);
            expect($xpath->query('.//input[@name="fields[people][' . $index . '][address][address1]"]', $row)->item(0)->getAttribute('value'))
                ->toBe(($index + 1) . ' Test Street');
        }

        // Client-added rows still need the placeholder so each gets a fresh index.
        expect($html)->toContain('data-formie-repeater-item-id="__ROW__"')
            ->toContain('name="fields[people][__ROW__][name]"')
            ->toContain('name="fields[people][__ROW__][address][address1]"');
    });
})->with(['formie', 'none']);
