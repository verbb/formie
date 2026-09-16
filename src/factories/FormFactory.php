<?php
namespace verbb\formie\factories;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields\Address;
use verbb\formie\fields\Agree;
use verbb\formie\fields\Calculations;
use verbb\formie\fields\Categories;
use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Content;
use verbb\formie\fields\Date;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Email;
use verbb\formie\fields\Entries;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Forms;
use verbb\formie\fields\Group;
use verbb\formie\fields\Heading;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Html;
use verbb\formie\fields\MissingField;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\Name;
use verbb\formie\fields\Note;
use verbb\formie\fields\Number;
use verbb\formie\fields\Password;
use verbb\formie\fields\Payment;
use verbb\formie\fields\Phone;
use verbb\formie\fields\Products;
use verbb\formie\fields\Radio;
use verbb\formie\fields\Recipients;
use verbb\formie\fields\Repeater;
use verbb\formie\fields\Section;
use verbb\formie\fields\Signature;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Submissions;
use verbb\formie\fields\Summary;
use verbb\formie\fields\Table;
use verbb\formie\fields\Tags;
use verbb\formie\fields\Users;
use verbb\formie\fields\Variants;
use verbb\formie\models\FieldLayout;

use Craft;

use InvalidArgumentException;
use RuntimeException;

final class FormFactory
{
    // Static Methods
    // =========================================================================

    private static function _generateAutoHandle(): string
    {
        do {
            $counter = self::$autoHandleCounter++;
            // Grow beyond two letters instead of cycling through an exhausted namespace.
            $handle = '';
            do {
                $handle = self::HANDLE_ALPHABET[$counter % 26] . $handle;
                $counter = intdiv($counter, 26);
            } while ($counter > 0);
            $handle = str_pad($handle, 2, 'a', STR_PAD_LEFT);
        } while (
            isset(self::$autoHandlesIssued[$handle]) ||
            in_array(strtolower($handle), array_map('strtolower', array_merge(self::RESERVED_HANDLES, \craft\validators\HandleValidator::$baseReservedWords)), true) ||
            Form::find()->withoutCpIndexScope()->handle($handle)->site('*')->unique()->status(null)->exists()
        );

        self::$autoHandlesIssued[$handle] = true;

        return $handle;
    }


    // Constants
    // =========================================================================

    private const HANDLE_ALPHABET = 'abcdefghijklmnopqrstuvwxyz';
    private const RESERVED_HANDLES = ['id', 'uid', 'title', 'datecreated', 'dateupdated'];


    // Properties
    // =========================================================================

    private static int $autoHandleCounter = 0;
    private static array $autoHandlesIssued = [];

