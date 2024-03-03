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
use verbb\formie\models\FormLayout;
use verbb\formie\models\FormSettings;
use verbb\formie\records\Form as FormRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
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
                Formie::$plugin->getStencils()->applyStencil($form, $stencil);
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
        $settings = Formie::$plugin->getSettings();
        $includeDrafts = $settings->includeDraftElementUsage;
        $includeRevisions = $settings->includeRevisionElementUsage;

        if ($form) {
            $query = (new Query())
                ->select(['elements.id', 'elements.type', 'relations.fieldId'])
                ->from(['relations' => Table::RELATIONS])
                ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[relations.sourceId]]')
                ->where(['relations.targetId' => $form->id]);

            if (!$includeDrafts) {
                $query->andWhere(['elements.draftId' => null]);
            }

            if (!$includeRevisions) {
                $query->andWhere(['elements.revisionId' => null]);
            }

            return $query->all();
        }

        return [];
    }
}
