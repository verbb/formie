<?php
namespace verbb\formie\helpers;

use Craft;
use craft\base\ElementInterface;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\DefineHiddenDefaultTemplateContextEvent;
use verbb\formie\fields\Hidden;
use verbb\formie\Formie;
use verbb\formie\models\HiddenDefaultTemplateContext;

use yii\base\Event;

class HiddenDefaultTemplateResolver
{
    public const EVENT_DEFINE_CONTEXT = 'defineHiddenDefaultTemplateContext';

    /** @var array<string, string> */
    private static array $_cache = [];

    public static function resolve(Hidden $field, ?ElementInterface $element = null): string
    {
        $template = trim((string)$field->defaultTemplate);

        if ($template === '' || !$field->usesTemplateDefault()) {
            return '';
        }

        $cacheKey = self::_cacheKey($field, $element, $template);

        if (array_key_exists($cacheKey, self::$_cache)) {
            return self::$_cache[$cacheKey];
        }

        try {
            $context = self::buildContext($field, $element);
            $variables = self::triggerDefineContext($field, $element, $context);

            $resolved = Formie::$plugin->getTemplates()->renderObjectTemplate(
                $template,
                $context,
                $variables,
            );
        } catch (\Throwable $e) {
            Craft::error(
                'Could not resolve hidden field default template for `' . $field->handle . '`: ' . $e->getMessage(),
                __METHOD__,
            );

            $resolved = '';
        }

        self::$_cache[$cacheKey] = $resolved;

        return $resolved;
    }

    public static function buildContext(Hidden $field, ?ElementInterface $element = null): HiddenDefaultTemplateContext
    {
        [$form, $submission] = self::resolveFormAndSubmission($field, $element);

        return HiddenDefaultTemplateContext::fromFieldContext($form, $submission);
    }

    /**
     * @return array<string, mixed>
     */
    public static function triggerDefineContext(
        Hidden $field,
        ?ElementInterface $element,
        HiddenDefaultTemplateContext $context,
    ): array {
        $event = new DefineHiddenDefaultTemplateContextEvent([
            'field' => $field,
            'element' => $element,
            'context' => $context,
            'variables' => [],
        ]);

        Event::trigger(self::class, self::EVENT_DEFINE_CONTEXT, $event);

        return $event->variables;
    }

    /**
     * @return array{0: ?Form, 1: ?Submission}
     */
    private static function resolveFormAndSubmission(Hidden $field, ?ElementInterface $element): array
    {
        $form = null;
        $submission = null;

        if ($element instanceof Submission) {
            $submission = $element;
            $form = $element->getForm() ?? $field->getForm();
        } elseif ($element instanceof Form) {
            $form = $element;
            $submission = $element->getCurrentSubmission();
        } else {
            $form = $field->getForm();

            if ($form) {
                $submission = $form->getCurrentSubmission();
            }
        }

        return [$form, $submission];
    }

    private static function _cacheKey(Hidden $field, ?ElementInterface $element, string $template): string
    {
        $fieldUid = (string)($field->uid ?? $field->handle ?? 'field');
        $elementId = $element?->id ?? 'no-element';
        $submissionId = $element instanceof Submission ? (string)($element->id ?? 'new') : 'no-submission';

        return md5(implode('|', [$fieldUid, $elementId, $submissionId, $template]));
    }
}
