<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\Settings;
use verbb\formie\models\Support;
use verbb\formie\records\Form as FormRecord;
use verbb\formie\records\Notification as NotificationRecord;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\i18n\Locale;
use craft\web\Controller;
use craft\web\UploadedFile;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use DateTime;
use ZipArchive;

use GuzzleHttp\Exception\RequestException;

class SupportController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function actionIndex(Support $support = null, $error = null): Response
    {
        $settings = Formie::$plugin->getSettings();
        $variables = compact('settings', 'support', 'error');

        return $this->renderTemplate('formie/settings/support', $variables);
    }

    /**
     * @inheritdoc
     */
    public function actionSendSupportRequest()
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
        $support->attachments = UploadedFile::getInstanceByName('attachments');

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
        $message .= PHP_EOL . '------------------------------' . PHP_EOL .  PHP_EOL;
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
                        'recursive' => false
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
                $formInfo = $this->_prepareSqlFormSettings($support->formId);
                $tempFileForm = $backupPath . '/' . StringHelper::toLowerCase('formie_' . gmdate('ymd_His') . '.sql');

                FileHelper::writeToFile($tempFileForm, $formInfo . PHP_EOL);

                $zip->addFile($tempFileForm, 'backups/' . pathinfo($tempFileForm, PATHINFO_BASENAME));
            } catch (\Throwable $e) {
                $noteError = "\n\nError adding database to help request: `" . $e->getMessage() . ":" . $e->getLine() . "`.";
                $requestParams['note'] .= $noteError;

                Formie::error($noteError);
            }

            //
            // Attachments
            //

            if ($support->attachments) {
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
        } catch (\Throwable $e) {
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
            $guzzleClient->post('https://support.verbb.io/api/get-help', [ 'json' => $requestParams ]);
        } catch (\Throwable $e) {
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


    // Private Methods
    // =========================================================================
    
    /**
     * @inheritdoc
     */
    private function _prepareSqlFormSettings($id)
    {
        $sql = '';

        //
        // Form
        //

        $tableName = Craft::$app->db->getSchema()->getRawTableName(FormRecord::tableName());

        $row = FormRecord::find()
            ->select(['*'])
            ->where(['id' => $id])
            ->asArray()
            ->one();

        // Remove the id col
        $formId = ArrayHelper::remove($row, 'id');

        foreach ($row as $columnName => $value) {
            if ($value === null) {
                $row[$columnName] = 'NULL';
            } else {
                $row[$columnName] = Craft::$app->db->quoteValue($value);
            }
        }

        $attrs = array_map([Craft::$app->db, 'quoteColumnName'], array_keys($row));
        $sql .= 'INSERT INTO ' . $tableName . ' (' . implode(', ', $attrs) . ') VALUES ' . PHP_EOL;
        $sql .= '(' . implode(', ', $row) . ');' . PHP_EOL;

        //
        // Notifications
        //

        $tableName = Craft::$app->db->getSchema()->getRawTableName(NotificationRecord::tableName());
        
        $notifications = NotificationRecord::find()
            ->select(['*'])
            ->where(['formId' => $formId])
            ->asArray()
            ->all();

        foreach ($notifications as $columnName => $row) {
            // Remove some attributes
            ArrayHelper::remove($row, 'id');

            $attrs = array_map([Craft::$app->db, 'quoteColumnName'], array_keys($row));
            $sql .= 'INSERT INTO ' . $tableName . ' (' . implode(', ', $attrs) . ') VALUES ' . PHP_EOL;
            $sql .= '(' . implode(', ', $row) . ');' . PHP_EOL . PHP_EOL;
        }

        return $sql;
    }
}
