<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\RichTextHelper;

use Craft;
use craft\base\Model;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\View;

use yii\behaviors\AttributeTypecastBehavior;

class Notification extends Model
{
    // Public Properties
    // =========================================================================

    public $id;
    public $formId;
    public $templateId;
    public $pdfTemplateId;
    public $name;
    public $enabled;
    public $subject;
    public $recipients;
    public $to;
    public $toConditions;
    public $cc;
    public $bcc;
    public $replyTo;
    public $replyToName;
    public $from;
    public $fromName;
    public $content;
    public $attachFiles;
    public $attachPdf;
    public $attachAssets;
    public $enableConditions;
    public $conditions;
    public $uid;


    // Private Properties
    // =========================================================================

    private $_template;
    private $_pdfTemplate;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if (!$this->templateId) {
            $this->templateId = null;
        }

        if (!$this->pdfTemplateId) {
            $this->pdfTemplateId = null;
        }

        // Cast some properties. Doesn't play with with JS otherwise.
        $this->attachFiles = (bool)$this->attachFiles;
        $this->enableConditions = (bool)$this->enableConditions;
        $this->attachPdf = (bool)$this->attachPdf;
    }

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['typecast'] = [
            'class' => AttributeTypecastBehavior::class,
            'attributeTypes' => [
                'id' => AttributeTypecastBehavior::TYPE_INTEGER,
                'formId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'templateId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'pdfTemplateId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'name' => AttributeTypecastBehavior::TYPE_STRING,
                'enabled' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'subject' => AttributeTypecastBehavior::TYPE_STRING,
                'recipients' => AttributeTypecastBehavior::TYPE_STRING,
                'to' => AttributeTypecastBehavior::TYPE_STRING,
                'toConditions' => AttributeTypecastBehavior::TYPE_STRING,
                'cc' => AttributeTypecastBehavior::TYPE_STRING,
                'bcc' => AttributeTypecastBehavior::TYPE_STRING,
                'replyTo' => AttributeTypecastBehavior::TYPE_STRING,
                'replyToName' => AttributeTypecastBehavior::TYPE_STRING,
                'from' => AttributeTypecastBehavior::TYPE_STRING,
                'fromName' => AttributeTypecastBehavior::TYPE_STRING,
                'attachFiles' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'attachPdf' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'enableConditions' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'conditions' => AttributeTypecastBehavior::TYPE_STRING,
                'uid' => AttributeTypecastBehavior::TYPE_STRING,
            ]
        ];

        return $behaviors;
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
        $rules[] = [['name', 'subject', 'to', 'cc', 'bcc', 'replyTo', 'replyToName', 'from', 'fromName'], 'string'];
        $rules[] = [['formId', 'templateId', 'pdfTemplateId'], 'number', 'integerOnly' => true];

        $rules[] = [['to'], 'required', 'when' => function($model) {
            return $model->recipients === 'email';
        }];

        return $rules;
    }

    /**
     * Parses the JSON content and renders it as HTML.
     *
     * @return string
     */
    public function getParsedContent()
    {
        return RichTextHelper::getHtmlContent($this->content, null, false);
    }

    /**
     * Returns the notification's recipients.
     *
     * @return string
     */
    public function getToEmail($submission)
    {
        if ($this->recipients === 'email') {
            return $this->to;
        }

        if ($this->recipients === 'conditions') {
            $conditionSettings = Json::decode($this->toConditions) ?? [];
            
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

        return '';
    }

    /**
     * Returns the template path for an email component.
     *
     * @param string|array $components can be 'form', 'page' or ['field1', 'field2'].
     * @param array $variables any variables to use with `$view->renderTemplate()`.
     * @return string
     * @throws Exception
     * @throws LoaderError
     */
    public function renderTemplate($components, $variables = []): string
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
    public function getTemplate()
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
    public function setTemplate($template)
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
    public function getPdfTemplate()
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
    public function setPdfTemplate($template)
    {
        if ($template) {
            $this->_pdfTemplate = $template;
            $this->pdfTemplateId = $template->id;
        } else {
            $this->_pdfTemplate = $this->pdfTemplateId = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getAssetAttachments()
    {
        $attachAssets = Json::decode($this->attachAssets) ?? [];

        if ($ids = ArrayHelper::getColumn($attachAssets, 'id')) {
            return Asset::find()->id($ids)->all();
        }

        return [];
    }
}
