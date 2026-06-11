<?php
namespace verbb\formie\base;

use verbb\formie\compatibility\fields\FieldCompatibility;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFieldSlotTagEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;
use verbb\formie\web\FieldRenderCallContext;

use craft\helpers\Template;

use Twig\Markup;

trait FieldServerRenderTrait
{
    // Public Methods
    // =========================================================================

    public function getHtmlId(Form $form, ?string $extra = null): string
    {
        return $this->getFieldPath()->htmlId($form, $extra);
    }

    public function getHtmlDataId(Form $form, ?string $extra = null): string
    {
        return $this->getFieldPath()->htmlDataId($form, $extra);
    }

    public function getHtmlName(?string $extra = null): string
    {
        if ($extra === null && ($inputName = FieldRenderCallContext::get('inputName'))) {
            $parent = $this->getParentField();

            if ($parent instanceof FieldInterface) {
                return $parent->getHtmlName($inputName);
            }

            $extra = $inputName;
        }

        return $this->getFieldPath()->htmlName($extra);
    }

    public function getContainerAttributes(): array
    {
        if (!$this->containerAttributes) {
            return [];
        }

        return ArrayHelper::map($this->containerAttributes, 'label', 'value');
    }

    public function getInputAttributes(): array
    {
        if (!$this->inputAttributes) {
            return [];
        }

        return ArrayHelper::map($this->inputAttributes, 'label', 'value');
    }

    public function renderSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = $this->defineFieldSlotTag($key, $context);

        if (!$tag) {
            $tag = FieldCompatibility::renderLegacyHtmlTag($this, $key, $context);
        }

        $form = $context->form ?? $this->getForm();
        $tag = Formie::$plugin->getThemeConfigService()->applyFieldTagConfig($this, $form, $key, $tag, $context);

        $event = new ModifyFieldSlotTagEvent([
            'field' => $this,
            'tag' => $tag,
            'key' => $key,
            'context' => $context->toArray(),
        ]);

        $this->trigger(static::EVENT_MODIFY_SLOT_TAG, $event);
        $this->triggerDeprecatedHtmlTagEvent($event);

        return $event->tag;
    }

    public function renderInput(Form $form, mixed $value): Markup
    {
        $inputTemplatePath = $this->defineInputTemplatePath();

        if (!$inputTemplatePath) {
            return Template::raw('');
        }

        $inputOptions = $this->getInputTemplateVariables($form, $value);
        $html = $form->renderTemplate($inputTemplatePath, $inputOptions);

        return Template::raw($html);
    }

    public function getInputTemplateVariables(Form $form, mixed $value): array
    {
        $frame = Formie::$plugin->getRendering()->getActiveRenderFrame();
        $includeScriptsInline = $frame?->includeScriptsInline() ?? false;
        $templateVars = $frame?->getTemplateVars() ?? [];

        $submission = $form->getCurrentSubmission();
        $errors = $submission ? $submission->getErrors($this->errorKey()) : [];

        $inputName = FieldRenderCallContext::get('inputName');

        return [
            'form' => $form,
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'errors' => $errors,
            'submission' => $submission,
            'includeScriptsInline' => $includeScriptsInline,
            'templateVars' => $templateVars,
            'inputName' => $inputName,
            'fieldLabelPrefix' => null,
            'fieldLabelSuffix' => null,
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        return Formie::$plugin->getFieldSlotRegistry()->resolve($key, $context);
    }

    protected function defineInputTemplatePath(): ?string
    {
        return static::getInputTemplatePath();
    }
}
