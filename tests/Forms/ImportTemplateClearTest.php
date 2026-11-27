<?php

declare(strict_types=1);

use craft\helpers\FileHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\models\EmailTemplate;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;

it('overwrites cleared template assignments when updating an imported form', function (string $clear, bool $explicitNull): void {
    $tag = 'importClear' . bin2hex(random_bytes(5));
    $directory = Craft::$app->getPath()->getSiteTemplatesPath() . '/' . $tag;
    FileHelper::createDirectory($directory);
    file_put_contents($directory . '/form.html', '<p>Local form template</p>');
    file_put_contents($directory . '/email.html', '<p>Local email template</p>');
    file_put_contents($directory . '/pdf.html', '<p>Local PDF template</p>');
    $formTemplate = new FormTemplate(['name' => $tag, 'handle' => $tag, 'template' => $tag, 'useCustomTemplates' => true]);
    $emailTemplate = new EmailTemplate(['name' => $tag, 'handle' => $tag, 'template' => $tag . '/email']);
    $pdfTemplate = new PdfTemplate(['name' => $tag, 'handle' => $tag, 'template' => $tag . '/pdf']);
    expect(Formie::$plugin->getFormTemplates()->saveTemplate($formTemplate))->toBeTrue()
        ->and(Formie::$plugin->getEmailTemplates()->saveTemplate($emailTemplate))->toBeTrue()
        ->and(Formie::$plugin->getPdfTemplates()->saveTemplate($pdfTemplate))->toBeTrue();
    $source = formie()->form(['handle' => $tag . 'Source'])->singleLineTextField('answer')->create();
    $source->setTemplate($formTemplate);
    $notification = new Notification(['name' => 'Notice', 'handle' => 'notice', 'to' => 'fixture@example.test', 'subject' => 'Local fixture', 'enabled' => false]);
    $notification->setTemplate($emailTemplate);
    $notification->setPdfTemplate($pdfTemplate);
    $source->setNotifications([$notification]);
    expect(Craft::$app->getElements()->saveElement($source))->toBeTrue();
    $source = Form::find()->id($source->id)->status(null)->one();
    $custom = ImportExportHelper::generateFormExport($source);
    $custom['handle'] = $tag . 'Destination';
    $destination = ImportExportHelper::createFromImport($custom)->form;
    $beforeNotification = Formie::$plugin->getNotifications()->getFormNotificationByHandle($destination, 'notice');
    $submission = formie()->submission($destination)->with(['answer' => 'Historical answer 0'])->save();
    $cleared = fn(string $kind): bool => $clear === 'all' || $clear === $kind;
    $notification = Formie::$plugin->getNotifications()->getFormNotificationByHandle($source, 'notice');

    if ($cleared('form')) {
        $source->setTemplate(null);
    }

    if ($cleared('email')) {
        $notification->setTemplate(null);
    }

    if ($cleared('pdf')) {
        $notification->setPdfTemplate(null);
    }

    $source->setNotifications([$notification]);
    expect(Craft::$app->getElements()->saveElement($source))->toBeTrue();
    $source = Form::find()->id($source->id)->status(null)->one();
    $data = ImportExportHelper::generateFormExport($source);

    if ($explicitNull) {
        if ($cleared('form')) {
            $data['formTemplate'] = null;
        }

        if ($cleared('email')) {
            $data['notifications'][0]['emailTemplate'] = null;
        }

        if ($cleared('pdf')) {
            $data['notifications'][0]['pdfTemplate'] = null;
        }
    }

    $data['handle'] = $destination->handle;
    ImportExportHelper::updateFromImport($data, Form::find()->id($destination->id)->status(null)->one());
    $updated = Form::find()->id($destination->id)->status(null)->one();
    $savedNotification = Formie::$plugin->getNotifications()->getFormNotificationByHandle($updated, 'notice');
    expect($updated->templateId)->toBe($cleared('form') ? null : $formTemplate->id)
        ->and($savedNotification->templateId)->toBe($cleared('email') ? null : $emailTemplate->id)
        ->and($savedNotification->pdfTemplateId)->toBe($cleared('pdf') ? null : $pdfTemplate->id)
        ->and($savedNotification->id)->toBe($beforeNotification->id);
    $data['handle'] = $tag . 'Fresh';
    $fresh = ImportExportHelper::createFromImport($data)->form;
    $freshNotification = Formie::$plugin->getNotifications()->getFormNotificationByHandle($fresh, 'notice');
    expect($fresh->templateId)->toBe($updated->templateId)
        ->and($freshNotification->templateId)->toBe($savedNotification->templateId)
        ->and($freshNotification->pdfTemplateId)->toBe($savedNotification->pdfTemplateId);
    $history = Submission::find()->id($submission->id)->status(null)->one();
    expect((int)$history->formId)->toBe((int)$destination->id)
        ->and($history->getFieldValueAsData('answer'))->toBe('Historical answer 0');
})->with(['form', 'email', 'pdf', 'all'])->with([false, true]);
