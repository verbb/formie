<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\base\SavableComponent;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use craft\models\FieldLayout as CraftFieldLayout;
use craft\models\FieldLayoutTab;

use DateTime;

class FieldLayout extends SavableComponent
{
    // Properties
    // =========================================================================

    public ?string $uid = null;

    private array $_pages = [];


    // Public Methods
    // =========================================================================

    public function __construct(mixed $config = [])
    {

        // Otherwise, we should always set defaults on a form's field layout
        if (!isset($config['pages'])) {
            $config['pages'] = [
                [
                    'label' => Craft::t('formie', 'Page 1'),
                    'settings' => [],
                    'rows' => [],
                ],
            ];
        }

        parent::__construct($config);
    }

    public function getForm(): ?Form
    {
        if ($this->_form || !$this->layoutId) {
            return $this->_form;
        }

        return $this->_form = Formie::$plugin->getForms()->getFormByLayoutId($this->layoutId);
    }

    public function getPages(): array
    {
        return $this->_pages;
    }

    public function setPages(array $pages): void
    {
        $this->_pages = [];

        foreach ($pages as $page) {
            $this->_pages[] = (!($page instanceof FieldLayoutPage)) ? new FieldLayoutPage($page) : $page;
        }
    }

    public function getRows(bool $includeDisabled = true): array
    {
        $rows = [];

        foreach ($this->getPages() as $page) {
            foreach ($page->getRows($includeDisabled) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function getFields(bool $includeDisabled = true): array
    {
        $fields = [];

        foreach ($this->getPages() as $page) {
            foreach ($page->getRows($includeDisabled) as $row) {
                foreach ($row->getFields($includeDisabled) as $field) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        $foundField = null;

        foreach ($this->getFields() as $field) {
            if ($field->handle === $handle) {
                $foundField = $field;
            }
        }

        return $foundField;
    }

    public function getCustomFields(): array
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Layout’s `getCustomFields()` method has been deprecated. Use `getFields()` instead.');

        return $this->getFields();
    }

    public function getFieldLayout(): CraftFieldLayout
    {
        // TODO: remove - for legacy purposes
        $config = ['type' => Submission::class];

        foreach ($this->getPages() as $page) {
            $tab = [
                'name' => $page->label,
            ];

            foreach ($page->getFields() as $field) {
                $tab['elements'][] = [
                    'label' => $field->label,
                    'attribute' => $field->handle,
                    'type' => "craft\\fieldlayoutelements\\TextField",
                ];
            }

            $config['tabs'][] = $tab;
        }

        return CraftFieldLayout::createFromConfig($config);
    }

    public function getFormBuilderConfig(): array
    {
        return array_map(function($page) {
            return $page->getFormBuilderConfig();
        }, $this->getPages());
    }

    public function validatePages(): void
    {
        foreach ($this->getPages() as $page) {
            if (!$page->validate()) {
                $this->addError('pages', $page->getErrors());
            }
        }
    }

    public function getVisiblePageFields(ElementInterface $element): array
    {
        // Compatibility with Craft Field Layout
        $currentPageFields = $element->getForm()?->getCurrentPage()?->getFields() ?? [];

        // Organise fields, so they're easier to check against
        $currentPageFieldHandles = ArrayHelper::getColumn($currentPageFields, 'handle');

        return array_filter($this->getFields(), function($field) use ($element, $currentPageFieldHandles) {
            // Check when we're doing a submission from the front-end, and we choose to validate the current page only
            if ($element instanceof Submission && $element->validateCurrentPageOnly) {
                if (!in_array($field->handle, $currentPageFieldHandles)) {
                    return false;
                }
            }

            if ($field->isConditionallyHidden($element)) {
                return false;
            }

            return true;
        });
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pages'], 'validatePages'];

        return $rules;
    }
}