    private array $formConfig;
    private array $settingsConfig = [];
    private array $pages = [];
    private int $currentPageIndex = 0;
    private int $currentRowIndex = 0;
    private bool $singleFieldRows = false;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'title' => 'Programmatic Form',
            'handle' => self::_generateAutoHandle(),
        ];

        $this->formConfig = array_merge($defaultConfig, $config);

        $this->_ensurePage(0);
        $this->_ensureRow(0, 0);
    }

    public function singleLineTextField(string $handle, array $config = []): self
    {
        return $this->addField(SingleLineText::class, $handle, $config);
    }

    public function emailField(string $handle, array $config = []): self
    {
        return $this->addField(Email::class, $handle, $config);
    }

    public function addressField(string $handle, array $config = []): self
    {
        return $this->addField(Address::class, $handle, $config);
    }

    public function agreeField(string $handle, array $config = []): self
    {
        return $this->addField(Agree::class, $handle, $config);
    }

    public function calculationsField(string $handle, array $config = []): self
    {
        return $this->addField(Calculations::class, $handle, $config);
    }

    public function categoriesField(string $handle, array $config = []): self
    {
        return $this->addField(Categories::class, $handle, $config);
    }

    public function checkboxesField(string $handle, array $config = []): self
    {
        return $this->addField(Checkboxes::class, $handle, $config);
    }

    public function multiLineTextField(string $handle, array $config = []): self
    {
        return $this->addField(MultiLineText::class, $handle, $config);
    }

    public function numberField(string $handle, array $config = []): self
    {
        return $this->addField(Number::class, $handle, $config);
    }

    public function dateField(string $handle, array $config = []): self
    {
        return $this->addField(Date::class, $handle, $config);
    }

    public function dropdownField(string $handle, array $config = []): self
    {
        return $this->addField(Dropdown::class, $handle, $config);
    }

    public function entriesField(string $handle, array $config = []): self
    {
        return $this->addField(Entries::class, $handle, $config);
    }

    public function fileUploadField(string $handle, array $config = []): self
    {
        return $this->addField(FileUpload::class, $handle, $config);
    }

    public function formsField(string $handle, array $config = []): self
    {
        return $this->addField(Forms::class, $handle, $config);
    }

    public function groupField(string $handle, array $config = []): self
    {
        return $this->addField(Group::class, $handle, $config);
    }

    public function headingField(string $handle, array $config = []): self
    {
        return $this->addField(Heading::class, $handle, $config);
    }

    public function hiddenField(string $handle, array $config = []): self
    {
        return $this->addField(Hidden::class, $handle, $config);
    }

    public function htmlField(string $handle, array $config = []): self
    {
        return $this->addField(Html::class, $handle, $config);
    }

    public function contentField(string $handle, array $config = []): self
    {
        return $this->addField(Content::class, $handle, $config);
    }

    public function noteField(string $handle, array $config = []): self
    {
        return $this->addField(Note::class, $handle, $config);
    }

    public function missingField(string $handle, array $config = []): self
    {
        return $this->addField(MissingField::class, $handle, $config);
    }

    public function nameField(string $handle, array $config = []): self
    {
        return $this->addField(Name::class, $handle, $config);
    }

    public function passwordField(string $handle, array $config = []): self
    {
        return $this->addField(Password::class, $handle, $config);
    }

    public function paymentField(string $handle, array $config = []): self
    {
        return $this->addField(Payment::class, $handle, $config);
    }

    public function phoneField(string $handle, array $config = []): self
    {
        return $this->addField(Phone::class, $handle, $config);
    }

    public function productsField(string $handle, array $config = []): self
    {
        return $this->addField(Products::class, $handle, $config);
    }

    public function radioField(string $handle, array $config = []): self
    {
        return $this->addField(Radio::class, $handle, $config);
    }

    public function recipientsField(string $handle, array $config = []): self
    {
        return $this->addField(Recipients::class, $handle, $config);
    }

    public function repeaterField(string $handle, array $config = []): self
    {
        return $this->addField(Repeater::class, $handle, $config);
    }

    public function sectionField(string $handle, array $config = []): self
    {
        return $this->addField(Section::class, $handle, $config);
    }

    public function signatureField(string $handle, array $config = []): self
    {
        return $this->addField(Signature::class, $handle, $config);
    }

    public function submissionsField(string $handle, array $config = []): self
    {
        return $this->addField(Submissions::class, $handle, $config);
    }

    public function summaryField(string $handle, array $config = []): self
    {
        return $this->addField(Summary::class, $handle, $config);
    }

    public function tableField(string $handle, array $config = []): self
    {
        return $this->addField(Table::class, $handle, $config);
    }

    public function tagsField(string $handle, array $config = []): self
    {
        return $this->addField(Tags::class, $handle, $config);
    }

    public function usersField(string $handle, array $config = []): self
    {
        return $this->addField(Users::class, $handle, $config);
    }

    public function variantsField(string $handle, array $config = []): self
    {
        return $this->addField(Variants::class, $handle, $config);
    }

    public function multiPage(int $pages = 2): self
    {
        $pages = max(1, $pages);

        for ($i = 0; $i < $pages; $i++) {
            $this->_ensurePage($i);
        }

        $this->currentPageIndex = 0;
        $this->currentRowIndex = 0;

        return $this;
    }

    public function page(int $pageNumber): self
    {
        $index = max(0, $pageNumber - 1);
        $this->_ensurePage($index);
        $this->currentPageIndex = $index;
        $this->currentRowIndex = 0;
        $this->_ensureRow($this->currentPageIndex, $this->currentRowIndex);

        return $this;
    }

    public function onPage(int $pageNumber): self
    {
        return $this->page($pageNumber);
    }

    public function singleFieldRows(bool $enabled = true): self
    {
        $this->singleFieldRows = $enabled;

        return $this;
    }

    public function addField(string $fieldClass, string $handle, array $config = []): self
    {
        if (!is_subclass_of($fieldClass, FieldInterface::class)) {
            throw new InvalidArgumentException('Field class must implement Formie FieldInterface: `' . $fieldClass . '`.');
        }

        return $this->addFieldConfig(array_merge($config, [
            'type' => $fieldClass,
            'handle' => $handle,
            'label' => $config['label'] ?? ucfirst($handle),
        ]));
    }

    public function addFieldConfig(array $fieldConfig): self
    {
        $this->_ensurePage($this->currentPageIndex);
        $this->_ensureRow($this->currentPageIndex, $this->currentRowIndex);

        $fieldClass = $fieldConfig['type'] ?? null;

        if (!is_string($fieldClass) || !is_subclass_of($fieldClass, FieldInterface::class)) {
            throw new InvalidArgumentException('Field config must include a valid Formie field `type`.');
        }

        if (!isset($fieldConfig['handle']) || !is_string($fieldConfig['handle']) || $fieldConfig['handle'] === '') {
            throw new InvalidArgumentException('Field config must include a non-empty `handle`.');
        }

        if (!isset($fieldConfig['label'])) {
            $fieldConfig['label'] = ucfirst($fieldConfig['handle']);
        }

        $this->pages[$this->currentPageIndex]['rows'][$this->currentRowIndex]['fields'][] = $fieldConfig;

        if ($this->singleFieldRows) {
            $this->currentRowIndex++;
            $this->_ensureRow($this->currentPageIndex, $this->currentRowIndex);
        }

        return $this;
    }

    public function addFields(array $fieldConfigs): self
    {
        foreach ($fieldConfigs as $fieldConfig) {
            $this->addFieldConfig($fieldConfig);
        }

        return $this;
    }

    public function row(array $fieldConfigs = []): self
    {
        $this->_ensurePage($this->currentPageIndex);

        $nextRowIndex = count($this->pages[$this->currentPageIndex]['rows']);
        $this->_ensureRow($this->currentPageIndex, $nextRowIndex);
        $this->currentRowIndex = $nextRowIndex;

        if ($fieldConfigs) {
            $this->addFields($fieldConfigs);
        }

        return $this;
    }

    public function settings(array $config): self
    {
        $this->settingsConfig = array_replace_recursive($this->settingsConfig, $config);

        return $this;
    }

    public function integrations(array $config): self
    {
        return $this->settings([
            'integrations' => $config,
        ]);
    }

    public function required(string $handle): self
    {
        foreach ($this->pages as $pageIndex => $page) {
            foreach ($page['rows'] as $rowIndex => $row) {
                foreach ($row['fields'] as $fieldIndex => $fieldConfig) {
                    if (($fieldConfig['handle'] ?? null) === $handle) {
                        $this->pages[$pageIndex]['rows'][$rowIndex]['fields'][$fieldIndex]['required'] = true;
                    }
                }
            }
        }

        return $this;
    }

    public function submitAction(string $action, array $config = []): self
    {
        $allowedActions = ['message', 'reload', 'reset', 'url', 'entry'];

        if (!in_array($action, $allowedActions, true)) {
            throw new InvalidArgumentException('Unknown submit action `' . $action . '`.');
        }

        $settings = ['submitAction' => $action];

        if (isset($config['method'])) {
            $settings['submitMethod'] = $config['method'];
        }

        if (isset($config['url'])) {
            $settings['submitActionUrl'] = $config['url'];
        }

        if (isset($config['tab'])) {
            $settings['submitActionTab'] = $config['tab'];
        }

        if (isset($config['message'])) {
            $settings['submitActionMessage'] = $config['message'];
        }

        if (array_key_exists('hideForm', $config)) {
            $settings['submitActionFormHide'] = (bool)$config['hideForm'];
        }

        $this->settingsConfig = array_merge($this->settingsConfig, $settings);

        return $this;
    }

    public function create(): Form
    {
        $pages = array_map(function(array $page): array {
            $page['rows'] = array_values(array_filter($page['rows'], static function(array $row): bool {
                return !empty($row['fields']);
            }));

            return $page;
        }, array_values($this->pages));

        $form = new Form($this->formConfig);
        $form->setFormLayout(new FieldLayout(['pages' => $pages]));
        $form->settings->setAttributes($this->settingsConfig, false);

        $saved = Craft::$app->elements->saveElement($form);

        if (!$saved) {
            throw new RuntimeException('Failed to save programmatic form: ' . json_encode($form->getErrors()));
        }

        return $form;
    }


    // Private Methods
    // =========================================================================

    private function _ensurePage(int $index): void
    {
        if (!isset($this->pages[$index])) {
            $this->pages[$index] = [
                'label' => 'Page ' . ($index + 1),
                'settings' => [],
                'rows' => [],
            ];
        }
    }

    private function _ensureRow(int $pageIndex, int $rowIndex): void
    {
        $this->_ensurePage($pageIndex);

        if (!isset($this->pages[$pageIndex]['rows'][$rowIndex])) {
            $this->pages[$pageIndex]['rows'][$rowIndex] = [
                'fields' => [],
            ];
        }
    }

}
