<?php
namespace verbb\formie\references;

use verbb\formie\base\ContainerParentFieldInterface;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\conditions\ConditionMigration;
use verbb\formie\elements\Form;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Payment;
use verbb\formie\helpers\FieldTraversal;
use verbb\formie\models\ReferenceExpression;
use verbb\formie\models\RichText;

use craft\helpers\Json;

/** Lossless read/save migration restricted to known reference-bearing settings. */
final class ReferenceMigration
{
    // Static Methods
    // =========================================================================

    public static function integrationSlots(array $settings): array
    {
        foreach ($settings as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (str_ends_with(strtolower((string)$key), 'fieldmapping') || in_array($key, ['attributeMapping', 'emailSendMapping'], true)) {
                foreach ($value as $destination => $slot) {
                    $settings[$key][$destination] = ReferenceSlot::fromStored($slot)->toArray();
                }
            } else {
                $settings[$key] = self::integrationSlots($value);
            }
        }

        return $settings;
    }

    public static function canonicalFieldTokens(Form $form, string $value): string
    {
        return self::canonicalFieldTokensForFields($form->getFields(), $value);
    }

    /**
     * Formie 3 read these settings as object templates, where `{handle}`, `{handle.part}`, `{field.handle}`, `{group[child]}`
     * and `{field:handle.__toString}` all named a field on the submission. A definition shared between forms keeps a readable path.
     */
    public static function legacyTextTokens(array $fields, string $value, bool $useReferences = true): string
    {
        if (!str_contains($value, '{')) {
            return $value;
        }

        return preg_replace_callback('/(?<!\{)\{([^{}\s|;:"]+|field:[^{}\s|;:"]+)\}(?!\})/', static function(array $match) use ($fields, $useReferences): string {
            $expression = ReferenceParser::parse($match[0]);

            // Current tokens and Formie's own variables, such as `{timestamp}` or `{formName}`, already mean something.
            if ($expression->isValid && $expression->target !== 'field') {
                return $match[0];
            }

            $isBare = !preg_match('/^field[:.]/', $match[1]);
            $path = preg_replace('/^field[:.]/', '', $match[1]);

            if ($isBare && ($token = self::_legacySubmissionToken($path, $fields))) {
                return $token;
            }

            $path = preg_replace('/\.__toString$/D', '', str_replace(['[', ']'], ['.', ''], $path));
            $target = self::_legacyFieldPath($fields, $path);

            if (!$target) {
                return $match[0];
            }

            [$field, $selector] = $target;
            $identifier = (string)$field->reference;

            if (!$useReferences) {
                $identifier = $selector === '' ? $path : substr($path, 0, -strlen($selector) - 1);
            }

            return ReferenceParser::serialize(new ReferenceExpression(raw: $match[0], target: 'field', identifier: $identifier, selector: $selector, isValid: true));
        }, $value);
    }

    public static function legacyFormSettings(array $settings, array $fields): array
    {
        $settings = self::_legacySettings($settings, ['submissionTitleFormat', 'redirectUrl', 'successMessage'], $fields, true);

        if (is_array($settings['integrations'] ?? null)) {
            foreach ($settings['integrations'] as $handle => $integration) {
                if (is_array($integration)) {
                    $settings['integrations'][$handle] = self::_legacySettings($integration, ['message', 'webhook', 'to'], $fields, true);
                }
            }
        }

        return $settings;
    }

    public static function legacyFieldSettings(array $settings, FieldInterface $field, array $fields, bool $useReferences = true): array
    {
        if ($field instanceof FileUpload) {
            foreach (['uploadLocationSubpath', 'filenameFormat'] as $key) {
                if (is_string($settings[$key] ?? null)) {
                    $settings[$key] = self::_legacyPathFilters($fields, $settings[$key], $useReferences);
                }
            }

            return self::_legacySettings($settings, ['uploadLocationSubpath', 'filenameFormat'], $fields, $useReferences);
        }

        if ($field instanceof Hidden) {
            return self::_legacySettings($settings, ['defaultValue'], $fields, $useReferences);
        }

        if ($field instanceof Payment && is_array($settings['providerSettings'] ?? null)) {
            foreach ($settings['providerSettings'] as $handle => $provider) {
                if (is_array($provider)) {
                    $settings['providerSettings'][$handle] = self::_legacySettings($provider, ['amountVariable', 'currencyVariable', 'paymentDescription', 'paymentReceiptEmail', 'billingDetails', 'metadata', 'subscriptionLimitVariable', 'subscriptionSetupFeeVariable', 'subscriptionSetupFeeDescription'], $fields, $useReferences);
                }
            }
        }

        return $settings;
    }

