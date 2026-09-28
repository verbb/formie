<?php

use verbb\formie\Formie;
use verbb\formie\client\bootstrap\models\FormBootstrap;
use verbb\formie\client\models\LoadContext;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\BrowserModuleManifest;
use Tests\Support\WebRequestTestHelper;

it('retains repeated declarations and shares the exact public inventory', function() {
    $form = formie()->form(['title' => 'Module parity'])->singleLineTextField('name')->create();
    $original = $form->getFieldByHandle('name');
    $field = new class extends SingleLineText {
        protected function defineBrowserModules(): array
        {
            return [
                new BrowserModule(['moduleId' => 'example:repeat', 'surfaces' => [BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED]]),
                new BrowserModule(['moduleId' => 'example:repeat', 'surfaces' => [BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED]]),
            ];
        }
    };
    $field->setAttributes($original->getAttributes(), false);
    $form->getPages()[0]->getRows()[0]->setFields([$field]);
    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $server = $builder->buildForSurface($form)->toArray();
    $client = $builder->buildForSurface($form, BrowserModule::SURFACE_CLIENT_RENDERED)->toArray();
    expect($server['surface'])->toBe(BrowserModule::SURFACE_SERVER_RENDERED)
        ->and($client['surface'])->toBe(BrowserModule::SURFACE_CLIENT_RENDERED)
        ->and($server['entries'])->toEqual($client['entries'])->toHaveCount(2)
        ->and($server['entries'][0]['key'])->not->toBe($server['entries'][1]['key'])
        ->and($server['entries'][0]['targets'][0]['uid'])->toBe($field->uid);
    WebRequestTestHelper::withWebRequestContext(function() use ($form, $client) {
        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext())->toArrayRecursive();
        expect($bootstrap['contractVersion'])->toBe(1)
            ->and($bootstrap['definition']['modules'])->toEqual($client)
            ->and($bootstrap['definition']['pages'][0]['rows'][0]['fields'][0]['moduleRefs'])->toBe(array_column($client['entries'], 'key'))
            ->and($bootstrap['definition']['pages'][0]['rows'][0]['fields'][0]['client']['valueType'])->not->toHaveKey('class');
    });
});

it('references configured occurrences rather than reusable module IDs', function() {
    $form = formie()
        ->form(['title' => 'Module occurrence references'])
        ->signatureField('firstSignature', ['penColor' => '#111111'])
        ->signatureField('secondSignature', ['penColor' => '#222222'])
        ->create();

    WebRequestTestHelper::withWebRequestContext(function() use ($form) {
        $definition = $form->getClientRenderedDefinition(new LoadContext())->toArrayRecursive();
        $entries = array_values(array_filter(
            $definition['modules']['entries'],
            static fn(array $entry): bool => $entry['moduleId'] === 'formie:signature',
        ));
        $fields = $definition['pages'][0]['rows'][0]['fields'];

        $entryKeys = array_column($entries, 'key');

        expect($entries)->toHaveCount(2)
            ->and(array_unique($entryKeys))->toHaveCount(2)
            ->and(array_column($entries, 'config'))->toContain(['backgroundColor' => '#ffffff', 'penColor' => '#111111', 'penWeight' => '2'])
            ->toContain(['backgroundColor' => '#ffffff', 'penColor' => '#222222', 'penWeight' => '2'])
            ->and($fields[0]['moduleRefs'])->toBe([$entries[0]['key']])
            ->and($fields[1]['moduleRefs'])->toBe([$entries[1]['key']]);
    });
});

it('rejects unsupported versions and executable URLs', function() {
    expect(fn() => new FormBootstrap(['contractVersion' => 99]))->toThrow(InvalidArgumentException::class)
        ->and(fn() => new BrowserModuleManifest(BrowserModule::SURFACE_SERVER_RENDERED, [], 99))->toThrow(InvalidArgumentException::class)
        ->and(fn() => new BrowserModule(['moduleId' => 'https://evil.example/code.js']))->toThrow(InvalidArgumentException::class)
        ->and(fn() => new BrowserModule(['moduleId' => 'example:test', 'src' => 'https://evil.example/code.js']))->toThrow(InvalidArgumentException::class)
        ->and(fn() => new BrowserModule(['moduleId' => 'example:test', 'capability' => 'decorative']))->toThrow(InvalidArgumentException::class)
        ->and(fn() => new BrowserModule(['moduleId' => 'example:test', 'surfaces' => []]))->toThrow(InvalidArgumentException::class)
        ->and(fn() => new BrowserModule(['moduleId' => 'example:test', 'kind' => 'decorative']))->toThrow(InvalidArgumentException::class);
});

it('adapts stable Formie 3 declarations without forwarding executable URLs', function() {
    $field = new class extends SingleLineText {
        public function getFrontEndJsModules(): ?array
        {
            return [
                ['module' => 'FormieSignature', 'src' => 'https://ignored.example/legacy.js', 'settings' => ['penColor' => '#123456']],
                ['module' => 'FormieSignature', 'settings' => ['penColor' => '#654321']],
            ];
        }
    };
    $entries = $field->browserModules(new BrowserModuleContext(['field' => $field]));
    expect($entries)->toHaveCount(2)
        ->and(get_object_vars($entries[0]))->not->toHaveKey('src')
        ->and($entries[0]->moduleId)->toBe('formie:signature')
        ->and($entries[0]->config['penColor'])->toBe('#123456')
        ->and($entries[0]->surfaces)->toBe(['server-rendered']);
});

it('excludes raw payment settings and recursively excludes PHP value classes', function() {
    $field = new \verbb\formie\fields\Payment(['handle' => 'payment', 'providerSettings' => ['secretKey' => 'never-public', 'serverAccount' => 'private-account']]);
    $definition = $field->getClientRenderedDefinition();
    expect(json_encode($definition))->not->toContain('never-public')->not->toContain('private-account')
        ->and($definition['input'])->not->toHaveKey('providerSettings');
    $bootstrap = new FormBootstrap();
    $bootstrap->contractVersion = 999;
    expect(fn() => $bootstrap->toArrayRecursive())->toThrow(InvalidArgumentException::class);
});
