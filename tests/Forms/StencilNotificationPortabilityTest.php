<?php

declare(strict_types=1);

use craft\helpers\FileHelper;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\models\EmailTemplate;
use verbb\formie\models\PdfTemplate;
use verbb\formie\models\Notification;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\records\Stencil as StencilRecord;
use verbb\formie\services\Stencils;

function stencilPortabilityFixture(string $scope, bool $custom = true): array
{
    $tag = 'portableStencil' . bin2hex(random_bytes(5));
    $directory = Craft::$app->getPath()->getSiteTemplatesPath() . '/' . $tag;
    FileHelper::createDirectory($directory);
    file_put_contents($directory . '/email.html', '<p>Local email template</p>');
    file_put_contents($directory . '/pdf.html', '<p>Local PDF template</p>');
    $email = new EmailTemplate(['name' => $tag, 'handle' => $tag, 'template' => $tag . '/email']);
    $pdf = new PdfTemplate(['name' => $tag, 'handle' => $tag, 'template' => $tag . '/pdf']);
    expect(Formie::$plugin->getEmailTemplates()->saveTemplate($email))->toBeTrue()
        ->and(Formie::$plugin->getPdfTemplates()->saveTemplate($pdf))->toBeTrue();
    $form = formie()->form()->singleLineTextField('message')->create();
    $notification = new Notification(['name' => 'Notice', 'handle' => 'notice', 'to' => 'fixture@example.test', 'subject' => 'Local fixture', 'enabled' => false]);

    if ($custom) {
        $notification->setTemplate($email);
        $notification->setPdfTemplate($pdf);
    }

    $form->setNotifications([$notification]);
    $data = new StencilData();
    $data->populateFormData($form);
    $stencil = new Stencil(['name' => $tag, 'handle' => $tag, 'scope' => $scope, 'data' => $data]);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();

    return [$stencil, $email, $pdf, $tag];
}

it('stores portable notification template identities only for project stencils', function (string $scope, bool $custom): void {
    [$stencil, $email, $pdf, $tag] = stencilPortabilityFixture($scope, $custom);
    $config = $stencil->getConfig()['data']['notifications'][0];

    if ($scope === Stencils::SCOPE_PROJECT) {
        expect($config)->not->toHaveKey('templateId')->not->toHaveKey('pdfTemplateId')
            ->and($config['templateUid'])->toBe($custom ? $email->uid : null)
            ->and($config['pdfTemplateUid'])->toBe($custom ? $pdf->uid : null);
    } else {
        expect($config['templateId'])->toBe($custom ? $email->id : null)
            ->and($config['pdfTemplateId'])->toBe($custom ? $pdf->id : null);
    }

    $saved = Formie::$plugin->getStencils()->getStencilById($stencil->id);
    $form = Formie::$plugin->getStencils()->createFromStencil($saved, ['title' => $tag, 'handle' => $tag . 'Form']);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $notification = Formie::$plugin->getForms()->getFormById($form->id)->getNotifications()[0];
    expect($notification->templateId)->toBe($custom ? $email->id : null)
        ->and($notification->pdfTemplateId)->toBe($custom ? $pdf->id : null);
})->with([Stencils::SCOPE_PROJECT, Stencils::SCOPE_SITE])->with([false, true]);

it('materialises portable notification templates while retaining legacy local config', function (string $mode): void {
    [$stencil, $email, $pdf] = stencilPortabilityFixture(Stencils::SCOPE_PROJECT);
    $config = $stencil->getConfig();
    $originalConfig = $config;
    $notification = &$config['data']['notifications'][0];
    unset($notification['templateUid'], $notification['pdfTemplateUid']);
    $notification['templateId'] = $email->id;
    $notification['pdfTemplateId'] = $pdf->id;

    if ($mode !== 'legacy') {
        $notification['templateUid'] = $mode === 'clear' ? null : $email->uid;
        $notification['pdfTemplateUid'] = $mode === 'clear' ? null : $pdf->uid;

        if ($mode === 'portable') {
            unset($notification['templateId'], $notification['pdfTemplateId']);
        }
    }

    unset($notification);
    $config['name'] .= ' changed';
    try {
        Craft::$app->getProjectConfig()->set(Stencils::CONFIG_STENCILS_KEY . '.' . $stencil->uid, $config);
        $stored = Json::decodeIfJson(StencilRecord::findOne($stencil->id)->data)['notifications'][0];
        expect($stored['templateId'] ?? null)->toBe($mode === 'clear' ? null : $email->id)
            ->and($stored['pdfTemplateId'] ?? null)->toBe($mode === 'clear' ? null : $pdf->id)
            ->and($stored)->not->toHaveKey('templateUid')->not->toHaveKey('pdfTemplateUid');
        $saved = Formie::$plugin->getStencils()->getStencilById($stencil->id)->getNotifications()[0];
        expect($saved->getTemplate()?->uid)->toBe($mode === 'clear' ? null : $email->uid)
            ->and($saved->getPdfTemplate()?->uid)->toBe($mode === 'clear' ? null : $pdf->uid);
    } finally {
        Craft::$app->getProjectConfig()->set(Stencils::CONFIG_STENCILS_KEY . '.' . $stencil->uid, $originalConfig);
    }
})->with(['portable', 'clear', 'legacy']);

it('resolves portable notification templates in stencil data read straight from project config', function (): void {
    [$stencil, $email, $pdf] = stencilPortabilityFixture(Stencils::SCOPE_PROJECT);
    $config = Craft::$app->getProjectConfig()->get(Stencils::CONFIG_STENCILS_KEY . '.' . $stencil->uid);

    // Migrations and other code build stencil data from project config without the change handler.
    $notification = (new StencilData(\craft\helpers\ProjectConfig::unpackAssociativeArrays($config['data'])))->notifications[0];

    expect($notification->templateId)->toBe($email->id)
        ->and($notification->pdfTemplateId)->toBe($pdf->id);
});
