<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\UrlHelper;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FormInstanceConfig;
use verbb\formie\models\SubmissionConfig;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use Twig\Error\RuntimeError;

final class RuntimeConfiguration
{
    // Constants
    // =========================================================================

    public const DURABLE_FORM_SETTINGS = ['completionBehavior', 'completionRedirectSource', 'redirectUrl', 'redirectTarget', 'successMessage', 'hideFormAfterSubmit', 'enableRedirectRules', 'redirectRules', 'integrations'];
    public const FORM_SETTINGS = [...self::DURABLE_FORM_SETTINGS, 'displayFormTitle', 'displayCurrentPageTitle', 'displayPageTabs', 'displayPageProgress', 'scrollToTop', 'submitMethod', 'successMessageTimeout', 'successMessagePosition', 'errorMessage', 'defaultLabelPosition', 'defaultInstructionsPosition'];
    public const PAGE_SETTINGS = ['submitButtonLabel', 'backButtonLabel', 'saveButtonLabel', 'showBackButton', 'showSaveButton', 'saveButtonStyle', 'buttonsPosition', 'submitButtonPlacement', 'cssClasses', 'containerAttributes', 'inputAttributes'];


    // Public Methods
    // =========================================================================

    public function isolate(Form $form): void
    {
        $form->settings = clone $form->settings;
        $form->settings->setForm($form);
        $form->setFormLayout($this->cloneLayout($form->getFormLayout(), $form));
    }

    public function cloneLayout(FieldLayout $source, Form $form): FieldLayout
    {
        $layout = clone $source;
        $pages = [];

        foreach ($source->getPages() as $sourcePage) {
            $page = clone $sourcePage;
            $page->setPageSettings($sourcePage->getPageSettings()?->toArray());
            $rows = [];

            foreach ($sourcePage->getRows() as $sourceRow) {
                $row = clone $sourceRow;
                $fields = [];

                foreach ($sourceRow->getFields() as $sourceField) {
                    $field = clone $sourceField;

                    if ($field instanceof Field) {
                        $field->setInstanceForm($form);
                    }

                    if ($field instanceof ParentFieldInterface) {
                        $field->setFieldLayout($this->cloneLayout($sourceField->getFieldLayout(), $form));
                    }
                    $fields[] = $field;
                }
                $row->setFields($fields);
                $rows[] = $row;
            }
            $page->setRows($rows);
            $pages[] = $page;
        }
        $layout->setPages($pages);
        return $layout;
    }

    public function findField(Form $form, string $path, bool $required = true): ?Field
    {
        foreach ($form->getFieldsRecursively() as $field) {
            if ($field->uid === $path) {
                return $field;
            }
        }
        $owner = $form;

        foreach (explode('.', $path) as $handle) {
            $owner = method_exists($owner, 'getFieldByHandle') ? $owner->getFieldByHandle($handle) : null;

            if (!$owner) {
                break;
            }
        }

        if (!$owner instanceof Field && $required) {
            throw new RuntimeError('Unknown runtime field target: ' . $path);
        }
        return $owner instanceof Field ? $owner : null;
    }

    public function populationValue(Field $field, mixed $value, ?Submission $submission = null): mixed
    {
        $hasObjects = static function(mixed $item) use (&$hasObjects): bool {
            if (is_array($item)) {
                foreach ($item as $child) {
                    if ($hasObjects($child)) {
                        return true;
                    }
                }
                return false;
            }
            return is_object($item);
        };

        // Trusted authoring may supply element queries or typed field values.
        // Freeze those through the owning field's WS05 projection before persistence.
        if ($hasObjects($value)) {
            $value = $field->serializeValueForClientInput($field->normalizeValue($value, $submission), $submission);
        }
        return \verbb\formie\content\FieldStorageCodec::assertSafe($value);
    }

