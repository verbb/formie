<?php
namespace verbb\formie\services;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutPageSettings;
use verbb\formie\models\FormSettings;
use verbb\formie\models\Notification;
use verbb\formie\models\RichText;
use verbb\formie\records\FormSiteOverride as FormSiteOverrideRecord;

use Craft;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Site;

use yii\base\Component;

class FormSiteOverrides extends Component
{
    // Public Methods
    // =========================================================================

    public function isEnabled(): bool
    {
        return Craft::$app->getIsMultiSite();
    }

    public function getBuilderTranslatableConfig(): array
    {
        $fieldTypes = $this->_fieldTypeTranslatableMap();

        return [
            'form' => Form::translatableRootProperties(),
            'formSettings' => FormSettings::translatableProperties(),
            'page' => FieldLayoutPage::translatableProperties(),
            'pageSettings' => FieldLayoutPageSettings::translatableProperties(),
            'notification' => Notification::translatableProperties(),
            'fieldTypes' => $fieldTypes,
            'scalarKeys' => $this->_buildScalarOverrideKeys($fieldTypes),
            'nestedKeys' => ['options', 'columns'],
        ];
    }

    public function getPrimarySiteId(): int
    {
        return (int)Craft::$app->getSites()->getPrimarySite()->id;
    }

    public function getEditableSites(?User $user = null): array
    {
        return Formie::$plugin->getFormSitePropagation()->getEditableSites($user);
    }

    public function getBuilderSitesForForm(?Form $form = null): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $editableSites = $this->getEditableSites();

        if (!$form) {
            return $editableSites;
        }

        $availableSiteIds = Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForForm($form);

