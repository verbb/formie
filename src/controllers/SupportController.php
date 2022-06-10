<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\models\Settings;
use verbb\formie\models\Support;

use Craft;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;
use craft\web\UploadedFile;
use craft\web\View;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\web\Response;

use ZipArchive;

use GuzzleHttp\Exception\RequestException;

use Throwable;

class SupportController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(Support $support = null, $error = null): Response
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $variables = compact('settings', 'support', 'error');

        return $this->renderTemplate('formie/settings/support', $variables);
    }

    public function actionSendSupportRequest(): ?Response
    {
        $this->requirePostRequest();

        App::maxPowerCaptain();

        $request = Craft::$app->getRequest();
        $plugins = Craft::$app->getPlugins();
        $tempFolder = Craft::$app->getPath()->getTempPath();
        $backupPath = Craft::$app->getPath()->getDbBackupPath();
        $user = Craft::$app->getUser()->getIdentity();

        $support = new Support();
        $support->fromEmail = $request->getParam('fromEmail');
        $support->formId = $request->getParam('formId');
        $support->message = trim($request->getParam('message'));
        $support->attachments = UploadedFile::getInstancesByName('attachments');

        if (!$support->validate()) {
            Craft::$app->getSession()->setError(Craft::t('app', 'An error occurred.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'support' => $support,
            ]);

            return null;
        }

        $form = Formie::$plugin->getForms()->getFormById($support->formId);

        // Add some extra info about this install
        $message = $support->message . PHP_EOL;
        $message .= PHP_EOL . '------------------------------' . PHP_EOL . PHP_EOL;
        $message .= 'Craft ' . Craft::$app->getEditionName() . ' ' . Craft::$app->getVersion() . PHP_EOL;
        $message .= 'Formie: ' . Formie::$plugin->getVersion() . PHP_EOL;
        $message .= 'License: ' . $plugins->getPluginLicenseKey('formie') . ' - ' . $plugins->getPluginLicenseKeyStatus('formie') . PHP_EOL;
        $message .= 'Domain: ' . Craft::$app->getRequest()->getHostInfo();

        $requestParams = [
            'firstName' => $user->getFriendlyName(),
            'lastName' => $user->lastName ?: 'Doe',
            'email' => $support->fromEmail,
            'subject' => 'Formie Support',
            'note' => $message,
        ];

        $zipPath = $tempFolder . '/' . StringHelper::UUID() . '.zip';

        try {
            $tempFileForm = null;

            // Create the zip
            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                throw new Exception('Cannot create zip at ' . $zipPath . '.');
            }

            // Composer files
            try {
                $composerService = Craft::$app->getComposer();
                $zip->addFile($composerService->getJsonPath(), 'composer.json');

                if (($composerLockPath = $composerService->getLockPath()) !== null) {
                    $zip->addFile($composerLockPath, 'composer.lock');
                }
            } catch (Exception $e) {
                // that's fine
            }

            //
            // Attached just the Formie log
            //
            $logPath = Craft::$app->getPath()->getLogPath();

            if (is_dir($logPath)) {
                try {
                    $logFiles = FileHelper::findFiles($logPath, [
                        'only' => ['formie.log*'],
                        'recursive' => false,
                    ]);
                } catch (ErrorException $e) {
                    $logFiles = [];
                }

                foreach ($logFiles as $logFile) {
                    $zip->addFile($logFile, 'logs/' . pathinfo($logFile, PATHINFO_BASENAME));
                }
            }

            //
            // Add the form
            //
            try {
                $formExport = ImportExportHelper::generateFormExport($form);
                $json = Json::encode($formExport, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);

                $tempFileForm = $backupPath . '/' . StringHelper::toLowerCase('formie_' . gmdate('ymd_His') . '.json');
                FileHelper::writeToFile($tempFileForm, $json);

                $zip->addFile($tempFileForm, pathinfo($tempFileForm, PATHINFO_BASENAME));
            } catch (Throwable $e) {
                $noteError = "\n\nError adding export to help request: `" . $e->getMessage() . ":" . $e->getLine() . "`.";
                $requestParams['note'] .= $noteError;

                Formie::error($noteError);
            }

            //
            // Form/Email/PDF Templates
            //
            try {
                // Form template
                if (($template = $form->getTemplate()) && $template->useCustomTemplates && $template->template) {
                    $templatePath = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . $template->template;
                    $destPath = 'form-template';

                    if (is_dir($templatePath)) {
                        FileHelper::addFilesToZip($zip, $templatePath, $destPath);
                    } else {
                        $templateFile = Craft::$app->getView()->resolveTemplate($template->template, View::TEMPLATE_MODE_SITE);
                        $zip->addFile($templateFile, $destPath . DIRECTORY_SEPARATOR . pathinfo($templateFile, PATHINFO_BASENAME));
                    }
                }

                // Email/PDF templates
                foreach ($form->getNotifications() as $notification) {
                    $notificationHandle = StringHelper::toKebabCase($notification->name);

                    if (($template = $notification->getTemplate()) && $template->template) {
                        $templatePath = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . $template->template;
                        $destPath = 'notifications' . DIRECTORY_SEPARATOR . $notificationHandle . DIRECTORY_SEPARATOR . 'email-template';

                        if (is_dir($templatePath)) {
                            FileHelper::addFilesToZip($zip, $templatePath, $destPath);
                        } else {
                            $templateFile = Craft::$app->getView()->resolveTemplate($template->template, View::TEMPLATE_MODE_SITE);
                            $zip->addFile($templateFile, $destPath . DIRECTORY_SEPARATOR . pathinfo($templateFile, PATHINFO_BASENAME));
                        }
                    }

                    if (($template = $notification->getPdfTemplate()) && $template->template) {
                        $templatePath = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . $template->template;
                        $destPath = 'notifications' . DIRECTORY_SEPARATOR . $notificationHandle . DIRECTORY_SEPARATOR . 'pdf-template';

                        if (is_dir($templatePath)) {
                            FileHelper::addFilesToZip($zip, $templatePath, $destPath);
                        } else {
                            $templateFile = Craft::$app->getView()->resolveTemplate($template->template, View::TEMPLATE_MODE_SITE);
                            $zip->addFile($templateFile, $destPath . DIRECTORY_SEPARATOR . pathinfo($templateFile, PATHINFO_BASENAME));
                        }
                    }
                }
            } catch (Throwable $e) {
                $noteError = "\n\nError adding template to help request: `" . $e->getMessage() . ":" . $e->getLine() . "`.";
                $requestParams['note'] .= $noteError;

                Formie::error($noteError);
            }

            //
            // Attachments
            //

            if ($support->attachments && is_array($support->attachments)) {
                foreach ($support->attachments as $attachment) {
                    $zip->addFile($attachment->tempName, $attachment->name);
                }
            }

            // Close and attach the zip
            $zip->close();
            $requestParams['filename'] = 'formie-support-' . StringHelper::UUID() . '.zip';
            $requestParams['fileMimeType'] = 'application/zip';
            $requestParams['fileBody'] = base64_encode(file_get_contents($zipPath));

            // Remove the temp files we've created
            if (is_file($tempFileForm)) {
                FileHelper::unlink($tempFileForm);
            }
        } catch (Throwable $e) {
            Formie::log('Tried to attach debug logs to a support request and something went horribly wrong: `' . $e->getMessage() . ':' . $e->getLine() . '`.');

            // There was a problem zipping, so reset the params and just send the email without the attachment.
            $requestParams['note'] .= "\n\nError attaching zip: `" . $e->getMessage() . ":" . $e->getLine() . "`.";
        }

        $guzzleClient = Craft::createGuzzleClient([
            'timeout' => 120,
            'connect_timeout' => 120,
            'verify' => false,
        ]);

        try {
            $guzzleClient->post('https://support.verbb.io/api/get-help', ['json' => $requestParams]);
        } catch (Throwable $e) {
            $messageText = $e->getMessage();

            // Check for Guzzle errors, which are truncated in the exception `getMessage()`.
            if ($e instanceof RequestException && $e->getResponse()) {
                $messageText = (string)$e->getResponse()->getBody()->getContents();
            }

            $message = Craft::t('formie', 'Support request error: “{message}” {file}:{line}', [
                'message' => $messageText,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Formie::error($message);

            Craft::$app->getSession()->setError(Craft::t('formie', 'An error occurred.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'support' => $support,
                'error' => $message,
            ]);

            return null;
        }

        // Delete the zip file
        if (is_file($zipPath)) {
            FileHelper::unlink($zipPath);
        }

        Craft::$app->getSession()->setNotice(Craft::t('formie', 'Support request sent successfully.'));

        return $this->redirectToPostedUrl();
    }
}
