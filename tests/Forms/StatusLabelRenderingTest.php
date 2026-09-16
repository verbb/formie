<?php

declare(strict_types=1);

it('renders the complete status name as plain text', function (string $class): void {
    $name = 'Awaiting <review> & approval';
    $status = new $class(['name' => $name]);
    $document = new DOMDocument();
    $document->loadHTML($status->getLabelHtml(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    expect($xpath->evaluate('string(//span[@class="formieStatusLabel"])'))->toBe($name)
        ->and($document->getElementsByTagName('review')->length)->toBe(0)
        ->and($xpath->query('//span[contains(@class,"status")]')->length)->toBe(1);
})->with([
    \verbb\formie\models\FormStatus::class,
    \verbb\formie\models\SubmissionStatus::class,
]);

it('escapes plain names in control panel table data', function (string $path, string $variable): void {
    $name = 'Awaiting <review> & approval';
    $item = new class($name) {
        public int $id = 1;
        public string $handle = 'review';
        public function __construct(public string $name) {}
        public function getCpEditUrl(): string { return '/admin/formie/example'; }
        public function canDelete(): bool { return true; }
        public function getScopeLabel(): string { return 'Global'; }
    };
    // Exercise the template's actual table-data preparation independently of the page shell.
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/templates/' . $path . '/index.html');
    $start = strpos($source, '{% set tableData = [] %}');
    $end = strpos($source, '{% js %}', $start);
    $template = substr($source, $start, $end - $start) . '{{ tableData | json_encode | raw }}';
    $data = json_decode(Craft::$app->getView()->renderString($template, [$variable => [$item]]), true, 512, JSON_THROW_ON_ERROR);
    $document = new DOMDocument();
    $document->loadHTML('<span>' . $data[0]['labelHtml']['html'] . '</span>', LIBXML_NOERROR | LIBXML_NOWARNING);

    expect($document->getElementsByTagName('span')->item(0)->textContent)->toBe($name)
        ->and($document->getElementsByTagName('review')->length)->toBe(0);
})->with([
    ['settings/form-templates', 'formTemplates'],
    ['settings/email-templates', 'emailTemplates'],
    ['settings/pdf-templates', 'pdfTemplates'],
    ['settings/form-groups', 'formGroups'],
    ['stencils', 'stencils'],
]);