    /** Older exports and stencils hold the same tokens as stored settings did, and only reach the database through here. */
    public static function legacyFormTokens(Form $form): void
    {
        $fields = $form->getFields();
        $settings = $form->settings;

        $settings->submissionTitleFormat = self::_legacyValue($settings->submissionTitleFormat, $fields, true);
        $settings->redirectUrl = self::_legacyValue($settings->redirectUrl, $fields, true);
        $settings->successMessage = RichText::from(self::_legacyValue($settings->successMessage->getSchema(), $fields, true));
        $settings->integrations = self::legacyFormSettings(['integrations' => $settings->integrations], $fields)['integrations'];

        foreach ($form->getPages() as $page) {
            $pageSettings = $page->getPageSettings();
            $pageSettings->pageConditions = ConditionMigration::legacyFieldTokens($pageSettings->pageConditions, $form);
            $pageSettings->nextButtonConditions = ConditionMigration::legacyFieldTokens($pageSettings->nextButtonConditions, $form);
        }

        foreach ($form->getNotifications() as $notification) {
            foreach (['conditions', 'toConditions'] as $attribute) {
                if (is_array($notification->$attribute)) {
                    $notification->$attribute = ConditionMigration::legacyFieldTokens($notification->$attribute, $form);
                }
            }
        }

        foreach (FieldTraversal::recursively($fields) as $field) {
            if (is_array($field->conditions ?? null)) {
                $field->conditions = ConditionMigration::legacyFieldTokens($field->conditions, $form);
            }

            $values = self::legacyFieldSettings(['uploadLocationSubpath' => $field->uploadLocationSubpath ?? null, 'filenameFormat' => $field->filenameFormat ?? null, 'defaultValue' => $field->defaultValue ?? null, 'providerSettings' => $field->providerSettings ?? null], $field, $fields);

            foreach ($values as $key => $value) {
                if ($value !== null && property_exists($field, $key)) {
                    $field->$key = $value;
                }
            }
        }
    }

    public static function canonicalFieldTokensForFields(array $fields, string $value): string
    {
        if (!str_contains($value, '{field:')) {
            return $value;
        }

        return preg_replace_callback('/\{field:[^{}]+\}/', static function(array $match) use ($fields): string {
            $expression = ReferenceParser::parse($match[0]);

            // Formie 3 stored field selectors as handle.selector, including Phone, Name and Address parts,
            // and Group children as group.child. Unknown paths must remain available for diagnosis.
            if ($expression->isValid && $expression->target === 'field' && $expression->selector === '' && str_contains($expression->identifier, '.')) {
                $path = self::_legacyFieldPath($fields, $expression->identifier);

                if ($path) {
                    return ReferenceParser::serialize(new ReferenceExpression(
                        raw: $expression->raw,
                        target: 'field',
                        identifier: (string)$path[0]->reference,
                        selector: $path[1],
                        default: $expression->default,
                        transformerId: $expression->transformerId,
                        transformerParams: $expression->transformerParams,
                        isValid: true,
                    ));
                }
            }

            if (!$expression->isValid || $expression->target !== 'field' || $expression->selector === '') {
                return $match[0];
            }

            $field = null;

            foreach ($fields as $candidate) {
                if (in_array($expression->identifier, [(string)$candidate->reference, (string)$candidate->uid, (string)$candidate->handle], true)) {
                    $field = $candidate;

                    break;
                }
            }

            if (!$field instanceof ParentFieldInterface) {
                return $match[0];
            }

            foreach ($field->referenceValues() as $referenceValue) {
                if ($referenceValue->selector === $expression->selector && $referenceValue->appliesTo($field)) {
                    return $match[0];
                }
            }

            $parts = explode(':', $expression->selector);
            $migrated = false;

            while ($field instanceof ParentFieldInterface && $parts !== []) {
                $child = $field->getFieldByHandle($parts[0]);

                if (!$child || !$child->reference) {
                    break;
                }

                array_shift($parts);
                $field = $child;
                $migrated = true;
            }

            if (!$migrated) {
                return $match[0];
            }

            return ReferenceParser::serialize(new ReferenceExpression(
                raw: $expression->raw,
                target: 'field',
                identifier: (string)$field->reference,
                selector: implode(':', $parts),
                default: $expression->default,
                transformerId: $expression->transformerId,
                transformerParams: $expression->transformerParams,
                isValid: true,
            ));
        }, $value);
    }


    // Private Methods
    // =========================================================================

