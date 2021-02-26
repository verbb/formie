<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\models\FieldLayout as CraftFieldLayout;
use craft\models\FieldLayoutTab as CraftFieldLayoutTab;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\Formie;

use yii\base\InvalidConfigException;

class FieldLayoutPage extends CraftFieldLayoutTab
{
    // Public Properties
    // =========================================================================

    /**
     * @var PageSettings
     */
    public $settings;


    // Private Properties
    // =========================================================================

    private $_layout;
    private $_fields;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->settings)) {
            $this->settings = new PageSettings();
        } else {
            $settings = Json::decodeIfJson($this->settings);
            $this->settings = new PageSettings($settings);
        }
    }

    /**
     * Returns the tab’s layout.
     *
     * @return FieldLayout|null The tab’s layout.
     * @throws InvalidConfigException if [[groupId]] is set but invalid
     */
    public function getLayout()
    {
        if ($this->_layout !== null) {
            return $this->_layout;
        }

        if (!$this->layoutId) {
            return null;
        }

        if (($this->_layout = Formie::$plugin->getFields()->getLayoutById($this->layoutId)) === null) {
            throw new InvalidConfigException('Invalid layout ID: ' . $this->layoutId);
        }

        return $this->_layout;
    }

    /**
     * Sets the page’s layout.
     *
     * @param FieldLayout $layout The page’s layout.
     */
    public function setLayout(CraftFieldLayout $layout)
    {
        $this->_layout = $layout;
    }

    /**
     * Returns the tab’s fields.
     *
     * @return FieldInterface[] The tab’s fields.
     * @throws InvalidConfigException
     */
    public function getFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        $this->_fields = [];

        if ($layout = $this->getLayout()) {
            foreach ($layout->getFields() as $field) {
                /** @var Field $field */
                if ($field->tabId == $this->id) {
                    $this->_fields[] = $field;
                }
            }
        }

        return $this->_fields;
    }

    /**
     * @inheritDoc
     */
    public function setFields(array $fields)
    {
        ArrayHelper::multisort($fields, 'sortOrder');
        $this->_fields = $fields;

        $this->elements = [];
        
        foreach ($this->_fields as $field) {
            $this->elements[] = Craft::createObject([
                'class' => CustomField::class,
                'required' => $field->required,
            ], [
                $field,
            ]);
        }
    }

    /**
     * @return FieldInterface[]
     * @throws InvalidConfigException
     */
    public function getRows()
    {
        /* @var FormFieldInterface[] $pageFields */
        $pageFields = $this->getFields();
        return Formie::$plugin->getFields()->groupIntoRows($pageFields);
    }
}