    public function validateSettings(object $model, array $settings, array $allowed, string $target): array
    {
        $unknown = array_diff(array_keys($settings), $allowed);

        if ($unknown) {
            throw new RuntimeError('Forbidden or unknown runtime settings for ' . $target . ': ' . implode(', ', $unknown));
        }

        foreach ($settings as $name => $value) {
            if (!$model->canSetProperty($name)) {
                throw new RuntimeError('Unsupported runtime setting for ' . $target . ': ' . $name);
            }
        }
        $copy = clone $model;

        try {
            $copy->setAttributes($settings, false);

            if (!$copy->validate(array_keys($settings))) {
                throw new RuntimeError(Json::encode($copy->getErrors()));
            }
        } catch (\Throwable $e) {
            throw new RuntimeError('Invalid runtime settings for ' . $target . ': ' . $e->getMessage(), -1, null, $e);
        }
        $values = $copy->getAttributes(array_keys($settings));

        // Rich-text settings expose typed models/Markup internally; retain their
        // explicit authored representation in the immutable configuration.
        foreach ($values as $name => $value) {
            if ($value instanceof \Twig\Markup) {
                $values[$name] = (string)$value;
            } elseif ($value instanceof \verbb\formie\models\RichText) {
                $values[$name] = $value->getValue();
            }
        }
        return $values;
    }

    public function validateIntegrationSettings(string $handle, array $settings): array
    {
        $service = Formie::$plugin->getIntegrations();
        $connection = $service->getIntegrationByHandle($handle) ?? $service->getCaptchaByHandle($handle);

        try {
            $binding = \verbb\formie\models\FormIntegration::fromSettings($connection, $settings);
            $runtime = $binding->createRuntime();
            $attributes = array_values(array_diff(array_keys($settings), ['execution']));

            if (!$runtime->validate($attributes)) {
                throw new \InvalidArgumentException(Json::encode($runtime->getErrors()));
            }
            return array_replace($settings, $runtime->getAttributes($attributes));
        } catch (\Throwable $e) {
            throw new RuntimeError('Invalid runtime integration settings for ' . $handle . ': ' . $e->getMessage(), -1, null, $e);
        }
    }

    public function apply(Form $form, FormInstanceConfig $config): void
    {
        $this->isolate($form);
        $form->settings->setAttributes(FormInstanceConfig::merge($form->settings->getAttributes(array_keys($config->form)), $config->form), false);
        $this->_applyFieldSettings($form->getFormLayout(), $config->fields);

        foreach ($form->getPages() as $page) {
            if (isset($config->pages[$page->uid])) {
                $settings = $config->pages[$page->uid];
                $page->getPageSettings()->setAttributes(FormInstanceConfig::merge($page->getPageSettings()->getAttributes(array_keys($settings)), $settings), false);
            }
        }
    }

    public function establish(Form $form, ?array $hostQuery = null): void
    {
        if ($form->isInstanceEstablished()) {
            return;
        }
        $form->markInstanceEstablished();
        $request = Craft::$app->getRequest();
        $query = $hostQuery ?? ($request->getIsConsoleRequest() || $request->getIsPost() ? [] : $request->getQueryParams());
        $config = $form->getInstanceConfig()->with('query', UrlHelper::filterRedirectQueryParams($query));
        $prefill = [];

        foreach ($form->getFieldsRecursively() as $field) {
            if ($field instanceof \verbb\formie\fields\Hidden && !$field->isAuthoritativeSource() && $field->valueSource !== 'custom') {
                $prefill[$field->uid] = $field->valueSource === 'query'
                    ? ($query[$field->queryParameter ?? ''] ?? null) : $field->getDefaultValue();
            }
            $key = $field->prefillQueryParam;

            if ($key && array_key_exists($key, $query) && (is_scalar($query[$key]) || is_array($query[$key]))) {
                // Store raw input. Field normalization owns its type; no reference parsing.
                $prefill[$field->uid] = $query[$key];
            }
        }
        $form->replaceInstanceConfig($config->with('prefill', $prefill));
    }

    public function persistInstance(Form $form): ?string
    {
        if (!$form->id || $form->getCurrentSubmission()?->id) {
            return null;
        }
        $this->establish($form);
        $data = $form->getInstanceConfig()->toArray();

        if (!array_filter(array_diff_key($data, ['version' => true]))) {
            return null;
        }
        $key = Craft::$app->getSecurity()->generateRandomString(40);
        $encrypted = Craft::$app->getSecurity()->encryptByKey(Json::encode($data), Craft::$app->getConfig()->getGeneral()->securityKey);
        Craft::$app->getDb()->createCommand()->insert('{{%formie_instance_configs}}', [
            'tokenHash' => hash('sha256', $key), 'formId' => $form->id, 'siteId' => $form->siteId,
            'config' => base64_encode($encrypted), 'expiresAt' => time() + SubmissionOperations::RETENTION_SECONDS,
        ])->execute();
        return $key;
    }

