<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\helpers\VariableNode;
use verbb\formie\prosemirror\tohtml\Renderer;

use craft\base\Model;
use craft\helpers\Json;

use yii\behaviors\AttributeTypecastBehavior;

class Notification extends Model
{
    // Public Properties
    // =========================================================================

    public $id;
    public $formId;
    public $templateId;
    public $name;
    public $enabled;
    public $subject;
    public $to;
    public $cc;
    public $bcc;
    public $replyTo;
    public $replyToName;
    public $from;
    public $fromName;
    public $content;
    public $attachFiles;
    public $enableConditions;
    public $conditions;
    public $uid;


    // Private Properties
    // =========================================================================

    private $_template;


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

        // Cast some properties. Doesn't play with with JS otherwise.
        $this->attachFiles = (bool)$this->attachFiles;
        $this->enableConditions = (bool)$this->enableConditions;
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
                'name' => AttributeTypecastBehavior::TYPE_STRING,
                'enabled' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'subject' => AttributeTypecastBehavior::TYPE_STRING,
                'to' => AttributeTypecastBehavior::TYPE_STRING,
                'cc' => AttributeTypecastBehavior::TYPE_STRING,
                'bcc' => AttributeTypecastBehavior::TYPE_STRING,
                'replyTo' => AttributeTypecastBehavior::TYPE_STRING,
                'replyToName' => AttributeTypecastBehavior::TYPE_STRING,
                'from' => AttributeTypecastBehavior::TYPE_STRING,
                'fromName' => AttributeTypecastBehavior::TYPE_STRING,
                'attachFiles' => AttributeTypecastBehavior::TYPE_BOOLEAN,
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

        $rules[] = [['name', 'subject', 'to'], 'required'];
        $rules[] = [['name', 'subject', 'to', 'cc', 'bcc', 'replyTo', 'replyToName', 'from', 'fromName'], 'string'];
        $rules[] = [['formId', 'templateId'], 'number', 'integerOnly' => true];

        return $rules;
    }

    /**
     * Parses the JSON content and renders it as HTML.
     *
     * @return string
     */
    public function getParsedContent()
    {
        $content = Json::decode($this->content);

        $renderer = new Renderer();
        $renderer->addNode(VariableNode::class);

        return $renderer->render([
            'type' => 'doc',
            'content' => $content,
        ]);
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
     * Sets the form template.
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
}
