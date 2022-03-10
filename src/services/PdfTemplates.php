<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\events\PdfEvent;
use verbb\formie\events\PdfRenderOptionsEvent;
use verbb\formie\events\PdfTemplateEvent;
use verbb\formie\helpers\Variables;
use verbb\formie\models\PdfTemplate;
use verbb\formie\records\PdfTemplate as TemplateRecord;

use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\Template;

use Dompdf\Dompdf;
use Dompdf\Options;

use yii\base\Component;
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


    // Private Properties
    // =========================================================================

    /**
     * @var PdfTemplate[]
     */
    private ?array $_templates = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns all templates.
     *
     * @param bool $withTrashed
     * @return PdfTemplate[]
     */
    public function getAllTemplates(bool $withTrashed = false): array
    {
        // Get the caches items if we have them cached, and the request is for non-trashed items
        if ($this->_templates !== null) {
            return $this->_templates;
        }

        $results = $this->_createTemplatesQuery($withTrashed)->all();
        $templates = [];

        foreach ($results as $row) {
            $templates[] = new PdfTemplate($row);
        }

        return $templates;
    }

    /**
     * Returns a template identified by its ID.
     *
     * @param int $id
     * @return PdfTemplate|null
     */
    public function getTemplateById(int $id): ?PdfTemplate
    {
        return ArrayHelper::firstWhere($this->getAllTemplates(), 'id', $id);
    }

    /**
     * Returns a template identified by its handle.
     *
     * @param string $handle
     * @return PdfTemplate|null
     */
    public function getTemplateByHandle(string $handle): ?PdfTemplate
    {
        return ArrayHelper::firstWhere($this->getAllTemplates(), 'handle', $handle, false);
    }

    /**
     * Returns a template identified by its UID.
     *
     * @param string $uid
     * @return PdfTemplate|null
     */
    public function getTemplateByUid(string $uid): ?PdfTemplate
    {
        return ArrayHelper::firstWhere($this->getAllTemplates(), 'uid', $uid, false);
    }

    /**
     * Saves templates in a new order by the list of template IDs.
     *
     * @param int[] $ids
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function reorderTemplates(array $ids): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds('{{%formie_pdftemplates}}', $ids);

        foreach ($ids as $template => $templateId) {
            if (!empty($uidsByIds[$templateId])) {
                $templateUid = $uidsByIds[$templateId];
                $projectConfig->set(self::CONFIG_TEMPLATES_KEY . '.' . $templateUid . '.sortOrder', $template + 1);
            }
        }

        return true;
    }

    /**
     * Saves the template.
     *
     * @param PdfTemplate $template
     * @param bool $runValidation
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
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
            Craft::info('Template not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($isNewTemplate) {
            $templateUid = StringHelper::UUID();
        } else {
            $templateUid = Db::uidById('{{%formie_pdftemplates}}', $template->id);
        }

        // Make sure no templates that are not archived share the handle
        $existingTemplate = $this->getTemplateByHandle($template->handle);

        if ($existingTemplate && (!$template->id || $template->id != $existingTemplate->id)) {
            $template->addError('handle', Craft::t('formie', 'That handle is already in use'));
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        if ($template->dateDeleted) {
            $configData = null;
        } else {
            $configData = [
                'name' => $template->name,
                'handle' => $template->handle,
                'template' => $template->template,
                'filenameFormat' => $template->filenameFormat,
                'sortOrder' => $template->sortOrder ?? 99,
            ];
        }

        $configPath = self::CONFIG_TEMPLATES_KEY . '.' . $templateUid;
        $projectConfig->set($configPath, $configData);

        if ($isNewTemplate) {
            $template->id = Db::idByUid('{{%formie_pdftemplates}}', $templateUid);
        }

        return true;
    }

    /**
     * Handle template change.
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleChangedTemplate(ConfigEvent $event): void
    {
        $templateUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $templateRecord = $this->_getTemplateRecord($templateUid);
            $isNewTemplate = $templateRecord->getIsNewRecord();

            $templateRecord->name = $data['name'];
            $templateRecord->handle = $data['handle'];
            $templateRecord->template = $data['template'];
            $templateRecord->filenameFormat = $data['filenameFormat'];
            $templateRecord->sortOrder = $data['sortOrder'] ?? 99;
            $templateRecord->uid = $templateUid;

            // Save the volume
            $templateRecord->save(false);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterSavePdfTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PDF_TEMPLATE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_PDF_TEMPLATE, new PdfTemplateEvent([
                'template' => $this->getTemplateById($templateRecord->id),
                'isNew' => $isNewTemplate,
            ]));
        }
    }

    /**
     * Delete a template by its id.
     *
     * @param int $id
     * @return bool
     * @throws Throwable
     */
    public function deleteTemplateById(int $id): bool
    {
        $template = $this->getTemplateById($id);

        if (!$template) {
            return false;
        }

        return $this->deleteTemplate($template);
    }

    /**
     * Deletes a pdf template.
     *
     * @param PdfTemplate $template The pdf template
     * @return bool Whether the pdf template was deleted successfully
     */
    public function deleteTemplate(PdfTemplate $template): bool
    {
        // Fire a 'beforeDeletePdfTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_PDF_TEMPLATE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_PDF_TEMPLATE, new PdfTemplateEvent([
                'template' => $template,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_TEMPLATES_KEY . '.' . $template->uid);
        return true;
    }

    /**
     * Handle template being deleted
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleDeletedTemplate(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];

        $template = $this->getTemplateByUid($uid);

        // Fire a 'beforeApplyPdfTemplateDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_PDF_TEMPLATE_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_PDF_TEMPLATE_DELETE, new PdfTemplateEvent([
                'template' => $template,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $templateRecord = $this->_getTemplateRecord($uid);

            // Save the template
            $templateRecord->softDelete();

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
        $settings = Formie::$plugin->getSettings();
        $view = Craft::$app->getView();

        $form = $submission->getForm();

        // Render the body content for the notification
        $parsedContent = Variables::getParsedValue($notification->getParsedContent(), $submission, $form, $notification);

        $variables = [
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

            $html = $message->getHtmlBody();
        }

        $dompdf = new Dompdf();

        // Set the config options
        $pathService = Craft::$app->getPath();
        $dompdfTempDir = $pathService->getTempPath() . DIRECTORY_SEPARATOR . 'formie_dompdf';
        $dompdfFontCache = $pathService->getCachePath() . DIRECTORY_SEPARATOR . 'formie_dompdf';
        $dompdfLogFile = $pathService->getLogPath() . DIRECTORY_SEPARATOR . 'formie_dompdf.htm';

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
        $options->setLogOutputFile($dompdfLogFile);
        $options->setIsRemoteEnabled(true);

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

    /**
     * Returns a Query object prepped for retrieving templates.
     *
     * @param bool $withTrashed
     * @return Query
     */
    private function _createTemplatesQuery(bool $withTrashed = false): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'template',
                'sortOrder',
                'dateDeleted',
                'uid'
            ])
            ->orderBy('sortOrder')
            ->from(['{{%formie_pdftemplates}}']);

        if (!$withTrashed) {
            $query->where(['dateDeleted' => null]);
        }

        return $query;
    }

    /**
     * Gets a template record by uid.
     *
     * @param string $uid
     * @return TemplateRecord
     */
    private function _getTemplateRecord(string $uid): TemplateRecord
    {
        /** @var TemplateRecord $template */
        if ($template = TemplateRecord::findWithTrashed()->where(['uid' => $uid])->one()) {
            return $template;
        }

        return new TemplateRecord();
    }
}
