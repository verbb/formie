<?php
namespace verbb\formie\models;

use verbb\formie\helpers\FileHelper;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\helpers\ArrayHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use yii\validators\Validator;

use DateTime;

abstract class BaseTemplate extends Model
{
    use SoftDeleteTrait {
        behaviors as softDeleteBehaviors;
    }


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?string $template = null;
    public ?int $sortOrder = null;
    public ?DateTime $dateDeleted = null;
    public ?string $uid = null;
    
    public bool $copyTemplates = false;
    public bool $hasSingleTemplate = false;


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDisplayName();
    }

    /**
     * Gets the display name for the template.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if ($this->dateDeleted !== null) {
            return $this->name . Craft::t('formie', ' (Trashed)');
        }

        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle', 'template'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ];
        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => $this->getRecordClass(),
        ];
        $rules[] = [
            'template', function($attribute, $params, Validator $validator) {
                $templatesPath = Craft::$app->getPath()->getSiteTemplatesPath();

                $view = Craft::$app->getView();
                $oldTemplatesPath = $view->getTemplatesPath();
                $view->setTemplatesPath($templatesPath);

                // Check how to validate templates
                if ($this->hasSingleTemplate) {
                    if (!$view->doesTemplateExist($this->$attribute)) {
                        // Check for the template across multiple base paths
                        if (!FileHelper::doesSitePathExist($this->$attribute)) {
                            $validator->addError($this, $attribute, Craft::t('formie', 'The template does not exist.'));
                        }
                    }
                } else {
                    // Check for the template across multiple base paths
                    if (!FileHelper::doesSitePathExist($this->$attribute)) {
                        $validator->addError($this, $attribute, Craft::t('formie', 'The template directory does not exist.'));
                    }
                }

                $view->setTemplatesPath($oldTemplatesPath);
            },
        ];

        return $rules;
    }

    /**
     * Returns the class of the template active record.
     *
     * @return string
     */
    abstract protected function getRecordClass(): string;
}
