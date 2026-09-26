<?php

use verbb\formie\Formie;
use verbb\formie\client\bootstrap\models\FormBootstrap;
use verbb\formie\client\models\LoadContext;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\BrowserModuleManifest;
use Tests\Support\WebRequestTestHelper;

it('retains repeated declarations and shares the exact public inventory', function() {
    $form = formie()->form(['title' => 'Module parity'])->singleLineTextField('name')->create();
    $original = $form->getFieldByHandle('name');
    $field = new class extends SingleLineText {
        protected function defineBrowserModules(): array
        {
            return [new BrowserModuleEntry(['moduleId' => 'example:repeat']), new BrowserModuleEntry(['moduleId' => 'example:repeat'])];
        }
    };
    $field->setAttributes($original->getAttributes(), false);
    $form->getPages()[0]->getRows()[0]->setFields([$field]);
    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $server = $builder->buildCanonical($form);
    $client = $builder->buildCanonical($form, BrowserModuleEntry::SURFACE_CLIENT_RENDERED);
    expect($server)->toBe($client)->and($server['entries'])->toHaveCount(2)
        ->and($server['entries'][0]['key'])->not->toBe($server['entries'][1]['key'])
        ->and($server['entries'][0]['targets'][0]['targetId'])->toBe($field->uid);
    WebRequestTestHelper::withWebRequestContext(function() use ($form, $server) {
        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext())->toArrayRecursive();
        expect($bootstrap['contractVersion'])->toBe(1)
            ->and($bootstrap['definition']['modules'])->toBe($server)
            ->and($bootstrap['definition']['pages'][0]['rows'][0]['fields'][0]['client']['valueType'])->not->toHaveKey('class');
    });
});

it('rejects unsupported versions and executable URLs', function() {
    expect(fn() => new FormBootstrap(['contractVersion' => 99]))->toThrow(InvalidArgumentException::class)
        ->and(fn() => new BrowserModuleManifest([], 99))->toThrow(InvalidArgumentException::class)
        ->and(fn() => (new BrowserModuleEntry(['moduleId' => 'https://evil.example/code.js']))->toArray())->toThrow(InvalidArgumentException::class)
        ->and(fn() => new BrowserModuleEntry(['moduleId' => 'example:test', 'src' => 'https://evil.example/code.js']))->toThrow(\yii\base\UnknownPropertyException::class);
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
    $entries = $field->browserModules()->toModules(new BrowserModuleContext(['field' => $field]));
    expect($entries)->toHaveCount(2)
        ->and($entries[0]->toArray())->not->toHaveKey('src')
        ->and($entries[0]->moduleId)->toBe('formie:signature')
        ->and($entries[0]->config['options']['penColor'])->toBe('#123456')
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