    private static function _legacySubmissionToken(string $path, array $fields): ?string
    {
        $attribute = ['id' => 'id', 'uid' => 'uid', 'title' => 'title', 'status' => 'status', 'cpEditUrl' => 'url'][$path] ?? null;

        if (!$attribute) {
            return null;
        }

        // Formie 3 read a field with this handle first. Only `status` and `cpEditUrl` aren't reserved handles.
        foreach ($fields as $field) {
            if ($field->handle === $path && !in_array($path, ['id', 'uid', 'title'], true)) {
                return null;
            }
        }

        return '{submission:' . $attribute . '}';
    }

    private static function _legacySettings(array $settings, array $keys, array $fields, bool $useReferences): array
    {
        foreach ($keys as $key) {
            if (isset($settings[$key])) {
                $settings[$key] = self::_legacyValue($settings[$key], $fields, $useReferences);
            }
        }

        return $settings;
    }

    /** Formie 3 read upload paths as object templates, so a plain filter on one value has an equivalent token. */
    private static function _legacyPathFilters(array $fields, string $value, bool $useReferences): string
    {
        // `owner` was the submission, for an upload inside a Repeater.
        $value = str_replace('{owner.id}', '{submission:id}', $value);

        return preg_replace_callback('/(?<!\{)\{([a-zA-Z][a-zA-Z0-9_.]*)\|(?:(lower|upper|title|capitalize|kebab|camel)|date\(\s*["\']([^"\']+)["\']\s*\))\}(?!\})/', static function(array $match) use ($fields, $useReferences): string {
            [$target, $identifier, $selector] = self::_legacyPathSource($fields, $match[1], $useReferences);

            if (!$target) {
                return $match[0];
            }

            return ReferenceParser::serialize(new ReferenceExpression(
                raw: $match[0],
                target: $target,
                identifier: $identifier,
                selector: $selector,
                transformerId: $match[2] !== '' ? $match[2] : 'format',
                transformerParams: $match[2] !== '' ? [] : ['preset' => 'custom', 'pattern' => $match[3]],
                isValid: true,
            ));
        }, $value);
    }

    private static function _legacyPathSource(array $fields, string $source, bool $useReferences): array
    {
        if (in_array($source, ['postDate', 'dateCreated'], true)) {
            return ['submission', 'date', ''];
        }

        if (in_array($source, ['id', 'uid', 'title'], true)) {
            return ['submission', $source, ''];
        }

        $alias = ReferenceParser::legacyAliases()[$source] ?? null;

        if ($alias && !str_contains($alias, ';')) {
            return [...explode(':', $alias, 2), ''];
        }

        $source = preg_replace('/^field\./', '', $source);
        $path = self::_legacyFieldPath($fields, $source);

        if (!$path) {
            return [null, '', ''];
        }

        [$field, $selector] = $path;

        return ['field', $useReferences ? (string)$field->reference : ($selector === '' ? $source : substr($source, 0, -strlen($selector) - 1)), $selector];
    }

    private static function _legacyValue(mixed $value, array $fields, bool $useReferences): mixed
    {
        if (is_array($value)) {
            return array_map(fn($item) => self::_legacyValue($item, $fields, $useReferences), $value);
        }

        if (!is_string($value)) {
            return $value;
        }

        // Rich text settings hold their document as a JSON string.
        if (str_starts_with($value, '[') && is_array($document = Json::decodeIfJson($value))) {
            $migrated = self::_legacyValue($document, $fields, $useReferences);

            return $migrated === $document ? $value : Json::encode($migrated);
        }

        return self::legacyTextTokens($fields, $value, $useReferences);
    }

    private static function _legacyFieldPath(array $fields, string $path): ?array
    {
        // Formie 3's string form of a field with sub-fields is the whole field's value.
        $path = preg_replace('/\.__toString$/D', '', $path);
        [$identifier, $selector] = array_pad(explode('.', $path, 2), 2, '');

        foreach ($fields as $field) {
            if (!$field->reference || !in_array($identifier, [(string)$field->reference, (string)$field->uid, (string)$field->handle], true)) {
                continue;
            }

            if ($selector === '') {
                return [$field, ''];
            }

            // Row values need a scope that a plain path can't express.
            if ($field instanceof RepeatableParentFieldInterface) {
                return null;
            }

            // A path into a Group always meant a child. Its own selectors didn't exist in Formie 3.
            if (!$field instanceof ContainerParentFieldInterface) {
                foreach ($field->referenceValues() as $referenceValue) {
                    if ($referenceValue->selector === $selector && $referenceValue->appliesTo($field)) {
                        return [$field, $selector];
                    }
                }
            }

            return $field instanceof ParentFieldInterface ? self::_legacyFieldPath($field->getFields(), $selector) : null;
        }

        return null;
    }
}
