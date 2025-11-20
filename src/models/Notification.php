<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\records\Notification as NotificationRecord;

use Craft;
use craft\base\Model;
use craft\elements\Asset;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
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
    public ?string $handle = null;
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
    public array $customSettings = [];
    public ?string $uid = null;

    private ?EmailTemplate $_template = null;
    private ?PdfTemplate $_pdfTemplate = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Normalize the options
        unset($config['attachAssetsHtml']);

        parent::__construct($config);
    }

    public function __toString()
    {
        return (string)$this->name;
    }

    public function getParsedContent(): string
    {
        return RichTextHelper::getHtmlContent($this->content, null, false);
    }

    public function getToEmail(Submission $submission): ?string
    {
        if ($this->recipients === self::RECIPIENTS_EMAIL) {
            return $this->to;
        }

        if ($this->recipients === self::RECIPIENTS_CONDITIONS) {
            $conditionSettings = $this->toConditions ?? [];

            if ($conditionSettings) {
                $toRecipients = $conditionSettings['toRecipients'] ?? [];

                // Normalize conditions for emails (just the emails, not the matching string)
                if ($toRecipients && is_array($toRecipients)) {
                    foreach ($toRecipients as $key => $toRecipient) {
                        // Only normalize if a static email (.env variables and field refs should stay)
                        if (str_contains($toRecipient['email'], '@')) {
                            $toRecipients[$key]['email'] = strtolower($toRecipient['email']);
                        }
                    }
                }

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

    public function getStatusCondition(Submission $submission): ?string
    {
        $conditionSettings = $this->conditions ?? [];

        if ($conditionSettings) {
            $conditions = $conditionSettings['conditions'] ?? [];

            if ($condition = ArrayHelper::firstWhere($conditions, 'field', '{submission:status}')) {
                return $condition['value'];
            };
        }

        return null;
    }

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

        // Otherwise, fall back on the default Formie templates.
        // Find the first available, resolved template in potential multiple components
        foreach ($components as $component) {
            $templatePath = 'formie/_special/email-template' . DIRECTORY_SEPARATOR . $component;

            // Note we need to include `.html` for default templates, because of users potentially setting `defaultTemplateExtensions`
            // which would be unable to find our templates if they disallow `.html`.
            // Check for `form.html` or `form/index.html` because we have to try resolving on our own...
            $paths = [
                $templatePath . '.html',
                $templatePath . DIRECTORY_SEPARATOR . 'index.html',

                // Also include searching the component path itself, for custom fields that don't resolve to Formie
                $component,
            ];

            foreach ($paths as $path) {
                if ($view->doesTemplateExist($path, View::TEMPLATE_MODE_CP)) {
                    return $view->renderTemplate($path, $variables, View::TEMPLATE_MODE_CP);
                }
            }
        }

        return '';
    }

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

    public function setTemplate(?EmailTemplate $template): void
    {
        if ($template) {
            $this->_template = $template;
            $this->templateId = $template->id;
        } else {
            $this->_template = $this->templateId = null;
        }
    }

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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'subject', 'handle'], 'required'];
        $rules[] = [['name', 'subject', 'to', 'cc', 'bcc', 'replyTo', 'replyToName', 'from', 'fromName', 'sender'], 'string'];
        $rules[] = [['formId', 'templateId', 'pdfTemplateId'], 'number', 'integerOnly' => true];

        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [['handle'], UniqueValidator::class, 'targetClass' => NotificationRecord::class, 'targetAttribute' => ['handle', 'formId']];
        $rules[] = [['handle'], 'string', 'max' => 255];

        $rules[] = [
            ['to'], 'required', 'when' => function($model) {
                return $model->recipients === self::RECIPIENTS_EMAIL;
            },
        ];

        return $rules;
    }
}
