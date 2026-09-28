<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FieldsController;
use verbb\formie\Formie;
use verbb\formie\helpers\FieldAccess;
use verbb\formie\helpers\SignatureAccess;
use verbb\formie\theme\context\RenderContext;

use craft\helpers\Json;

it('embeds browser classes but never executable theme config on the form element', function (): void {
    $form = formie()->form(['title' => 'Summary Theme Embed'])->singleLineTextField('fullName')->create();
    Formie::$plugin->getRendering()->pushRenderFrame($form, [
        'themeConfig' => ['fieldSummaryLabel' => ['class' => 'embedded-summary-label']],
    ]);

    try {
        $tag = WebRequestTestHelper::withWebRequestContext(fn() => Formie::$plugin->getFormSlotRegistry()->resolve('form', RenderContext::from([
            'form' => $form,
        ])));

        expect($tag?->coreAttributes['data']['formie-theme-config'] ?? null)->toBeNull()
            ->and($tag?->coreAttributes['data']['formie-theme-classes'] ?? null)->toBeString();
    } finally {
        Formie::$plugin->getRendering()->popRenderFrame();
    }
});

it('binds Summary fragments to the issued immutable theme and ignores posted executable config', function (): void {
    $form = formie()
        ->form(['title' => 'Summary Theme Ajax'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Theme owner'])->save();
    $summaryField = $form->getFieldByHandle('summary');

    Formie::$plugin->getRendering()->pushRenderFrame($form, [
        'themeConfig' => [
            'fieldSummaryLabel' => [
                'class' => 'issued-summary-label',
                'append' => ['tag' => 'span', 'text' => '<safe-text>'],
            ],
        ],
    ]);

    try {
        $accessToken = FieldAccess::issueAccessToken($submission, (int)$summaryField->id);
    } finally {
        Formie::$plugin->getRendering()->popRenderFrame();
    }

    $html = WebRequestTestHelper::withWebRequestContext(function () use ($accessToken): string {
        Craft::$app->getRequest()->setBodyParams([
            'accessToken' => $accessToken,
            'theme' => 'none',
            'themeConfig' => Json::encode([
                'fieldSummaryLabel' => [
                    'tag' => 'script',
                    'attributes' => ['onclick' => 'alert(1)', 'class' => 'posted-summary-label'],
                    'append' => ['html' => '<img src=x onerror=alert(1)>'],
                ],
            ]),
        ]);

        return (new FieldsController('formie-fields-summary-theme', Craft::$app))->actionGetSummaryHtml();
    }, [
        'method' => 'POST',
    ]);

    expect($html)->toContain('issued-summary-label', '&lt;safe-text&gt;')
        ->and($html)->not->toContain('posted-summary-label', '<script', 'onclick=', 'onerror=');
});

it('rejects a tampered encrypted Summary theme snapshot', function (): void {
    $form = formie()
        ->form(['title' => 'Summary Theme Token'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();
    $submission = formie()->submission($form)->with(['fullName' => 'Token owner'])->save();
    $summaryField = $form->getFieldByHandle('summary');
    $token = FieldAccess::issueAccessToken($submission, (int)$summaryField->id);

    $offset = intdiv(strlen((string)$token), 2);
    $replacement = $token[$offset] === 'A' ? 'B' : 'A';
    $tampered = substr_replace((string)$token, $replacement, $offset, 1);

    expect(FieldAccess::resolveAccessToken($tampered))->toBeNull();
});

it('keeps large issued themes out of compact tokens and restores them in another process', function (): void {
    $form = formie()->form()->singleLineTextField('name')->summaryField('summary')->create();
    $submission = formie()->submission($form)->with(['name' => 'Owner'])->save();
    $rendering = Formie::$plugin->getRendering();
    $rendering->pushRenderFrame($form, ['theme' => 'none', 'themeConfig' => [
        'fieldSummaryLabel' => ['attributes' => ['title' => str_repeat('x', 48000)]],
    ]]);
    try {
        $token = FieldAccess::issueAccessToken($submission, $form->getFieldByHandle('summary')->id);
        $second = FieldAccess::issueAccessToken($submission, $form->getFieldByHandle('summary')->id);
    } finally {
        $rendering->popRenderFrame();
    }
    $decode = static fn($value) => Json::decode(Craft::$app->getSecurity()->decryptByKey(base64_decode($value), Formie::$plugin->getSettings()->getSecurityKey()));
    $payload = $decode($token);
    expect(strlen($token))->toBeLessThan(1024)->and($payload)->not->toHaveKey('config')
        ->and($payload['theme'])->toBeString()->toBe($decode($second)['theme']);
    $rows = (new \craft\db\Query())->from('{{%formie_instance_configs}}')->where(['tokenHash' => $payload['theme']])->all();
    expect($rows)->toHaveCount(1)->and($rows[0]['config'])->not->toContain(str_repeat('x', 64));

    $script = <<<'PHP'
require 'tests/bootstrap-craft.php';
$payload = \verbb\formie\helpers\FieldAccess::resolveAccessToken($argv[1]);
$form = \verbb\formie\elements\Form::find()->id($payload['formId'])->siteId($payload['siteId'])->status(null)->one();
$theme = (new \verbb\formie\services\ThemeConfig())->restoreFragmentState($form, $payload['theme']);
echo json_encode([$theme->mode, strlen($theme->config['fieldSummaryLabel']['attributes']['title']), $theme->digest]);
PHP;
    $process = proc_open([PHP_BINARY, '-r', $script, $token], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
    expect(is_resource($process))->toBeTrue();
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    expect(proc_close($process))->toBe(0, $errors)
        ->and(Json::decode(substr($output, strrpos($output, "\n") + 1)))->toBe(['none', 48000, $payload['themeDigest']]);

    Craft::$app->getDb()->createCommand()->update('{{%formie_instance_configs}}', ['expiresAt' => time() - 1], ['tokenHash' => $payload['theme']])->execute();
    expect(FieldAccess::resolveAccessToken($token))->toBeNull();
});

it('issues default-theme tokens without extra rows and expires the token itself', function (): void {
    $form = formie()->form()->summaryField('summary')->create();
    $submission = formie()->submission($form)->save();
    $before = (new \craft\db\Query())->from('{{%formie_instance_configs}}')->count();
    $token = FieldAccess::issueAccessToken($submission, $form->getFieldByHandle('summary')->id);
    expect((new \craft\db\Query())->from('{{%formie_instance_configs}}')->count())->toBe($before);
    $payload = Json::decode(Craft::$app->getSecurity()->decryptByKey(base64_decode($token), Formie::$plugin->getSettings()->getSecurityKey()));
    expect($payload['theme'])->toBeNull()->and(FieldAccess::resolveAccessToken($token)['theme']['config'])->toBe([]);
    $payload['expiresAt'] = time() - 1;
    $expired = base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($payload), Formie::$plugin->getSettings()->getSecurityKey()));
    expect(FieldAccess::resolveAccessToken($expired))->toBeNull();
});

it('rejects unavailable or mismatched stored Summary state without a theme fallback', function (string $change): void {
    $form = formie()->form()->summaryField('summary')->create();
    $submission = formie()->submission($form)->save();
    $rendering = Formie::$plugin->getRendering();
    $rendering->pushRenderFrame($form, ['themeConfig' => ['fieldSummaryLabel' => ['class' => 'issued-theme']]]);
    try {
        $token = FieldAccess::issueAccessToken($submission, $form->getFieldByHandle('summary')->id);
    } finally {
        $rendering->popRenderFrame();
    }
    expect(FieldAccess::resolveAccessToken($token)['theme']['config']['fieldSummaryLabel']['class'])->toBe(['issued-theme']);
    $payload = Json::decode(Craft::$app->getSecurity()->decryptByKey(base64_decode($token), Formie::$plugin->getSettings()->getSecurityKey()));
    $db = Craft::$app->getDb();
    if ($change === 'missing') {
        $db->createCommand()->delete('{{%formie_instance_configs}}', ['tokenHash' => $payload['theme']])->execute();
    } elseif ($change === 'site') {
        $db->createCommand()->update('{{%formie_instance_configs}}', ['siteId' => 0], ['tokenHash' => $payload['theme']])->execute();
    } elseif ($change === 'form') {
        $otherForm = formie()->form()->create();
        $db->createCommand()->update('{{%formie_instance_configs}}', ['formId' => $otherForm->id], ['tokenHash' => $payload['theme']])->execute();
    } else {
        $payload['themeDigest'] = str_repeat('0', 64);
        $token = base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($payload), Formie::$plugin->getSettings()->getSecurityKey()));
    }
    expect(FieldAccess::resolveAccessToken($token))->toBeNull();
    expect(WebRequestTestHelper::withWebRequestContext(function () use ($token): string {
        Craft::$app->getRequest()->setBodyParams(['accessToken' => $token]);
        return (new FieldsController('formie-fields-summary-unavailable', Craft::$app))->actionGetSummaryHtml();
    }, ['method' => 'POST']))->toBe('');
})->with(['missing', 'site', 'form', 'digest']);

it('keeps Signature image links independent of retained Summary theme state', function (): void {
    $form = formie()->form()->signatureField('signature')->create();
    $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4////fwAJ+wP9KobjigAAAABJRU5ErkJggg==';
    $submission = formie()->submission($form)->with(['signature' => 'data:image/png;base64,' . $png])->save();
    $before = (new \craft\db\Query())->from('{{%formie_instance_configs}}')->count();
    $field = $form->getFieldByHandle('signature');
    $token = SignatureAccess::issueAccessToken($submission, $field->id, $field->valueKey(), $submission->getFieldValue('signature'));
    [$encodedPayload] = explode('.', $token, 2);
    $encodedPayload .= str_repeat('=', (4 - strlen($encodedPayload) % 4) % 4);
    $payload = Json::decode(base64_decode(strtr($encodedPayload, '-_', '+/'), true));
    expect($payload)->not->toHaveKey('expiresAt')->not->toHaveKey('theme')
        ->and($payload['purpose'])->toBe('signature-image')
        ->and(FieldAccess::issueAccessToken($submission, $field->id))->toBeNull()
        ->and((new \craft\db\Query())->from('{{%formie_instance_configs}}')->count())->toBe($before);
    $image = WebRequestTestHelper::withWebRequestContext(function () use ($token) {
        return (new FieldsController('formie-fields-signature-theme', Craft::$app))->actionGetSignatureImage()?->data;
    }, ['queryParams' => ['accessToken' => $token]]);
    expect($image)->toBe(base64_decode($png));
});
