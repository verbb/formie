<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormLayout;
use verbb\formie\models\FormSettings;
use verbb\formie\records\Form as FormRecord;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\NestedElementInterface;
use craft\db\Query;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

use Throwable;

class Forms extends Component
{
    // Properties
    // =========================================================================

    private array $_cachedElements = [];
    private array $_cachedFields = [];


    // Public Methods
    // =========================================================================

    public function getFormById(int $id, int $siteId = null): ?Form
    {
        return Form::find()->id($id)->siteId($siteId)->one();
    }

    public function getFormByHandle(string $handle, int $siteId = null): ?Form
    {
        return Form::find()->handle($handle)->siteId($siteId)->one();
    }

    public function getFormByUid(string $uid, int $siteId = null): ?Form
    {
        return Form::find()->uid($uid)->siteId($siteId)->one();
    }

    public function getFormByLayoutId(int $layoutId, int $siteId = null): ?Form
    {
        return Form::find()->layoutId($layoutId)->siteId($siteId)->one();
    }

    public function getAllForms(): array
    {
        return Form::find()->all();
    }

    public function buildFormFromPost(): Form
    {
        $request = Craft::$app->getRequest();
        $formId = $request->getParam('formId');
        $siteId = $request->getParam('siteId');

        if ($formId) {
            $form = Craft::$app->getElements()->getElementById($formId, Form::class, $siteId);

            if (!$form) {
                throw new Exception("No form found for ID: $formId");
            }
        } else {
            $form = new Form();
        }

        $form->title = $request->getParam('title', $form->title);
        $form->handle = $request->getParam('handle', $form->handle);
        $form->templateId = StringHelper::toId($request->getParam('templateId', $form->templateId));
        $form->defaultStatusId = StringHelper::toId($request->getParam('defaultStatusId', $form->defaultStatusId));
        $form->userDeletedAction = $request->getParam('userDeletedAction', $form->userDeletedAction);
        $form->fileUploadsAction = $request->getParam('fileUploadsAction', $form->fileUploadsAction);
        $form->dataRetention = $request->getParam('dataRetention', $form->dataRetention);
        $form->dataRetentionValue = $request->getParam('dataRetentionValue', $form->dataRetentionValue);
        $form->submitActionEntryId = $request->getParam('submitActionEntryId.id');
        $form->submitActionEntrySiteId = $request->getParam('submitActionEntryId.siteId');

        // Populate the form builder layout (pages/rows/fields)
        if ($pages = $request->getParam('pages')) {
            $form->getFormLayout()->setPages(Json::decodeIfJson($pages));
        }

        // Deleted pages/rows/fields are sent separately for convenience, but add them to the field layout for processing
        if ($deleted = $request->getParam('deleted')) {
            $form->getFormLayout()->setDeletedItems(Json::decodeIfJson($deleted));
        }

        // Merge in any new settings, while retaining existing ones. Important for users with permissions.
        if ($newSettings = $request->getParam('settings')) {
            // Retain any integration form settings before wiping them
            $oldIntegrationSettings = $form->settings->integrations ?? [];
            $newIntegrationSettings = $newSettings['integrations'] ?? [];
            $newSettings['integrations'] = array_merge($oldIntegrationSettings, $newIntegrationSettings);

            $form->settings->setAttributes($newSettings, false);
        }

        // Set the notifications
        $form->setNotifications(Formie::$plugin->getNotifications()->buildNotificationsFromPost());

        // Set custom field values
        $form->setFieldValuesFromRequest('fields');

        // Apply a chosen stencil, which will override a few things above
        if ($stencilId = $request->getParam('applyStencilId')) {
            if ($stencil = Formie::$plugin->getStencils()->getStencilById($stencilId)) {
                $stencil->applyStencilToForm($form);
            }
        }

        return $form;
    }

    public function handleBeforeSubmitHook($context): string
    {
        $form = $context['form'] ?? null;
        $page = $context['page'] ?? null;

        return Formie::$plugin->getIntegrations()->getCaptchasHtmlForForm($form, $page);
    }