    public function restoreToken(Form $form, string $requestToken): void
    {
        $raw = Craft::$app->getSecurity()->validateData($requestToken);
        $token = $raw === false ? null : Json::decodeIfJson($raw);

        if (!is_array($token) || ($token['form'] ?? null) !== $form->uid || (int)($token['site'] ?? 0) !== (int)$form->siteId) {
            return;
        }
        // Tokens issued before WS09 have no config. Never read an ambient session snapshot.
        $config = new FormInstanceConfig();

        if (!empty($token['config'])) {
            $row = (new Query())->from('{{%formie_instance_configs}}')->where([
                'tokenHash' => hash('sha256', $token['config']), 'formId' => $form->id, 'siteId' => $form->siteId,
            ])->andWhere(['>', 'expiresAt', time()])->one();

            if (!$row) {
                throw new \yii\web\ForbiddenHttpException('Form instance has expired. Reload the form.');
            }
            $json = Craft::$app->getSecurity()->decryptByKey(base64_decode($row['config']), Craft::$app->getConfig()->getGeneral()->securityKey);

            if ($json === false) {
                throw new \RuntimeException('Unable to decrypt form instance configuration.');
            }
            $config = SubmissionConfig::decode(Json::decode($json), $form);
        }
        $form->markInstanceEstablished();
        $form->replaceInstanceConfig($config);
    }

    public function applyValues(Submission $submission): void
    {
        $form = $submission->getForm();
        $config = $form->getInstanceConfig();
        $submission->getContentManager(); // Hydrate deferred persisted content before testing presence.

        foreach ($form->getFields() as $field) {
            $state = $submission->getContentState();
            $uid = $field->uid;
            $present = array_key_exists($uid, $state->rawValuesByUid) || array_key_exists($uid, $state->normalizedValuesByUid);
            $value = $present ? $submission->getFieldValue($field->handle) : null;
            [$value, $changed] = $this->_resolveValue($field, $value, $present, $config, $submission);

            if ($changed) {
                $submission->setFieldValue($field->handle, $value);
            }
        }
    }

    // Private Methods
    // =========================================================================

    private function _resolveValue(Field $field, mixed $value, bool $present, FormInstanceConfig $config, Submission $submission): array
    {
        $changed = false;

        if (array_key_exists($field->uid, $config->forced)) {
            return [$config->forced[$field->uid], true];
        }

        if ($field instanceof \verbb\formie\fields\Hidden && $field->isAuthoritativeSource()) {
            return [$field->getDefaultValue(), true];
        }

        if (!$present) {
            $value = $field->getInitialValue($submission);
            $changed = true;
        }

        if ($field instanceof ParentFieldInterface) {
            // Parent fields own the writable parts shape for rich domain values.
            $raw = is_array($value) ? $value : $field->serializeValueForClientInput($value, $submission);
            $repeatable = $field instanceof \verbb\formie\base\RepeatableParentFieldInterface;
            $rows = $repeatable ? (is_array($raw) ? $raw : []) : [is_array($raw) ? $raw : []];

            foreach ($rows as $index => $row) {
                $row = is_array($row) ? $row : [];

                foreach ($field->getFields($repeatable ? $index : null) as $child) {
                    [$childValue, $childChanged] = $this->_resolveValue($child, $row[$child->handle] ?? null, array_key_exists($child->handle, $row), $config, $submission);

                    if ($childChanged) {
                        $row[$child->handle] = $childValue;
                        $changed = true;
                    }
                }
                $rows[$index] = $row;
            }

            if ($changed) {
                $value = $repeatable ? $rows : $rows[0];
            }
        }
        return [$value, $changed];
    }

    private function _applyFieldSettings(FieldLayout $layout, array $overrides): void
    {
        foreach ($layout->getFields() as $field) {
            if (isset($overrides[$field->uid])) {
                $settings = $overrides[$field->uid];
                $field->setAttributes(FormInstanceConfig::merge($field->getAttributes(array_keys($settings)), $settings), false);
            }

            if ($field instanceof ParentFieldInterface) {
                $this->_applyFieldSettings($field->getFieldLayout(), $overrides);
            }
        }
    }

}
