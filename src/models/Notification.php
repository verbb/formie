<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\RichTextHelper;

use Craft;
use craft\base\Model;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use craft\web\View;

use Twig\Error\LoaderError;

use Exception;

class Notification extends Model
{
    // Constants
    // =========================================================================

    public const RECIPIENTS_EMAIL = 'email';
    public const RECIPIENTS_CONDITIONS = 'conditions';


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $formId = null;
    public ?int $templateId = null;
    public ?int $pdfTemplateId = null;
    public ?string $name = null;
    public ?bool $enabled = null;
    public ?string $subject = null;
    public string $recipients = self::RECIPIENTS_EMAIL;
    public ?string $to = null;
    public ?array $toConditions = null;
    public ?string $cc = null;
    public ?string $bcc = null;
    public ?string $replyTo = null;
    public ?string $replyToName = null;
    public ?string $from = null;
    public ?string $fromName = null;
    public ?string $sender = null;
    public ?string $content = null;
    public ?bool $attachFiles = null;
    public ?string $attachPdf = null;
    public ?array $attachAssets = null;
    public ?bool $enableConditions = null;
    public ?array $conditions = null;
    public ?string $uid = null;


    // Private Properties
    // =========================================================================

    private ?EmailTemplate $_template = null;
    private ?PdfTemplate $_pdfTemplate = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        if (!$this->templateId) {
            $this->templateId = null;
        }

        if (!$this->pdfTemplateId) {
            $this->pdfTemplateId = null;
        }

        // Cast some properties. Doesn't play well with JS otherwise.
        $this->attachFiles = (bool)$this->attachFiles;
        $this->enableConditions = (bool)$this->enableConditions;
        $this->attachPdf = (bool)$this->attachPdf;

        // Normalise TipTap v1 nodes
        // TODO: can be removed at the next breakpoint
        if (is_string($this->content)) {
            $this->content = RichTextHelper::normalizeNodes($this->content);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'subject'], 'required'];
        $rules[] = [['name', 'subject', 'to', 'cc', 'bcc', 'replyTo', 'replyToName', 'from', 'fromName', 'sender'], 'string'];
        $rules[] = [['formId', 'templateId', 'pdfTemplateId'], 'number', 'integerOnly' => true];

        $rules[] = [
            ['to'], 'required', 'when' => function($model) {
                return $model->recipients === self::RECIPIENTS_EMAIL;
            },
        ];

        return $rules;
    }

    /**
     * Parses the JSON content and renders it as HTML.
     *
     * @return string
     */
    public function getParsedContent(): string
    {
        return RichTextHelper::getHtmlContent($this->content, null, false);
    }

    /**
     * Returns the notification's recipients.
     *
     * @param $submission
     * @return string
     */
    public function getToEmail($submission): ?string
    {
        if ($this->recipients === self::RECIPIENTS_EMAIL) {
            return $this->to;
        }

        if ($this->recipients === self::RECIPIENTS_CONDITIONS) {
            $conditionSettings = $this->toConditions ?? [];

            if ($conditionSettings) {
                $toRecipients = $conditionSettings['toRecipients'] ?? [];

                $results = ConditionsHelper::evaluateConditions($toRecipients, $submission, function($result, $condition) {
                    if ($result) {
                        return $condition['email'];
                    }
                });

                return implode(',', $results);
            }
        }

        return null;
    }

    /**
     * Returns the template path for an email component.
     *
     * @param array|string $components can be 'form', 'page' or ['field1', 'field2'].
     * @param array $variables any variables to use with `$view->renderTemplate()`.
     * @return string
     * @throws Exception
     * @throws LoaderError
     */
    public function renderTemplate(array|string $components, array $variables = []): string
    {
        $view = Craft::$app->getView();
        
        // Normalise the components to allow for a single component
        if (!is_array($components)) {
            $components = [$components];
        }

        // Check for email templates, and a custom set of templates
        if (($template = $this->getTemplate()) && $template->template) {
            // Find the first available, resolved template in potential multiple components
            foreach ($components as $component) {
                $path = $template->template . DIRECTORY_SEPARATOR . $component;

                // Ensure that the path exists in site templates
                if ($view->doesTemplateExist($path, View::TEMPLATE_MODE_SITE)) {
                    return $view->renderTemplate($path, $variables, View::TEMPLATE_MODE_SITE);
                }
            }
        }

        // Otherwise, fall bacl on the default Formie templates.
        // Find the first available, resolved template in potential multiple components
        foreach ($components as $component) {
            $templatePath = 'formie/_special/email-template' . DIRECTORY_SEPARATOR . $component;

            // Note we need to include `.html` for default templates, because of users potentially setting `defaultTemplateExtensions`
            // which would be unable to find our templates if they disallow `.html`.
            // Check for `form.html` or `form/index.html` because we have to try resolving on our own...
            $paths = [
                $templatePath . '.html',
                $templatePath . DIRECTORY_SEPARATOR . 'index.html',
            ];

            foreach ($paths as $path) {
                if ($view->doesTemplateExist($path, View::TEMPLATE_MODE_CP)) {
                    return $view->renderTemplate($path, $variables, View::TEMPLATE_MODE_CP);
                }
            }
        }

        return '';
    }

    /**
     * Returns the notification's template, or null if not set.
     *
     * @return EmailTemplate|null
     */
    public function getTemplate(): ?EmailTemplate
    {
        if (!$this->_template) {
            if ($this->templateId) {
                $this->_template = Formie::$plugin->getEmailTemplates()->getTemplateById($this->templateId);
            } else {
                return null;
            }
        }

        return $this->_template;
    }

    /**
     * Sets the email template.
     *
     * @param EmailTemplate|null $template
     */
    public function setTemplate(?EmailTemplate $template): void
    {
        if ($template) {
            $this->_template = $template;
            $this->templateId = $template->id;
        } else {
            $this->_template = $this->templateId = null;
        }
    }

    /**
     * Returns the notification's PDF template, or null if not set.
     *
     * @return PdfTemplate|null
     */
    public function getPdfTemplate(): ?PdfTemplate
    {
        if (!$this->_pdfTemplate) {
            if ($this->pdfTemplateId) {
                $this->_pdfTemplate = Formie::$plugin->getPdfTemplates()->getTemplateById($this->pdfTemplateId);
            } else {
                return null;
            }
        }

        return $this->_pdfTemplate;
    }

    /**
     * Sets the PDF template.
     *
     * @param PdfTemplate|null $template
     */
    public function setPdfTemplate(?PdfTemplate $template): void
    {
        if ($template) {
            $this->_pdfTemplate = $template;
            $this->pdfTemplateId = $template->id;
        } else {
            $this->_pdfTemplate = $this->pdfTemplateId = null;
        }
    }

    public function getAssetAttachments(): array
    {
        if ($this->attachAssets) {
            if ($ids = ArrayHelper::getColumn($this->attachAssets, 'id')) {
                return Asset::find()->id($ids)->all();
            }
        }

        return [];
    }

    public function getPlaceholder(): string
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        return Craft::t('formie', $settings->emptyValuePlaceholder);
    }
}
