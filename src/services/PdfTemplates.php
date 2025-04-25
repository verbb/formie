<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\events\PdfEvent;
use verbb\formie\events\PdfRenderOptionsEvent;
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\models\PdfTemplate;
use verbb\formie\models\Settings;
use verbb\formie\records\PdfTemplate as TemplateRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Template;

use Dompdf\Dompdf;
use Dompdf\Options;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

use Throwable;

class PdfTemplates extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_PDF_TEMPLATE = 'beforeSavePdfTemplate';
    public const EVENT_AFTER_SAVE_PDF_TEMPLATE = 'afterSavePdfTemplate';
    public const EVENT_BEFORE_DELETE_PDF_TEMPLATE = 'beforeDeletePdfTemplate';
    public const EVENT_BEFORE_APPLY_PDF_TEMPLATE_DELETE = 'beforeApplyPdfTemplateDelete';
    public const EVENT_AFTER_DELETE_PDF_TEMPLATE = 'afterDeletePdfTemplate';

    public const EVENT_BEFORE_RENDER_PDF = 'beforeRenderPdf';
    public const EVENT_AFTER_RENDER_PDF = 'afterRenderPdf';
    public const EVENT_MODIFY_RENDER_OPTIONS = 'modifyRenderOptions';

    public const CONFIG_TEMPLATES_KEY = 'formie.pdfTemplates';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_templates = null;


    // Public Methods
    // =========================================================================

    public function getAllTemplates(bool $withTrashed = false): array
    {
        return $this->_templates()->all();
    }

    public function getTemplateById(int $id): ?PdfTemplate
    {
        return $this->_templates()->firstWhere('id', $id);
    }

    public function getTemplateByHandle(string $handle): ?PdfTemplate
    {
        return $this->_templates()->firstWhere('handle', $handle, true);
    }

    public function getTemplateByUid(string $uid): ?PdfTemplate
    {
        return $this->_templates()->firstWhere('uid', $uid, true);
    }

    public function reorderTemplates(array $ids): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds(Table::FORMIE_PDF_TEMPLATES, $ids);

        foreach ($ids as $template => $templateId) {
            if (!empty($uidsByIds[$templateId])) {
                $templateUid = $uidsByIds[$templateId];
                $projectConfig->set(self::CONFIG_TEMPLATES_KEY . '.' . $templateUid . '.sortOrder', $template + 1);
            }
        }

        return true;
    }

    public function saveTemplate(PdfTemplate $template, bool $runValidation = true): bool
    {
        $isNewTemplate = !(bool)$template->id;

        // Fire a 'beforeSavePdfTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_PDF_TEMPLATE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_PDF_TEMPLATE, new PdfTemplateEvent([
                'template' => $template,
                'isNew' => $isNewTemplate,
            ]));
        }

        if ($runValidation && !$template->validate()) {
            Formie::info('Template not saved due to validation error.');

            return false;
        }

        if ($isNewTemplate) {
            $template->uid = StringHelper::UUID();

            $template->sortOrder = (new Query())
                ->from([Table::FORMIE_PDF_TEMPLATES])
                ->max('[[sortOrder]]') + 1;
        } else if (!$template->uid) {
            $template->uid = Db::uidById(Table::FORMIE_PDF_TEMPLATES, $template->id);
        }

        // Make sure no templates that are not archived share the handle
        $existingTemplate = $this->getTemplateByHandle($template->handle);

        if ($existingTemplate && (!$template->id || $template->id != $existingTemplate->id)) {
            $template->addError('handle', Craft::t('formie', 'That handle is already in use'));
            return false;
        }

        $configPath = self::CONFIG_TEMPLATES_KEY . '.' . $template->uid;
        Craft::$app->getProjectConfig()->set($configPath, $template->getConfig(), "Save the “{$template->handle}” PDF template");

        if ($isNewTemplate) {
            $template->id = Db::idByUid(Table::FORMIE_PDF_TEMPLATES, $template->uid);
        }

        return true;
    }

    public function handleChangedTemplate(ConfigEvent $event): void
    {
        $templateUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $templateRecord = $this->_getTemplateRecord($templateUid, true);
            $isNewTemplate = $templateRecord->getIsNewRecord();

            $templateRecord->name = $data['name'];
            $templateRecord->handle = $data['handle'];
            $templateRecord->template = $data['template'];
            $templateRecord->filenameFormat = $data['filenameFormat'];
            $templateRecord->sortOrder = $data['sortOrder'];
            $templateRecord->uid = $templateUid;

            if ($wasTrashed = (bool)$templateRecord->dateDeleted) {
                $templateRecord->restore();
            } else {
                $templateRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_templates = null;

        // Fire an 'afterSavePdfTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PDF_TEMPLATE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_PDF_TEMPLATE, new PdfTemplateEvent([
                'template' => $this->getTemplateById($templateRecord->id),
                'isNew' => $isNewTemplate,
            ]));
        }
    }

    public function deleteTemplateById(int $id): bool
    {
        $template = $this->getTemplateById($id);

        if (!$template) {
            return false;
        }

        return $this->deleteTemplate($template);
    }

    public function deleteTemplate(PdfTemplate $template): bool
    {
        // Fire a 'beforeDeletePdfTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_PDF_TEMPLATE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_PDF_TEMPLATE, new PdfTemplateEvent([
                'template' => $template,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_TEMPLATES_KEY . '.' . $template->uid, "Delete PDF template “{$template->handle}”");
        return true;
    }

    public function handleDeletedTemplate(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $templateRecord = $this->_getTemplateRecord($uid);

        if ($templateRecord->getIsNewRecord()) {
            return;
        }

        $template = $this->getTemplateById($templateRecord->id);

        // Fire a 'beforeApplyPdfTemplateDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_PDF_TEMPLATE_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_PDF_TEMPLATE_DELETE, new PdfTemplateEvent([
                'template' => $template,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::FORMIE_PDF_TEMPLATES, ['id' => $templateRecord->id])
                ->execute();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterDeletePdfTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_PDF_TEMPLATE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_PDF_TEMPLATE, new PdfTemplateEvent([
                'template' => $template,
            ]));
        }
    }

    public function renderPdf($pdfTemplate, $submission, $notification): string
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();
        $view = Craft::$app->getView();

        $form = $submission->getForm();

        // Render the body content for the notification
        $parsedContent = Variables::getParsedValue($notification->getParsedContent(), $submission, $form, $notification, true);

        $variables = [
            'form' => $form,
            'submission' => $submission,
            'notification' => $notification,
            'contentHtml' => Template::raw($parsedContent),
        ];

        // Trigger a 'beforeRenderPdf' event
        $event = new PdfEvent([
            'template' => $pdfTemplate->template ?? null,
            'variables' => $variables,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_PDF, $event);

        if ($event->pdf !== null) {
            return $event->pdf;
        }

        $variables = $event->variables;
        $template = $event->template;

        // If a custom template is supplied, use that, otherwise just use the email notification HTML
        if ($template) {
            $oldTemplatesPath = $view->getTemplatesPath();

            // We need to do a little more work here to deal with a template, if picked
            $view->setTemplatesPath(Craft::$app->getPath()->getSiteTemplatesPath());

            if (!$view->doesTemplateExist($template)) {
                throw new Exception('PDF template file does not exist.');
            }

            try {
                $html = $view->renderTemplate($template, $variables);
            } catch (\Exception $e) {
                Formie::error('An error occurred while generating this PDF: ' . $e->getMessage());

                // Set the pdf html to the render error.
                Craft::$app->getErrorHandler()->logException($e);
                $html = Craft::t('formie', 'An error occurred while generating this PDF.');
            }

            // Restore the original template path
            $view->setTemplatesPath($oldTemplatesPath);
        } else {
            $emailRender = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
            $message = $emailRender['email'] ?? '';

            $html = $message->getSymfonyEmail()->getHtmlBody();
        }

        $dompdf = new Dompdf();

        // Set the config options
        $tempPath = Craft::$app->getPath()->getTempPath();
        $dompdfTempDir = $tempPath . DIRECTORY_SEPARATOR . 'formie_dompdf';
        $dompdfFontCache = $tempPath . DIRECTORY_SEPARATOR . 'formie_dompdf';
        $dompdfLogFile = $tempPath . DIRECTORY_SEPARATOR . 'formie_dompdf.htm';

        // Ensure directories are created
        FileHelper::createDirectory($dompdfTempDir);
        FileHelper::createDirectory($dompdfFontCache);

        if (!FileHelper::isWritable($dompdfLogFile)) {
            throw new ErrorException("Unable to write to file: $dompdfLogFile");
        }

        if (!FileHelper::isWritable($dompdfFontCache)) {
            throw new ErrorException("Unable to write to folder: $dompdfFontCache");
        }

        if (!FileHelper::isWritable($dompdfTempDir)) {
            throw new ErrorException("Unable to write to folder: $dompdfTempDir");
        }

        $options = new Options();
        $options->setTempDir($dompdfTempDir);
        $options->setFontCache($dompdfFontCache);
        $options->setFontDir($dompdfFontCache);
        $options->setLogOutputFile($dompdfLogFile);
        $options->setIsRemoteEnabled(true);
        $options->setDefaultFont('sans-serif');

        // Set additional render options
        if ($this->hasEventHandlers(self::EVENT_MODIFY_RENDER_OPTIONS)) {
            $this->trigger(self::EVENT_MODIFY_RENDER_OPTIONS, new PdfRenderOptionsEvent([
                'options' => $options,
            ]));
        }

        // Set the options
        $dompdf->setOptions($options);

        // Paper Size and Orientation
        $pdfPaperSize = $settings->pdfPaperSize;
        $pdfPaperOrientation = $settings->pdfPaperOrientation;
        $dompdf->setPaper($pdfPaperSize, $pdfPaperOrientation);

        $dompdf->loadHtml($html);
        $dompdf->render();

        // Trigger an 'afterRenderPdf' event
        $afterEvent = new PdfEvent([
            'template' => $template,
            'variables' => $variables,
            'pdf' => $dompdf->output(),
        ]);
        $this->trigger(self::EVENT_AFTER_RENDER_PDF, $afterEvent);

        return $afterEvent->pdf;
    }


    // Private Methods
    // =========================================================================

    private function _templates(): MemoizableArray
    {
        if (!isset($this->_templates)) {
            $templates = [];

            foreach ($this->_createTemplatesQuery()->all() as $result) {
                $templates[] = new PdfTemplate($result);
            }

            $this->_templates = new MemoizableArray($templates);
        }

        return $this->_templates;
    }

    private function _createTemplatesQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'template',
                'filenameFormat',
                'sortOrder',
                'dateDeleted',
                'uid',
            ])
            ->from([Table::FORMIE_PDF_TEMPLATES])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);

        return $query;
    }

    private function _getTemplateRecord(string $uid, bool $withTrashed = false): TemplateRecord
    {
        $query = $withTrashed ? TemplateRecord::findWithTrashed() : TemplateRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new TemplateRecord();
    }
}
