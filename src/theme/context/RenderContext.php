<?php
namespace verbb\formie\theme\context;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FieldLayoutPage;

final class RenderContext
{
    // Static Methods
    // =========================================================================

    public static function from(array|self|null $context = null, array $defaults = []): self
    {
        if ($context instanceof self) {
            $context = $context->toArray();
        }

        $values = $context ?? [];
        $renderContext = new self();

        $form = self::_getContextValue($values, $defaults, 'form');
        $field = self::_getContextValue($values, $defaults, 'field');
        $submission = self::_getContextValue($values, $defaults, 'submission') ?? self::_getContextValue($values, $defaults, 'element');
        $targetPage = self::_getContextValue($values, $defaults, 'targetPage') ?? self::_getContextValue($values, $defaults, 'page');
        $currentPage = self::_getContextValue($values, $defaults, 'currentPage');

        $renderContext->form = $form instanceof Form ? $form : null;
        $renderContext->field = $field instanceof FieldInterface ? $field : null;
        $renderContext->submission = $submission instanceof Submission ? $submission : null;
        $renderContext->targetPage = $targetPage instanceof FieldLayoutPage ? $targetPage : null;
        $renderContext->currentPage = $currentPage instanceof FieldLayoutPage ? $currentPage : null;
        $renderContext->row = self::_getContextValue($values, $defaults, 'row');
        $renderContext->errors = self::_getContextValue($values, $defaults, 'errors') ?? [];
        $renderContext->renderOptions = self::_getContextValue($values, $defaults, 'renderOptions') ?? [];

        $extra = $values;

        if ($defaults) {
            foreach ($defaults as $key => $value) {
                if (!array_key_exists($key, $extra)) {
                    $extra[$key] = $value;
                }
            }
        }

        unset(
            $extra['form'],
            $extra['field'],
            $extra['submission'],
            $extra['element'],
            $extra['targetPage'],
            $extra['page'],
            $extra['currentPage'],
            $extra['row'],
            $extra['errors'],
            $extra['renderOptions'],
        );

        if (!is_array($renderContext->errors)) {
            $renderContext->errors = [];
        }

        if (!is_array($renderContext->renderOptions)) {
            $renderContext->renderOptions = [];
        }

        $renderContext->extra = $extra;

        return $renderContext;
    }


    // Properties
    // =========================================================================

    public ?Form $form = null;
    public ?FieldInterface $field = null;
    public ?Submission $submission = null;
    public ?FieldLayoutPage $targetPage = null;
    public ?FieldLayoutPage $currentPage = null;
    public mixed $row = null;
    public array $errors = [];
    public array $renderOptions = [];
    public array $extra = [];


    // Public Methods
    // =========================================================================

    public function toArray(): array
    {
        return array_merge($this->extra, [
            'form' => $this->form,
            'field' => $this->field,
            'submission' => $this->submission,
            'page' => $this->targetPage,
            'currentPage' => $this->currentPage,
            'row' => $this->row,
            'errors' => $this->errors,
            'renderOptions' => $this->renderOptions,
        ]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (property_exists($this, $key)) {
            return $this->$key ?? $default;
        }

        if ($key === 'page') {
            return $this->targetPage ?? $default;
        }

        return $this->extra[$key] ?? $default;
    }

    public function renderOption(string $key, mixed $default = null): mixed
    {
        return $this->renderOptions[$key] ?? $default;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function fieldIsConditionallyHidden(): bool
    {
        if (!$this->field || !$this->submission) {
            return false;
        }

        return $this->field->isConditionallyHidden($this->submission);
    }

    public function isMultipage(): bool
    {
        return $this->form?->hasMultiplePages() ?? false;
    }

    public function pageIsCurrent(): bool
    {
        $pageId = $this->targetPage?->id ?? null;
        $currentPageId = $this->currentPage?->id ?? null;

        return $pageId !== null && $currentPageId !== null && (string)$pageId === (string)$currentPageId;
    }

    public function pageHasErrors(): bool
    {
        if (!$this->targetPage || !$this->submission || !method_exists($this->targetPage, 'getFieldErrors')) {
            return false;
        }

        return (bool)$this->targetPage->getFieldErrors($this->submission);
    }

    public function pageIsComplete(): bool
    {
        if (!$this->form || !$this->targetPage || !$this->currentPage) {
            return false;
        }

        return $this->form->getPageIndex($this->currentPage) > $this->form->getPageIndex($this->targetPage);
    }

    public function inputId(): ?string
    {
        if (!$this->field || !$this->form || !method_exists($this->field, 'getHtmlId')) {
            return null;
        }

        return $this->field->getHtmlId($this->form);
    }

    public function errorsId(): ?string
    {
        $inputId = $this->inputId();

        return $inputId ? "{$inputId}-errors" : null;
    }

    public function instructionsId(): ?string
    {
        $inputId = $this->inputId();

        return $inputId ? "{$inputId}-instructions" : null;
    }


    // Private Methods
    // =========================================================================

    private static function _getContextValue(array $context, array $defaults, string $key): mixed
    {
        if (array_key_exists($key, $context)) {
            return $context[$key];
        }

        if (array_key_exists($key, $defaults)) {
            return $defaults[$key];
        }

        return null;
    }
}