        return array_values(array_filter(
            $editableSites,
            fn(Site $site) => in_array((int)$site->id, $availableSiteIds, true),
        ));
    }

    public function resolveBuilderActiveSiteId(?Form $form = null, ?int $activeSiteId = null): int
    {
        $activeSiteId ??= $this->getActiveSiteId();
        $sites = $this->getBuilderSitesForForm($form);

        if ($sites === []) {
            return $activeSiteId;
        }

        $siteIds = array_map(fn(Site $site) => (int)$site->id, $sites);

        if (in_array($activeSiteId, $siteIds, true)) {
            return $activeSiteId;
        }

        return (int)$siteIds[0];
    }

    public function getActiveSiteId(): int
    {
        $requestedSite = Cp::requestedSite();

        if ($requestedSite) {
            return (int)$requestedSite->id;
        }

        return (int)Craft::$app->getSites()->getCurrentSite()->id;
    }

    public function resolveFormTitlesForSite(array $canonicalTitles, int $siteId): array
    {
        if (!$this->isEnabled() || $canonicalTitles === []) {
            return $canonicalTitles;
        }

        $siteId = (int)$siteId;

        if ($siteId === $this->getPrimarySiteId()) {
            return $canonicalTitles;
        }

        $formIds = array_map('intval', array_keys($canonicalTitles));

        $rows = (new Query())
            ->select(['formId', 'overrides'])
            ->from(Table::FORMIE_FORM_SITE_OVERRIDES)
            ->where([
                'siteId' => $siteId,
                'formId' => $formIds,
            ])
            ->all();

        $resolved = $canonicalTitles;

        foreach ($rows as $row) {
            $formId = (int)$row['formId'];
            $overrides = Json::decodeIfJson($row['overrides'] ?? null);

            if (!is_array($overrides)) {
                continue;
            }

            $normalized = $this->normalizeOverrides($overrides);
            $title = $normalized['title'] ?? null;

            if ($title !== null && $title !== '' && array_key_exists($formId, $resolved)) {
                $resolved[$formId] = (string)$title;
            }
        }

        return $resolved;
    }

    public function getOverrides(int $formId, int $siteId): array
    {
        if (!$this->isEnabled() || $siteId === $this->getPrimarySiteId()) {
            return [];
        }

        $record = FormSiteOverrideRecord::findOne([
            'formId' => $formId,
            'siteId' => $siteId,
        ]);

        if (!$record) {
            return [];
        }

        $overrides = Json::decodeIfJson($record->overrides);

        if (!is_array($overrides)) {
            return [];
        }

        return $this->_remapLegacyOverrideKeys($formId, $this->normalizeOverrides($overrides));
    }

    public function getAllOverrides(int $formId): array
    {
        if (!$this->isEnabled() || !$formId) {
            return [];
        }

        $rows = (new Query())
            ->select(['siteId', 'overrides'])
            ->from(Table::FORMIE_FORM_SITE_OVERRIDES)
            ->where(['formId' => $formId])
            ->all();

        $result = [];

        foreach ($rows as $row) {
            $siteId = (int)$row['siteId'];
            $overrides = Json::decodeIfJson($row['overrides'] ?? null);

            if (!is_array($overrides)) {
                continue;
            }

            $normalized = $this->_remapLegacyOverrideKeys($formId, $this->normalizeOverrides($overrides));

            if ($normalized !== []) {
                $result[$siteId] = $normalized;
            }
        }

        return $result;
    }

    public function saveOverrides(int $formId, int $siteId, array $overrides): void
    {
        if (!$this->isEnabled() || !$formId || $siteId === $this->getPrimarySiteId()) {
            return;
        }

        $existing = $this->getOverrides($formId, $siteId);
        $overrides = $this->_remapLegacyOverrideKeys($formId, $this->normalizeOverrides($overrides));
        $overrides = $this->_mergeOverridePayloads($existing, $overrides);

        if ($overrides === []) {
            $this->deleteOverrides($formId, $siteId);

            return;
        }

        $record = FormSiteOverrideRecord::findOne([
            'formId' => $formId,
            'siteId' => $siteId,
        ]) ?? new FormSiteOverrideRecord([
            'formId' => $formId,
            'siteId' => $siteId,
        ]);

        $record->overrides = $overrides;
        $record->save(false);

        Formie::$plugin->getFormSitePropagation()->syncElementSiteTitles($formId);
    }

    public function deleteOverrides(int $formId, int $siteId): void
    {
        FormSiteOverrideRecord::deleteAll([
            'formId' => $formId,
            'siteId' => $siteId,
        ]);

        Formie::$plugin->getFormSitePropagation()->syncElementSiteTitles($formId);
    }

    public function applyToForm(Form $form, ?int $siteId = null, bool $clone = false): Form
    {
        if (!$this->isEnabled()) {
            return $form;
        }

        $siteId ??= (int)($form->siteId ?: Craft::$app->getSites()->getCurrentSite()->id);

        if ($siteId === $this->getPrimarySiteId()) {
            return $form;
        }

        $overrides = $this->getOverrides((int)$form->id, $siteId);

        if ($overrides === []) {
            return $form;
        }

        $target = $clone ? clone $form : $form;

        if ($clone) {
            // `clone $form` is shallow — pages/fields are shared with the canonical layout cache.
            $target->setFormLayout(unserialize(serialize($form->getFormLayout())));
            $this->_cloneNestedLayoutsForForm($target);
        }

        $this->_applyOverridesToFormElement($target, $overrides);

        return $target;
    }

    public function applyToBuilderData(array $canonicalData, ?int $siteId, ?array $overrides = null): array
    {
        if (!$this->isEnabled()) {
            return $canonicalData;
        }

        $siteId ??= $this->getPrimarySiteId();

        if ($siteId === $this->getPrimarySiteId()) {
            return $canonicalData;
        }

        $formId = (int)($canonicalData['id'] ?? 0);
        $overrides ??= $formId ? $this->getOverrides($formId, $siteId) : [];

        if ($overrides === []) {
            return $canonicalData;
        }

        return $this->mergeOverridesIntoBuilderData($canonicalData, $overrides);
    }

    public function mergeOverridesIntoBuilderData(array $canonicalData, array $overrides): array
    {
        $merged = $this->_cloneBuilderData($canonicalData);

        if (isset($overrides['title'])) {
            $merged['title'] = $overrides['title'];
        }

        if (isset($overrides['settings']) && is_array($overrides['settings'])) {
            $merged['settings'] = $this->_mergeSettingsArray(
                is_array($merged['settings'] ?? null) ? $merged['settings'] : [],
                $overrides['settings'],
            );
        }

        if (isset($overrides['pages']) && is_array($overrides['pages']) && isset($merged['pages']) && is_array($merged['pages'])) {
            $merged['pages'] = $this->_mergePagesArray($merged['pages'], $overrides['pages']);
        }

        if (isset($overrides['fields']) && is_array($overrides['fields']) && isset($merged['pages']) && is_array($merged['pages'])) {
            $merged['pages'] = $this->_mergeFieldsIntoPages($merged['pages'], $overrides['fields']);
        }

        if (isset($overrides['notifications']) && is_array($overrides['notifications']) && isset($merged['notifications']) && is_array($merged['notifications'])) {
            $merged['notifications'] = $this->_mergeNotificationsArray($merged['notifications'], $overrides['notifications']);
        }

        return $merged;
    }

    public function normalizeOverrides(array $overrides): array
    {
        $normalized = [];

        if (!empty($overrides['title'])) {
            $normalized['title'] = (string)$overrides['title'];
        }

        if (!empty($overrides['settings']) && is_array($overrides['settings'])) {
            $settings = $this->_normalizeSettingsOverrides($overrides['settings']);

            if ($settings !== []) {
                $normalized['settings'] = $settings;
            }
        }

        if (!empty($overrides['pages']) && is_array($overrides['pages'])) {
            $pages = $this->_normalizePagesOverrides($overrides['pages']);

            if ($pages !== []) {
                $normalized['pages'] = $pages;
            }
        }

        if (!empty($overrides['fields']) && is_array($overrides['fields'])) {
            $fields = $this->_normalizeFieldsOverrides($overrides['fields']);

            if ($fields !== []) {
                $normalized['fields'] = $fields;
            }
        }

        if (!empty($overrides['notifications']) && is_array($overrides['notifications'])) {
            $notifications = $this->_normalizeNotificationsOverrides($overrides['notifications']);

            if ($notifications !== []) {
                $normalized['notifications'] = $notifications;
            }
        }

        return $normalized;
    }

    public function getBuilderSiteCrumbConfig(?Form $form = null, ?int $activeSiteId = null): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $sites = $this->getBuilderSitesForForm($form);

        if (count($sites) <= 1) {
            return null;
        }

        $activeSiteId = $this->resolveBuilderActiveSiteId($form, $activeSiteId);
        $selectedSite = Craft::$app->getSites()->getSiteById($activeSiteId) ?? Craft::$app->getSites()->getCurrentSite();
        $request = Craft::$app->getRequest();
        $path = $request->getPathInfo();
        $params = $request->getQueryParamsWithoutPath();
        unset($params['fresh'], $params['site']);

        $items = array_map(function(Site $site) use ($activeSiteId, $path, $params) {
            return [
                'label' => Craft::t('site', $site->name),
                'selected' => (int)$site->id === $activeSiteId,
                'url' => UrlHelper::cpUrl($path, ['site' => $site->handle] + $params),
                'attributes' => [
                    'data' => [
                        'site-id' => $site->id,
                        'formie-site-id' => $site->id,
                    ],
                ],
            ];
        }, $sites);

        return [
            'id' => 'site-crumb',
            'icon' => 'world',
            'iconAltText' => Craft::t('app', 'Site'),
            'label' => Craft::t('site', $selectedSite->name),
            'menu' => [
                'items' => $items,
                'label' => Craft::t('app', 'Select site'),
            ],
        ];
    }

    public function getBuilderMultiSiteConfig(Form $form, ?int $activeSiteId = null): array
    {
        if (!$this->isEnabled()) {
            return [
                'enabled' => false,
            ];
        }

        $primarySiteId = $this->getPrimarySiteId();
        $activeSiteId = $this->resolveBuilderActiveSiteId($form, $activeSiteId);
        $sites = array_map(function(Site $site) use ($primarySiteId) {
            return [
                'id' => (int)$site->id,
                'name' => $site->name,
                'handle' => $site->handle,
                'language' => $site->language,
                'primary' => (int)$site->id === $primarySiteId,
            ];
        }, $this->getBuilderSitesForForm($form));

        return [
            'enabled' => count($sites) > 1,
            'primarySiteId' => $primarySiteId,
            'activeSiteId' => $activeSiteId,
            'sites' => $sites,
            'overrides' => $form->id ? $this->getAllOverrides((int)$form->id) : [],
            'layoutReadOnly' => false,
        ];
    }

    public function remapOverrideKeysForForm(int $formId, array $overrides): array
    {
        return $this->_remapLegacyOverrideKeys($formId, $overrides);
    }

    public function resolveCanonicalFormTitle(Form $form): string
    {
        return $this->_resolveCanonicalFormTitle($form);
    }


    // Private Methods
    // =========================================================================

    private function _applyOverridesToFormElement(Form $form, array $overrides): void
    {
        if (!empty($overrides['title'])) {
            $form->title = (string)$overrides['title'];
        }

        if (!empty($overrides['settings']) && is_array($overrides['settings'])) {
            $this->_applySettingsOverrides($form, $overrides['settings']);
        }

        foreach ($form->getPages() as $page) {
            $pageOverride = $this->_resolvePageOverride(
                $overrides['pages'] ?? [],
                ['id' => $page->id, 'uid' => $page->uid, '_handle' => $page->getHandle()],
            );

            if (!is_array($pageOverride)) {
                continue;
            }

            if (!empty($pageOverride['label'])) {
                $page->label = (string)$pageOverride['label'];
            }

            if (!empty($pageOverride['settings']) && is_array($pageOverride['settings'])) {
                $this->_applyPageSettingsOverrides($page, $pageOverride['settings']);
            }
        }

        foreach ($form->getFields() as $field) {
            if (!$field instanceof FieldInterface) {
                continue;
            }

            $this->_applyFieldOverridesFromPayload($field, $overrides['fields'] ?? []);
        }

        if (!empty($overrides['notifications']) && is_array($overrides['notifications'])) {
            $this->_applyNotificationOverrides($form, $overrides['notifications']);
        }
    }

    private function _applySettingsOverrides(Form $form, array $settingsOverrides): void
    {
        foreach (FormSettings::translatableProperties() as $key) {
            if (!array_key_exists($key, $settingsOverrides)) {
                continue;
            }

            $value = $settingsOverrides[$key];

            if ($form->settings->hasProperty($key)) {
                $form->settings->$key = $this->_coerceRichTextValue($value);
            }
        }
    }

    private function _applyPageSettingsOverrides(FieldLayoutPage $page, array $settingsOverrides): void
    {
        $settings = $page->getPageSettings();

        foreach (FieldLayoutPageSettings::translatableProperties() as $key) {
            if (!array_key_exists($key, $settingsOverrides)) {
                continue;
            }

            if ($settings->hasProperty($key)) {
                $settings->$key = (string)$settingsOverrides[$key];
            }
        }
    }

    private function _applyFieldOverridesFromPayload(FieldInterface $field, array $fieldOverrides): void
    {
        $resolved = $this->_resolveFieldOverride(
            $fieldOverrides,
            ['reference' => $field->reference, 'uid' => $field->uid],
        );

        if (is_array($resolved)) {
            $this->_applyFieldOverrides($field, $resolved);
        }

        if ($field instanceof ParentFieldInterface) {
            $this->_applyNestedLayoutFieldOverrides($field, $fieldOverrides);
        }
    }

    private function _applyNestedLayoutFieldOverrides(ParentFieldInterface $field, array $fieldOverrides): void
    {
        foreach ($field->getFieldLayout()->getPages() as $page) {
            foreach ($page->getRows() as $row) {
                foreach ($row->getFields() as $nestedField) {
                    if (!$nestedField instanceof FieldInterface) {
                        continue;
                    }

                    $this->_applyFieldOverridesFromPayload($nestedField, $fieldOverrides);
                }
            }
        }
    }

    private function _cloneNestedLayoutsForForm(Form $form): void
    {
        foreach ($form->getFields() as $field) {
            if ($field instanceof FieldInterface) {
                $this->_cloneNestedLayoutsForField($field);
            }
        }
    }

    private function _cloneNestedLayoutsForField(FieldInterface $field): void
    {
        if (!$field instanceof ParentFieldInterface) {
            return;
        }

        $field->setFieldLayout(unserialize(serialize($field->getFieldLayout())));

        foreach ($field->getFieldLayout()->getPages() as $page) {
            foreach ($page->getRows() as $row) {
                foreach ($row->getFields() as $nestedField) {
                    if ($nestedField instanceof FieldInterface) {
                        $this->_cloneNestedLayoutsForField($nestedField);
                    }
                }
            }
        }
    }

    private function _applyFieldOverrides(FieldInterface $field, array $fieldOverrides): void
    {
        foreach ($fieldOverrides as $key => $value) {
            if ($key === 'options' && is_array($value)) {
                $settings = $field->getSettings();
                $settings['options'] = $this->_mergeOptionsArray(
                    is_array($settings['options'] ?? null) ? $settings['options'] : [],
                    $value,
                );
                $field->setAttributes($settings, false);

                continue;
            }

            if ($key === 'columns' && is_array($value)) {
                $settings = $field->getSettings();
                $settings['columns'] = $this->_mergeColumnsArray(
                    is_array($settings['columns'] ?? null) ? $settings['columns'] : [],
                    $value,
                );
                $field->setAttributes($settings, false);

                continue;
            }

            if ($key === 'validationMessages' && is_array($value)) {
                $field->setAttributes([
                    'validationMessages' => array_replace(
                        is_array($field->validationMessages ?? null) ? $field->validationMessages : [],
                        $value,
                    ),
                ], false);

                continue;
            }

            $richTextKeys = $field::translatableRichTextProperties();

            if (in_array($key, $richTextKeys, true)) {
                if (property_exists($field, $key)) {
                    $current = $field->$key;

                    if ($current instanceof RichText) {
                        $field->$key = $this->_coerceRichTextValue($value);
                    } else {
                        $field->$key = is_string($value) ? $value : (string)($value ?? '');
                    }
                } else {
                    $field->setAttributes([$key => $this->_coerceRichTextValue($value)], false);
                }

                continue;
            }

            if (property_exists($field, $key)) {
                $field->$key = $value;
            } else {
                $field->setAttributes([$key => $value], false);
            }
        }
    }

    private function _applyNotificationOverrides(Form $form, array $notificationOverrides): void
    {
        $notifications = $form->getNotifications();

        foreach ($notifications as $notification) {
            if (!$notification instanceof Notification) {
                continue;
            }

            $override = $this->_resolveNotificationOverride(
                $notificationOverrides,
                ['handle' => $notification->handle, 'uid' => $notification->uid],
            );

            if (!is_array($override)) {
                continue;
            }

            if (array_key_exists('subject', $override)) {
                $notification->subject = (string)$override['subject'];
            }

            if (array_key_exists('fromName', $override)) {
                $notification->fromName = (string)$override['fromName'];
            }

            if (array_key_exists('replyToName', $override)) {
                $notification->replyToName = (string)$override['replyToName'];
            }

            if (array_key_exists('content', $override)) {
                $notification->content = $this->_coerceRichTextValue($override['content']);
            }
        }
    }

    private function _mergePagesArray(array $pages, array $pageOverrides): array
    {
        return array_map(function(array $page) use ($pageOverrides) {
            $override = $this->_resolvePageOverride($pageOverrides, $page);

            if (is_array($override) && !empty($override['label'])) {
                $page['label'] = $override['label'];
            }

            if (is_array($override) && !empty($override['settings']) && is_array($override['settings'])) {
                $page['settings'] = $this->_mergePageSettingsArray(
                    is_array($page['settings'] ?? null) ? $page['settings'] : [],
                    $override['settings'],
                );
            }

            return $page;
        }, $pages);
    }

    private function _mergeFieldsIntoPages(array $pages, array $fieldOverrides): array
    {
        return array_map(function(array $page) use ($fieldOverrides) {
            return $this->_mergePageFields($page, $fieldOverrides);
        }, $pages);
    }

    private function _mergePageFields(array $page, array $fieldOverrides): array
    {
        if (!isset($page['rows']) || !is_array($page['rows'])) {
            return $page;
        }

        $page['rows'] = array_map(function($row) use ($fieldOverrides) {
            if (!is_array($row) || !isset($row['fields']) || !is_array($row['fields'])) {
                return $row;
            }

            $row['fields'] = array_map(function($field) use ($fieldOverrides) {
                if (!is_array($field)) {
                    return $field;
                }

                return $this->_mergeFieldWithOverrides($field, $fieldOverrides);
            }, $row['fields']);

            return $row;
        }, $page['rows']);

        return $page;
    }

    private function _mergeFieldWithOverrides(array $field, array $fieldOverrides): array
    {
        $override = $this->_resolveFieldOverride($fieldOverrides, $field);

        if (is_array($override)) {
            $field = $this->_mergeFieldArray($field, $override);
        }

        if (isset($field['rows']) && is_array($field['rows'])) {
            $field['rows'] = $this->_mergeRowsWithFieldOverrides($field['rows'], $fieldOverrides);
        }

        if (isset($field['settings']['rows']) && is_array($field['settings']['rows'])) {
            $field['settings']['rows'] = $this->_mergeRowsWithFieldOverrides($field['settings']['rows'], $fieldOverrides);
        }

        return $field;
    }

    private function _mergeRowsWithFieldOverrides(array $rows, array $fieldOverrides): array
    {
        return array_map(function($row) use ($fieldOverrides) {
            if (!is_array($row) || !isset($row['fields']) || !is_array($row['fields'])) {
                return $row;
            }

            $row['fields'] = array_map(function($field) use ($fieldOverrides) {
                if (!is_array($field)) {
                    return $field;
                }

                return $this->_mergeFieldWithOverrides($field, $fieldOverrides);
            }, $row['fields']);

            return $row;
        }, $rows);
    }

    private function _mergeFieldArray(array $field, array $override): array
    {
        foreach ($override as $key => $value) {
            if ($key === 'options' && is_array($value)) {
                $field['options'] = $this->_mergeOptionsArray(
                    is_array($field['options'] ?? null) ? $field['options'] : [],
                    $value,
                );

                continue;
            }

            if ($key === 'columns' && is_array($value)) {
                $field['columns'] = $this->_mergeColumnsArray(
                    is_array($field['columns'] ?? null) ? $field['columns'] : [],
                    $value,
                );

                continue;
            }

            $field[$key] = $value;
        }

        return $field;
    }

    private function _mergeNotificationsArray(array $notifications, array $notificationOverrides): array
    {
        return array_map(function($notification) use ($notificationOverrides) {
            if (!is_array($notification)) {
                return $notification;
            }

            $key = $this->_getNotificationStorageKey($notification);
            $override = $key !== null
                ? $this->_resolveNotificationOverride($notificationOverrides, $notification)
                : null;

            if (!is_array($override)) {
                return $notification;
            }

            if (array_key_exists('subject', $override)) {
                $notification['subject'] = $override['subject'];
            }

            if (array_key_exists('fromName', $override)) {
                $notification['fromName'] = $override['fromName'];
            }

            if (array_key_exists('replyToName', $override)) {
                $notification['replyToName'] = $override['replyToName'];
            }

            if (array_key_exists('content', $override)) {
                $notification['content'] = $override['content'];
            }

            return $notification;
        }, $notifications);
    }

    private function _mergeSettingsArray(array $settings, array $settingsOverrides): array
    {
        foreach (FormSettings::translatableProperties() as $key) {
            if (array_key_exists($key, $settingsOverrides)) {
                $settings[$key] = $settingsOverrides[$key];
            }
        }

        return $settings;
    }

    private function _mergePageSettingsArray(array $settings, array $settingsOverrides): array
    {
        foreach (FieldLayoutPageSettings::translatableProperties() as $key) {
            if (array_key_exists($key, $settingsOverrides)) {
                $settings[$key] = $settingsOverrides[$key];
            }
        }

        return $settings;
    }

    private function _mergeColumnsArray(array $columns, array $columnOverrides): array
    {
        $overridesByHandle = [];

        foreach ($columnOverrides as $override) {
            if (!is_array($override)) {
                continue;
            }

            $handle = trim((string)($override['handle'] ?? ''));

            if ($handle !== '') {
                $overridesByHandle[$handle] = $override;
            }
        }

        return array_map(function($column) use ($overridesByHandle) {
            if (!is_array($column)) {
                return $column;
            }

            $handle = trim((string)($column['handle'] ?? ''));
            $override = $handle !== '' ? ($overridesByHandle[$handle] ?? null) : null;

            if (is_array($override) && array_key_exists('heading', $override)) {
                $column['heading'] = $override['heading'];
            }

            return $column;
        }, $columns);
    }

    private function _mergeOptionsArray(array $options, array $optionOverrides): array
    {
        $overridesByCanonicalValue = [];

        foreach ($optionOverrides as $option) {
            if (!is_array($option)) {
                continue;
            }

            $value = (string)($option['value'] ?? '');

            if ($value !== '') {
                $overridesByCanonicalValue[$value] = $option;
            }
        }

        $legacyOverrides = array_values(array_filter(
            $optionOverrides,
            function($option) use ($options) {
                if (!is_array($option) || array_key_exists('optgroup', $option)) {
                    return false;
                }

                $value = (string)($option['value'] ?? '');

                foreach ($options as $canonicalOption) {
                    if (!is_array($canonicalOption) || array_key_exists('optgroup', $canonicalOption)) {
                        continue;
                    }

                    if ((string)($canonicalOption['value'] ?? '') === $value) {
                        return false;
                    }
                }

                return true;
            },
        ));

        return array_map(function($option) use ($legacyOverrides, $overridesByCanonicalValue) {
            if (!is_array($option) || array_key_exists('optgroup', $option)) {
                return $option;
            }

            $canonicalValue = (string)($option['value'] ?? '');
            $override = $overridesByCanonicalValue[$canonicalValue] ?? null;

            if (!is_array($override)) {
                $override = $this->_resolveLegacyOptionOverride($option, $legacyOverrides);
            }

            if (!is_array($override)) {
                return $option;
            }

            if (array_key_exists('label', $override)) {
                $option['label'] = $override['label'];
            }

            if (array_key_exists('optionValue', $override)) {
                $option['value'] = $override['optionValue'];
            } elseif (
                array_key_exists('value', $override)
                && (string)$override['value'] !== $canonicalValue
            ) {
                $option['value'] = $override['value'];
            }

            return $option;
        }, $options);
    }

    private function _resolveLegacyOptionOverride(array $option, array $legacyOverrides): ?array
    {
        $canonicalLabel = (string)($option['label'] ?? '');

        foreach ($legacyOverrides as $override) {
            if (!is_array($override) || array_key_exists('optgroup', $override)) {
                continue;
            }

            $overrideLabel = (string)($override['label'] ?? '');

            if ($canonicalLabel === '') {
                continue;
            }

            if (
                $overrideLabel === $canonicalLabel
                || str_starts_with($overrideLabel, $canonicalLabel . ' ')
                || str_starts_with($overrideLabel, $canonicalLabel . '(')
            ) {
                return $override;
            }
        }

        return null;
    }

    private function _normalizeSettingsOverrides(array $settings): array
    {
        $normalized = [];

        foreach (FormSettings::translatableProperties() as $key) {
            if (array_key_exists($key, $settings) && $settings[$key] !== null && $settings[$key] !== '') {
                $normalized[$key] = $settings[$key];
            }
        }

        return $normalized;
    }

    private function _normalizePageSettingsOverrides(array $settings): array
    {
        $normalized = [];

        foreach (FieldLayoutPageSettings::translatableProperties() as $key) {
            if (!empty($settings[$key])) {
                $normalized[$key] = (string)$settings[$key];
            }
        }

        return $normalized;
    }

    private function _normalizePagesOverrides(array $pages): array
    {
        $normalized = [];

        foreach ($pages as $pageUid => $pageOverride) {
            if (!is_array($pageOverride)) {
                continue;
            }

            $entry = [];

            if (!empty($pageOverride['label'])) {
                $entry['label'] = (string)$pageOverride['label'];
            }

            if (!empty($pageOverride['settings']) && is_array($pageOverride['settings'])) {
                $settings = $this->_normalizePageSettingsOverrides($pageOverride['settings']);

                if ($settings !== []) {
                    $entry['settings'] = $settings;
                }
            }

            if ($entry !== []) {
                $normalized[(string)$pageUid] = $entry;
            }
        }

        return $normalized;
    }

    private function _normalizeFieldsOverrides(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $fieldUid => $fieldOverride) {
            if (!is_array($fieldOverride)) {
                continue;
            }

            $entry = [];

            foreach ($fieldOverride as $key => $value) {
                if ($key === 'options' && is_array($value) && $value !== []) {
                    $entry['options'] = $value;
                    continue;
                }

                if ($key === 'columns' && is_array($value) && $value !== []) {
                    $entry['columns'] = $value;
                    continue;
                }

                if ($value !== null && $value !== '') {
                    if (in_array($key, ['instructions', 'validationMessages'], true) && $value === []) {
                        continue;
                    }

                    $entry[$key] = $value;
                }
            }

            if ($entry !== []) {
                $normalized[(string)$fieldUid] = $entry;
            }
        }

        return $normalized;
    }

    private function _normalizeNotificationsOverrides(array $notifications): array
    {
        $normalized = [];

        foreach ($notifications as $uid => $notificationOverride) {
            if (!is_array($notificationOverride)) {
                continue;
            }

            $entry = [];

            if (!empty($notificationOverride['subject'])) {
                $entry['subject'] = (string)$notificationOverride['subject'];
            }

            if (!empty($notificationOverride['fromName'])) {
                $entry['fromName'] = (string)$notificationOverride['fromName'];
            }

            if (!empty($notificationOverride['replyToName'])) {
                $entry['replyToName'] = (string)$notificationOverride['replyToName'];
            }

            if (!empty($notificationOverride['content'])) {
                $entry['content'] = $notificationOverride['content'];
            }

            if ($entry !== []) {
                $normalized[(string)$uid] = $entry;
            }
        }

        return $normalized;
    }

    private function _getFieldStorageKey(array $field): ?string
    {
        $reference = trim((string)($field['reference'] ?? ''));

        if ($reference !== '') {
            return $reference;
        }

        $uid = trim((string)($field['uid'] ?? ''));

        return $uid !== '' ? $uid : null;
    }

    private function _getPageStorageKey(array $page): ?string
    {
        $uid = trim((string)($page['uid'] ?? ''));

        if ($uid !== '') {
            return $uid;
        }

        if (isset($page['id']) && $page['id'] !== null && $page['id'] !== '') {
            return (string)$page['id'];
        }

        return null;
    }

    private function _getNotificationStorageKey(array $notification): ?string
    {
        $handle = trim((string)($notification['handle'] ?? ''));

        if ($handle !== '') {
            return $handle;
        }

        $uid = trim((string)($notification['uid'] ?? ''));

        return $uid !== '' ? $uid : null;
    }

    private function _resolveFieldOverride(array $fieldOverrides, array $field): ?array
    {
        $storageKey = $this->_getFieldStorageKey($field);

        if ($storageKey !== null && isset($fieldOverrides[$storageKey]) && is_array($fieldOverrides[$storageKey])) {
            return $fieldOverrides[$storageKey];
        }

        $uid = trim((string)($field['uid'] ?? ''));

        if ($uid !== '' && isset($fieldOverrides[$uid]) && is_array($fieldOverrides[$uid])) {
            return $fieldOverrides[$uid];
        }

        return null;
    }

    private function _resolvePageOverride(array $pageOverrides, array $page): ?array
    {
        $storageKey = $this->_getPageStorageKey($page);

        if ($storageKey !== null && isset($pageOverrides[$storageKey]) && is_array($pageOverrides[$storageKey])) {
            return $pageOverrides[$storageKey];
        }

        if (isset($page['id']) && $page['id'] !== null && $page['id'] !== '') {
            $id = (string)$page['id'];

            if (isset($pageOverrides[$id]) && is_array($pageOverrides[$id])) {
                return $pageOverrides[$id];
            }
        }

        $handle = trim((string)($page['_handle'] ?? $page['handle'] ?? ''));

        if ($handle !== '' && isset($pageOverrides[$handle]) && is_array($pageOverrides[$handle])) {
            return $pageOverrides[$handle];
        }

        return null;
    }

    private function _resolveNotificationOverride(array $notificationOverrides, array $notification): ?array
    {
        $storageKey = $this->_getNotificationStorageKey($notification);

        if ($storageKey !== null && isset($notificationOverrides[$storageKey]) && is_array($notificationOverrides[$storageKey])) {
            return $notificationOverrides[$storageKey];
        }

        $uid = trim((string)($notification['uid'] ?? ''));

        if ($uid !== '' && isset($notificationOverrides[$uid]) && is_array($notificationOverrides[$uid])) {
            return $notificationOverrides[$uid];
        }

        return null;
    }

    private function _buildOverrideKeyMaps(int $formId): array
    {
        $form = Form::find()->id($formId)->status(null)->one();

        if (!$form) {
            return [
                'fields' => [],
                'pages' => [],
                'notifications' => [],
            ];
        }

        $fieldUidToReference = [];

        foreach ($form->getFields() as $field) {
            if (!$field instanceof FieldInterface) {
                continue;
            }

            $this->_collectFieldReferenceMappings($field, $fieldUidToReference);
        }

        $pageKeyMap = [];

        foreach ($form->getPages() as $page) {
            $uid = trim((string)$page->uid);
            $id = trim((string)$page->id);
            $handle = trim((string)($page->getHandle() ?? ''));

            if ($uid === '') {
                continue;
            }

            if ($id !== '') {
                $pageKeyMap[$id] = $uid;
            }

            if ($handle !== '') {
                $pageKeyMap[$handle] = $uid;
            }
        }

        $notificationUidToHandle = [];

        foreach ($form->getNotifications() as $notification) {
            if (!$notification instanceof Notification) {
                continue;
            }

            $handle = trim((string)($notification->handle ?? ''));
            $uid = trim((string)$notification->uid);

            if ($uid !== '' && $handle !== '') {
                $notificationUidToHandle[$uid] = $handle;
            }
        }

        return [
            'fields' => $fieldUidToReference,
            'pages' => $pageKeyMap,
            'notifications' => $notificationUidToHandle,
        ];
    }

    private function _collectFieldReferenceMappings(FieldInterface $field, array &$fieldUidToReference): void
    {
        $reference = trim((string)$field->reference);
        $uid = trim((string)$field->uid);

        if ($uid !== '' && $reference !== '') {
            $fieldUidToReference[$uid] = $reference;
        }

        if ($field instanceof ParentFieldInterface) {
            foreach ($field->getFieldLayout()->getPages() as $page) {
                foreach ($page->getRows() as $row) {
                    foreach ($row->getFields() as $nestedField) {
                        if ($nestedField instanceof FieldInterface) {
                            $this->_collectFieldReferenceMappings($nestedField, $fieldUidToReference);
                        }
                    }
                }
            }
        }
    }

    private function _mergeOverridePayloads(array $existing, array $incoming): array
    {
        if ($existing === []) {
            return $incoming;
        }

        if ($incoming === []) {
            return $existing;
        }

        $merged = $existing;

        if (array_key_exists('title', $incoming)) {
            $merged['title'] = $incoming['title'];
        }

        foreach (['settings', 'pages', 'fields', 'notifications'] as $section) {
            if (!isset($incoming[$section]) || !is_array($incoming[$section])) {
                continue;
            }

            $merged[$section] = $merged[$section] ?? [];

            foreach ($incoming[$section] as $key => $value) {
                if (
                    isset($merged[$section][$key])
                    && is_array($merged[$section][$key])
                    && is_array($value)
                ) {
                    $merged[$section][$key] = array_replace($merged[$section][$key], $value);
                } else {
                    $merged[$section][$key] = $value;
                }
            }
        }

        return $this->normalizeOverrides($merged);
    }

    private function _remapLegacyOverrideKeys(int $formId, array $overrides): array
    {
        if ($formId <= 0) {
            return $overrides;
        }

        $maps = $this->_buildOverrideKeyMaps($formId);

        foreach (['fields', 'pages', 'notifications'] as $section) {
            if (empty($overrides[$section]) || !is_array($overrides[$section])) {
                continue;
            }

            $map = $maps[$section];
            $remapped = [];

            foreach ($overrides[$section] as $key => $value) {
                $stableKey = $map[(string)$key] ?? (string)$key;

                if (isset($remapped[$stableKey]) && is_array($remapped[$stableKey]) && is_array($value)) {
                    $remapped[$stableKey] = array_replace($remapped[$stableKey], $value);
                } else {
                    $remapped[$stableKey] = $value;
                }
            }

            $overrides[$section] = $remapped;
        }

        return $this->normalizeOverrides($overrides);
    }

    private function _resolveCanonicalFormTitle(Form $form): string
    {
        if (!$form->id) {
            return (string)$form->title;
        }

        $primarySiteId = $this->getPrimarySiteId();
        $title = (new Query())
            ->select(['title'])
            ->from([CraftTable::ELEMENTS_SITES])
            ->where(['elementId' => (int)$form->id, 'siteId' => $primarySiteId])
            ->scalar();

        if (is_string($title) && $title !== '') {
            return $title;
        }

        return (string)$form->title;
    }

    private function _cloneBuilderData(array $data): array
    {
        $cloned = Json::decode(Json::encode($data), true);

        return is_array($cloned) ? $cloned : $data;
    }

    private function _coerceRichTextValue(mixed $value): RichText
    {
        if ($value instanceof RichText) {
            return $value;
        }

        if (is_array($value)) {
            return RichText::from($value);
        }

        return RichText::from((string)$value);
    }

    private function _fieldTypeTranslatableMap(): array
    {
        $map = [];

        foreach (Formie::$plugin->getFields()->getResolvedRegisteredFieldTypes() as $fieldType) {
            if (!is_subclass_of($fieldType, FieldInterface::class)) {
                continue;
            }

            $map[$fieldType] = $fieldType::translatableProperties();
        }

        return $map;
    }

    private function _buildScalarOverrideKeys(array $fieldTypes): array
    {
        $nested = ['options', 'columns', 'validationMessages'];
        $keys = array_merge(
            Form::translatableRootProperties(),
            FormSettings::translatableProperties(),
            FieldLayoutPage::translatableProperties(),
            FieldLayoutPageSettings::translatableProperties(),
            Notification::translatableProperties(),
        );

        foreach ($fieldTypes as $properties) {
            foreach ($properties as $property) {
                if (!in_array($property, $nested, true)) {
                    $keys[] = $property;
                }
            }
        }

        return array_values(array_unique($keys));
    }
}
