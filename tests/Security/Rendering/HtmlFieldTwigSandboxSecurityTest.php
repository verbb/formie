<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\fields\Html;
use Twig\Error\Error;

beforeEach(function (): void {
    \verbb\formie\Formie::$plugin->getSettings()->allowHtmlFieldTwig = true;
    \verbb\formie\Formie::$plugin->getSettings()->requireHtmlPurification = false;
});

it('defaults html fields to twig disabled', function (): void {
    $field = new Html();

    expect($field->allowTwig)->toBeFalse();
})->group('security');

it('blocks craft environment access in sandboxed html field twig', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Sandbox'])
        ->htmlField('notice', [
            'htmlContent' => '{{ craft.app.cache.cachePath }}',
            'allowTwig' => true,
            'purifyContent' => false,
        ])
        ->create();

    $field = $form->getFieldByHandle('notice');

    expect(fn() => $field?->getRenderedHtmlBlock($form, null, null))
        ->toThrow(Error::class);
})->group('security');

it('allows sandboxed html field twig to read form and submission context', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Context'])
        ->htmlField('notice', [
            'htmlContent' => '<p>{{ form.title }} · {{ submission.id ?? "new" }}</p>',
            'allowTwig' => true,
            'purifyContent' => false,
        ])
        ->create();

    $submission = formie()->submission($form)->save();
    $field = $form->getFieldByHandle('notice');
    $html = $field?->getRenderedHtmlBlock($form, null, $submission);

    expect($html)->toBeString()
        ->toContain('<p>HTML Field Twig Context · ' . $submission->id . '</p>')
        ->not->toContain('{{');
})->group('security');

it('still purifies hostile twig output from html fields', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Purify'])
        ->htmlField('notice', [
            'htmlContent' => '<p>{{ "safe-text" }}</p>' . MaliciousPayloads::storedXssProbe(),
            'allowTwig' => true,
            'purifyContent' => true,
        ])
        ->create();

    $field = $form->getFieldByHandle('notice');
    $html = $field?->getRenderedHtmlBlock($form, null, null);

    expect($html)->toBeString()
        ->toContain('safe-text')
        ->not->toContain('<script')
        ->not->toContain('onerror=');
})->group('security');

it('keeps an invalid twig preview from breaking other builder field choices', function (): void {
    $form = formie()->form()->htmlField('invalidPreview', [
        'htmlContent' => '{{ craft.app.cache.cachePath }}', 'allowTwig' => true,
    ])->singleLineTextField('usableField')->create();
    $field = $form->getFieldByHandle('invalidPreview');
    $settings = $field->modifyFieldSettings([]);
    expect(json_encode($settings))->toContain('Unable to render HTML preview.')
        ->not->toContain('cachePath', 'SecurityNotAllowedPropertyError');
    $choices = \verbb\formie\Formie::$plugin->getFields()->getExistingFields();
    expect(json_encode($choices))->toContain('usableField');
})->group('security');

function withDevModeOff(callable $callback): mixed
{
    $general = Craft::$app->getConfig()->getGeneral();
    $previous = $general->devMode;
    $general->devMode = false;

    try {
        return $callback();
    } finally {
        $general->devMode = $previous;
    }
}

it('renders blocked html field twig as nothing and logs it when devMode is off', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Fail Soft', 'handle' => 'htmlFieldTwigFailSoft'])
        ->htmlField('latestNews', [
            'htmlContent' => '<p>{{ url("news") }}</p>',
            'allowTwig' => true,
            'purifyContent' => false,
        ])
        ->create();

    $field = $form->getFieldByHandle('latestNews');
    $offset = count(Craft::getLogger()->messages);
    $html = withDevModeOff(fn() => $field?->getRenderedHtmlBlock($form, null, null));
    $logged = implode("\n", array_filter(array_column(array_slice(Craft::getLogger()->messages, $offset), 0), 'is_string'));

    expect($html)->toBe('')
        ->and($logged)->toContain('Unable to render Twig in HTML field “latestNews” on form “htmlFieldTwigFailSoft”')
        ->toContain('Unknown "url" function');
})->group('security');

it('still renders allowed html field twig when devMode is off', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Allowed'])
        ->htmlField('notice', [
            'htmlContent' => '<p>{{ form.title }}</p>',
            'allowTwig' => true,
            'purifyContent' => false,
        ])
        ->create();

    $field = $form->getFieldByHandle('notice');

    expect(withDevModeOff(fn() => $field?->getRenderedHtmlBlock($form, null, null)))
        ->toBe('<p>HTML Field Twig Allowed</p>');
})->group('security');

it('still shows the builder preview error for blocked html field twig when devMode is off', function (): void {
    $form = formie()->form()->htmlField('invalidPreview', [
        'htmlContent' => '{{ url("news") }}', 'allowTwig' => true,
    ])->create();
    $field = $form->getFieldByHandle('invalidPreview');
    $settings = withDevModeOff(fn() => $field->modifyFieldSettings([]));

    expect(json_encode($settings))->toContain('Unable to render HTML preview.');
})->group('security');

// Form templates carried over from Formie 3 call getRenderedHtmlContent() directly.
it('renders blocked html field twig as nothing from getRenderedHtmlContent() when devMode is off', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Direct', 'handle' => 'htmlFieldTwigDirect'])
        ->htmlField('latestNews', [
            'htmlContent' => '<p>{{ url("news") }}</p>',
            'allowTwig' => true,
            'purifyContent' => false,
        ])
        ->create();

    $field = $form->getFieldByHandle('latestNews');
    $offset = count(Craft::getLogger()->messages);
    $html = withDevModeOff(fn() => $field?->getRenderedHtmlContent());
    $logged = implode("\n", array_filter(array_column(array_slice(Craft::getLogger()->messages, $offset), 0), 'is_string'));

    expect($html)->toBe('')
        ->and($logged)->toContain('Unable to render Twig in HTML field “latestNews” on form “htmlFieldTwigDirect”')
        ->and(fn() => $field?->getRenderedHtmlContent())->toThrow(\Twig\Error\Error::class);
})->group('security');