    public function getFormBuilderTabs(Form $form = null, array $variables = []): array
    {
        $user = Craft::$app->getUser();

        $tabs = [];

        $tabs[] = [
            'label' => Craft::t('formie', 'Fields'),
            'value' => 'fields',
            'url' => '#tab-fields',
        ];

        if ($form && $fieldLayout = $form->getFieldLayout()) {
            foreach ($fieldLayout->getTabs() as $tab) {
                $tabSlug = StringHelper::toKebabCase($tab->name);

                $tabs[] = [
                    'label' => $tab->name,
                    'value' => "form-fields-$tabSlug",
                    'url' => "#tab-form-fields-$tabSlug",
                    'tab' => $tab,
                ];
            }
        }

        $suffix = ':' . ($form->uid ?? '');

        if ($user->checkPermission('formie-showFormAppearance') || $user->checkPermission("formie-showFormAppearance{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Appearance'),
                'value' => 'appearance',
                'url' => '#tab-appearance',
            ];
        }

        if ($user->checkPermission('formie-showFormBehavior') || $user->checkPermission("formie-showFormBehavior{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Behaviour'),
                'value' => 'behaviour',
                'url' => '#tab-behaviour',
            ];
        }

        if ($user->checkPermission('formie-showNotifications') || $user->checkPermission("formie-showNotifications{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Email Notifications'),
                'value' => 'notifications',
                'url' => '#tab-notifications',
            ];
        }

        if ($user->checkPermission('formie-showFormIntegrations') || $user->checkPermission("formie-showFormIntegrations{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Integrations'),
                'value' => 'integrations',
                'url' => '#tab-integrations',
            ];
        }

        $formUsage = $variables['formUsage'] ?? [];

        if ($formUsage && ($user->checkPermission('formie-showFormUsage') || $user->checkPermission("formie-showFormUsage{$suffix}"))) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Usage'),
                'value' => 'usage',
                'url' => '#tab-usage',
            ];
        }

        if ($user->checkPermission('formie-showFormSettings') || $user->checkPermission("formie-showFormSettings{$suffix}")) {
            $tabs[] = [
                'label' => Craft::t('formie', 'Settings'),
                'value' => 'settings',
                'url' => '#tab-settings',
            ];
        }

        return $tabs;
    }

    public function getFormUsage(Form $form): array
    {
        $elements = [];
        $settings = Formie::$plugin->getSettings();
        $includeDrafts = $settings->includeDraftElementUsage;
        $includeRevisions = $settings->includeRevisionElementUsage;

        if (!$form) {
            return $elements;
        }

        $query = (new Query())
            ->select([
                'elements.id',
                'elements.type',
                'relations.fieldId',
                'relations.sourceSiteId AS siteId',
            ])
            ->from(['relations' => Table::RELATIONS])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[relations.sourceId]]')
            ->where(['relations.targetId' => $form->id]);

        if (!$includeDrafts) {
            $query->andWhere(['elements.draftId' => null]);
        }

        if (!$includeRevisions) {
            $query->andWhere(['elements.revisionId' => null]);
        }

        foreach ($query->all() as $info) {
            try {
                // Use the combined element cache, keyed solely by element id.
                $cacheKey = $info['id'] . '_' . $info['siteId'];
                $elementId = $info['id'];
                $siteId = $info['siteId'];
                
                if (isset($this->_cachedElements[$cacheKey])) {
                    $element = $this->_cachedElements[$cacheKey];
                } else {
                    $element = Craft::$app->getElements()->getElementById($elementId, $info['type'], $siteId);
                    $this->_cachedElements[$cacheKey] = $element;
                }

                // Use the combined field cache.
                $fieldId = $info['fieldId'];
                
                if (isset($this->_cachedFields[$fieldId])) {
                    $field = $this->_cachedFields[$fieldId];
                } else {
                    $field = Craft::$app->getFields()->getFieldById($fieldId);
                    $this->_cachedFields[$fieldId] = $field;
                }

                if (!$element) {
                    continue;
                }

                if (isset($elements[$element->id . '_' . $element->siteId])) {
                    continue;
                }

                $nestedElements = [];
                $this->_handleNestedElement($element, $field, 0, $nestedElements);

                // Sort descending by level and reassign levels.
                usort($nestedElements, function ($a, $b) {
                    return $b['level'] <=> $a['level'];
                });

                foreach ($nestedElements as $i => $nestedElement) {
                    $nestedElement['level'] = $i;
                    $elements[$nestedElement['element']->id . '_' . $nestedElement['site']->id] = $nestedElement;
                }
            } catch (Throwable $e) {
                // Just ignore any errors
            }
        }

        return array_values($elements);
    }


    // Private Methods
    // =========================================================================

    private function _handleNestedElement(ElementInterface $element, ?FieldInterface $field, int $level, array &$accumulator = []): void
    {
        try {
            $accumulator[] = [
                'element' => $element,
                'site' => $element->site,
                'field' => $field,
                'level' => $level,
            ];

            if ($element instanceof NestedElementInterface && $element->ownerId) {
                try {
                    // Retrieve (or cache) the owner element using per-site cache key.
                    $ownerId = $element->ownerId;
                    $ownerCacheKey = $ownerId . '_' . $element->siteId;

                    if (isset($this->_cachedElements[$ownerCacheKey])) {
                        $ownerElement = $this->_cachedElements[$ownerCacheKey];
                    } else {
                        $ownerElement = Craft::$app->getElements()->getElementById($ownerId, null, $element->siteId);
                        $this->_cachedElements[$ownerCacheKey] = $ownerElement;
                    }

                    // Retrieve (or cache) the owner field using its id.
                    $fieldId = $element->fieldId;

                    if (isset($this->_cachedFields[$fieldId])) {
                        $ownerField = $this->_cachedFields[$fieldId];
                    } else {
                        $ownerField = Craft::$app->getFields()->getFieldById($fieldId);
                        $this->_cachedFields[$fieldId] = $ownerField;
                    }

                    if ($ownerElement) {
                        $this->_handleNestedElement($ownerElement, $ownerField, $level + 1, $accumulator);
                    }
                } catch (Throwable $e) {
                    // Skip over owner-related errors
                }
            }
        } catch (Throwable $e) {
            // Skip over
        }
    }
}
