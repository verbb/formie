<?php
namespace verbb\formie\base;

use verbb\formie\models\IntegrationField;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\ElementHelper;
use craft\helpers\Template as TemplateHelper;

trait RelationFieldTrait
{
    // Public Methods
    // =========================================================================

    public function getCpElementHtml(array &$context)
    {
        if (!isset($context['element'])) {
            return null;
        }

        /** @var Element $element */
        $element = $context['element'];
        $label = $element->getUiLabel();

        if (!isset($context['context'])) {
            $context['context'] = 'index';
        }

        // How big is the element going to be?
        if (isset($context['size']) && ($context['size'] === 'small' || $context['size'] === 'large')) {
            $elementSize = $context['size'];
        } else if (isset($context['viewMode']) && $context['viewMode'] === 'thumbs') {
            $elementSize = 'large';
        } else {
            $elementSize = 'small';
        }

        // Create the thumb/icon image, if there is one
        // ---------------------------------------------------------------------

        $thumbSize = $elementSize === 'small' ? 34 : 120;
        $thumbUrl = $element->getThumbUrl($thumbSize);

        if ($thumbUrl !== null) {
            $imageSize2x = $thumbSize * 2;
            $thumbUrl2x = $element->getThumbUrl($imageSize2x);

            $srcsets = [
                "$thumbUrl {$thumbSize}w",
                "$thumbUrl2x {$imageSize2x}w",
            ];
            $sizesHtml = "{$thumbSize}px";
            $srcsetHtml = implode(', ', $srcsets);
            $imgHtml = "<div class='elementthumb' data-sizes='{$sizesHtml}' data-srcset='{$srcsetHtml}'></div>";
        } else {
            $imgHtml = '';
        }

        $htmlAttributes = array_merge(
            $element->getHtmlAttributes($context['context']),
            [
                'class' => 'element ' . $elementSize,
                'data-type' => get_class($element),
                'data-id' => $element->id,
                'data-site-id' => $element->siteId,
                'data-status' => $element->getStatus(),
                'data-label' => (string)$element,
                'data-url' => $element->getUrl(),
                'data-level' => $element->level,
                'title' => $label . (Craft::$app->getIsMultiSite() ? ' – ' . $element->getSite()->name : ''),
            ]);

        if ($context['context'] === 'field') {
            $htmlAttributes['class'] .= ' removable';
        }

        if ($element->hasErrors()) {
            $htmlAttributes['class'] .= ' error';
        }

        if ($element::hasStatuses()) {
            $htmlAttributes['class'] .= ' hasstatus';
        }

        if ($thumbUrl !== null) {
            $htmlAttributes['class'] .= ' hasthumb';
        }

        $html = '<div';

        // todo: swap this with Html::renderTagAttributse in 4.0
        // (that will cause a couple breaking changes since `null` means "don't show" and `true` means "no value".)
        foreach ($htmlAttributes as $attribute => $value) {
            $html .= ' ' . $attribute . ($value !== null ? '="' . Html::encode($value) . '"' : '');
        }

        if (ElementHelper::isElementEditable($element)) {
            $html .= ' data-editable';
        }

        if ($element->trashed) {
            $html .= ' data-trashed';
        }

        $html .= '>';

        if ($context['context'] === 'field' && isset($context['name'])) {
            $html .= '<input type="hidden" name="' . $context['name'] . '[]" value="' . $element->id . '">';
            $html .= '<a class="delete icon" title="' . Craft::t('app', 'Remove') . '"></a> ';
        }

        if ($element::hasStatuses()) {
            $status = $element->getStatus();
            $statusClasses = $status . ' ' . ($element::statuses()[$status]['color'] ?? '');
            $html .= '<span class="status ' . $statusClasses . '"></span>';
        }

        $html .= $imgHtml;
        $html .= '<div class="label">';

        $html .= '<span class="title">';

        // CHANGED - allow linking off the label
        $encodedLabel = Html::encode($label);
        $cpEditUrl = Html::encode($element->getCpEditUrl());
        $html .= "<a href=\"{$cpEditUrl}\" target=\"_blank\">{$encodedLabel}</a>";

        $html .= '</span></div></div>';

        return TemplateHelper::raw($html);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var Element|null $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        } else {
            /** @var ElementQueryInterface $value */
            $value = $this->_all($value, $element);
        }

        /** @var ElementQuery|array $value */
        $variables = $this->inputTemplateVariables($value, $element);

        $variables['field'] = $this;

        return Craft::$app->getView()->renderTemplate($this->inputTemplate, $variables);
    }

    /**
     * @inheritDoc
     */
    public function getFieldMappedValueForIntegration(IntegrationField $integrationField, $formField, $value, $submission)
    {
        // Override the value to get full elements
        $value = $submission->getFieldValue($formField->handle);

        // Send through a CSV of element titles, when mapping to a string
        if ($integrationField->getType() === IntegrationField::TYPE_STRING) {
            $titles = ArrayHelper::getColumn($value->all(), 'title');

            return implode(', ', $titles);
        }

        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            return $value->ids();
        }

        return null;
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns a clone of the element query value, prepped to include disabled and cross-site elements.
     *
     * @param ElementQueryInterface $query
     * @param ElementInterface|null $element
     * @return ElementQueryInterface
     */
    private function _all(ElementQueryInterface $query, ElementInterface $element = null): ElementQueryInterface
    {
        $clone = clone $query;
        $clone
            ->anyStatus()
            ->siteId('*')
            ->unique();

        if ($element !== null) {
            $clone->preferSites([$this->targetSiteId($element)]);
        }
        return $clone;
    }
}
