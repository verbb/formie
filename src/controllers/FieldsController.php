<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Signature;
use verbb\formie\fields\Summary;
use verbb\formie\helpers\FieldAccess;
use verbb\formie\helpers\CalculationsHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\options\OptionSourceFieldInterface;

use Craft;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\Response;

use Throwable;

class FieldsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['get-summary-html', 'get-signature-image'];


    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('formie/settings/fields', []);
    }

    public function actionGetElementSelectOptions(): Response
    {
        $elementIds = $this->request->getParam('elements');

        $elements = array_map(function($elementInfo) {
            $element = Craft::$app->getElements()->getElementById($elementInfo['id'], null, $elementInfo['siteId'], ['status' => null]);

            if (!$element) {
                return null;
            }

            return [
                'id' => $element->id,
                'siteId' => $element->siteId,
                'label' => $element->title,
                'url' => $element->cpEditUrl,
                'status' => $element->status,
                'elementType' => get_class($element),
            ];
        }, $elementIds);

        return $this->asJson(array_filter($elements));
    }

    public function actionGetElementSelectPreviewOptions(): Response
    {
        $elements = [];

        try {
            $fieldData = $this->request->getParam('field');
            $type = $fieldData['type'];
            $fieldSettings = $fieldData['settings'];

            // Create a new fieldtype, and populate the settings
            $field = new $type();
            $field->sources = $fieldSettings['sources'] ?? [];
            $field->source = $fieldSettings['source'] ?? null;

            // Fetch the element query for the field, so we can fetch the content (limited)
            $elements = $field->getPreviewElements();
        } catch (Throwable $e) {
            Formie::error('Unable to fetch element select options: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return $this->asJson($elements);
    }

    public function actionGetPredefinedOptions(): Response
    {
        $type = $this->request->getParam('option');

        $options = Formie::$plugin->getOptionSources()->getPredefinedOptionsForType($type);

        return $this->asJson($options);
    }

    public function actionResolveOptionSource(): Response
    {
        $this->requireAcceptsJson();

        $fieldType = (string)$this->request->getBodyParam('fieldType', '');
        $fieldSettings = $this->request->getBodyParam('fieldSettings', []);

        if (!is_array($fieldSettings)) {
            throw new BadRequestHttpException('Invalid field settings payload.');
        }

        $field = $this->_createOptionSourceFieldFromPayload($fieldType, $fieldSettings);
        $result = Formie::$plugin->getOptionSources()->resolve($field);

        return $this->asJson([
            'options' => $result->items,
            'error' => $result->error,
            'stale' => $result->stale,
            'count' => count($result->items),
        ]);
    }

    public function actionDetachOptionSource(): Response
    {
        $this->requireAcceptsJson();

        $fieldType = (string)$this->request->getBodyParam('fieldType', '');
        $fieldSettings = $this->request->getBodyParam('fieldSettings', []);

        if (!is_array($fieldSettings)) {
            throw new BadRequestHttpException('Invalid field settings payload.');
        }

        $field = $this->_createOptionSourceFieldFromPayload($fieldType, $fieldSettings);
        $options = Formie::$plugin->getOptionSources()->detachToStatic($field);

        return $this->asJson([
            'optionsMode' => 'static',
            'optionSource' => null,
            'options' => $options,
            'count' => count($options),
        ]);
    }

    public function actionGetElementOptionSourceConfig(): Response
    {
        $this->requireAcceptsJson();

        $provider = (string)($this->request->getBodyParam('provider')
            ?? $this->request->getQueryParam('provider')
            ?? $this->request->getParam('provider', ''));

        if (trim($provider) === '') {
            throw new BadRequestHttpException('Missing required param: provider.');
        }

        return $this->asJson(
            Formie::$plugin->getOptionSources()->getElementProviderBuilderConfig($provider),
        );
    }

    public function actionGetIntegrationOptionSourceConfig(): Response
    {
        $this->requireAcceptsJson();

        $provider = (string)($this->request->getBodyParam('provider')
            ?? $this->request->getQueryParam('provider')
            ?? $this->request->getParam('provider', ''));
        $integrationId = (int)($this->request->getBodyParam('integrationId')
            ?? $this->request->getQueryParam('integrationId')
            ?? $this->request->getParam('integrationId', 0));
        $sourceUsage = (string)($this->request->getBodyParam('sourceUsage')
            ?? $this->request->getQueryParam('sourceUsage')
            ?? $this->request->getParam('sourceUsage', ''));
        $sourceUsage = $sourceUsage !== '' ? $sourceUsage : null;

        if ($integrationId > 0) {
            if (trim($provider) !== '') {
                return $this->asJson(
                    Formie::$plugin->getOptionSources()->getIntegrationBuilderConfig($provider, $integrationId, $sourceUsage),
                );
            }

            return $this->asJson(
                Formie::$plugin->getOptionSources()->getIntegrationBuilderConfigForIntegration($integrationId, $sourceUsage),
            );
        }

        return $this->asJson([
            'integrationOptions' => Formie::$plugin->getOptionSources()->getEnabledIntegrationOptions($sourceUsage),
        ]);
    }

    public function actionGetFieldTypeConfig(): Response
    {
        $this->requireAcceptsJson();

        $hydrateOnlyParam = $this->request->getBodyParam('hydrateOnly',
            $this->request->getQueryParam('hydrateOnly',
                $this->request->getParam('hydrateOnly', false)));
        $hydrateOnly = filter_var($hydrateOnlyParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $hydrateOnly = $hydrateOnly ?? false;

        $type = (string)($this->request->getBodyParam('type')
            ?? $this->request->getQueryParam('type')
            ?? $this->request->getParam('type'));
        $type = trim($type);

        if ($type === '') {
            throw new BadRequestHttpException('Missing required param: type.');
        }

        $fieldTypeConfig = Formie::$plugin->getFields()->getFormBuilderFieldTypeConfig($type, $hydrateOnly);

        if (!$fieldTypeConfig) {
            throw new BadRequestHttpException('Unknown field type.');
        }

        return $this->asJson([
            'fieldType' => $fieldTypeConfig,
        ]);
    }

    public function actionGetPaymentProviderSettingsSchema(): Response
    {
        $this->requireAcceptsJson();

        $fieldType = (string)($this->request->getBodyParam('fieldType')
            ?? $this->request->getQueryParam('fieldType')
            ?? $this->request->getParam('fieldType', Payment::class));
        $fieldType = trim($fieldType);

        $providerHandle = (string)($this->request->getBodyParam('providerHandle')
            ?? $this->request->getQueryParam('providerHandle')
            ?? $this->request->getParam('providerHandle'));
        $providerHandle = trim($providerHandle);

        $schemaGroup = (string)($this->request->getBodyParam('schemaGroup')
            ?? $this->request->getQueryParam('schemaGroup')
            ?? $this->request->getParam('schemaGroup', 'defineFormBuilderGeneralSchema'));
        $schemaGroup = trim($schemaGroup);

        if ($providerHandle === '') {
            throw new BadRequestHttpException('Missing required param: providerHandle.');
        }

        if ($schemaGroup === '') {
            throw new BadRequestHttpException('Missing required param: schemaGroup.');
        }

        $field = Formie::$plugin->getFields()->getRegisteredFieldByType($fieldType, false);

        if (!$field instanceof Payment) {
            throw new BadRequestHttpException('Invalid payment field type.');
        }

        $schema = $field->getProviderSettingsSchemaForHandle($providerHandle, $schemaGroup);
        $compiled = SchemaHelper::compileSchema($schema);

        return $this->asJson([
            'schema' => $compiled['schema'],
            'schemaIndex' => $compiled,
            'defaultValues' => $field->getProviderSettingsDefaultsForHandle($providerHandle),
        ]);
    }

    public function actionValidateCalculationsFormula(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $formula = trim((string)$this->request->getBodyParam('formula', ''));
        $availableTokens = $this->request->getBodyParam('availableTokens', []);
        $tokenLabels = $this->request->getBodyParam('tokenLabels', []);

        if (!$formula) {
            return $this->asJson([
                'valid' => false,
                'message' => Craft::t('formie', 'Enter a formula to test.'),
                'technicalMessage' => null,
            ]);
        }

        $tokenMatches = [];
        preg_match_all('/\{[^}]+\}/', $formula, $tokenMatches);
        $tokensInFormula = $tokenMatches[0] ?? [];

        if (is_array($availableTokens) && $availableTokens) {
            $availableTokenLookup = array_flip($availableTokens);
            $unknownTokens = array_values(array_filter($tokensInFormula, function($token) use ($availableTokenLookup) {
                return !isset($availableTokenLookup[$token]);
            }));

            if ($unknownTokens) {
                return $this->asJson([
                    'valid' => false,
                    'message' => Craft::t('formie', 'Unknown field token(s): {tokens}', [
                        'tokens' => implode(', ', array_slice($unknownTokens, 0, 3)),
                    ]),
                    'technicalMessage' => null,
                ]);
            }
        }

        $variables = [];
        $variableMeta = [];
        $compiledFormula = preg_replace_callback('/\{[^}]+\}/', function($matches) use (&$variables, &$variableMeta, $tokenLabels) {
            $rawToken = (string)($matches[0] ?? '');
            $token = trim((string)($matches[0] ?? ''), '{}');
            $variableName = 'var_' . preg_replace('/[^A-Za-z0-9_]/', '_', $token);
            $variableName = trim($variableName, '_');

            if ($variableName === '' || preg_match('/^[0-9]/', $variableName)) {
                $variableName = 'var_' . $variableName;
            }

            $baseName = $variableName;
            $suffix = 1;
            while (isset($variables[$variableName])) {
                $suffix++;
                $variableName = "{$baseName}_{$suffix}";
            }

            $variables[$variableName] = 1;
            $label = is_array($tokenLabels) ? (string)($tokenLabels[$rawToken] ?? '') : '';
            $variableMeta[$variableName] = [
                'token' => $rawToken,
                'label' => $label ?: $rawToken,
            ];

            return $variableName;
        }, $formula);

        try {
            $evaluator = CalculationsHelper::getEvaluator();
            $evaluator->parse($compiledFormula, array_keys($variables));

            return $this->asJson([
                'valid' => true,
                'message' => Craft::t('formie', 'Formula is valid.'),
                'technicalMessage' => null,
            ]);
        } catch (Throwable $e) {
            $technicalMessage = $this->_sanitizeCalculationValidationMessage($e->getMessage(), $variableMeta);

            return $this->asJson([
                'valid' => false,
                'message' => $this->_getFriendlyCalculationValidationMessage($technicalMessage, $formula, $variableMeta, is_array($tokenLabels) ? $tokenLabels : []),
                'technicalMessage' => $technicalMessage,
            ]);
        }
    }

    public function actionGetSummaryHtml(): string
    {
        $this->requirePostRequest();
        $context = $this->_resolveFieldAccessContext((string)$this->request->getParam('accessToken', ''));

        if (!$context) {
            return '';
        }

        if (!($context['field'] instanceof Summary)) {
            return '';
        }

        $context['form']->setCurrentSubmission($context['submission']);
        $value = $context['submission']->getFieldValue($context['field']->valueKey());
        $html = (string)$context['field']->renderInput($context['form'], $value);
        $accessToken = FieldAccess::issueAccessToken($context['submission'], (int)$context['field']->id);

        if ($accessToken && !str_contains($html, 'data-formie-summary-token')) {
            $escapedToken = htmlspecialchars($accessToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = preg_replace(
                '/data-formie-summary-container(?:="true")?/',
                '$0 data-formie-summary-token="' . $escapedToken . '"',
                $html,
                1
            ) ?? $html;
        }

        return $html;
    }

    public function actionGetSignatureImage(): ?Response
    {
        $context = $this->_resolveFieldAccessContext((string)$this->request->getParam('accessToken', ''));

        if (!$context || !($context['field'] instanceof Signature)) {
            return null;
        }

        $value = trim((string)$context['submission']->getFieldValue($context['field']->valueKey()));

        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'base64,')) {
            $parts = explode('base64,', $value, 2);
            $value = trim((string)($parts[1] ?? ''));
        }

        $image = base64_decode($value, true);

        if (!is_string($image) || $image === '') {
            $fallback = base64_decode($value, false);
            $image = is_string($fallback) ? $fallback : null;
        }

        if (!is_string($image) || $image === '') {
            return null;
        }

        $response = Craft::$app->getResponse();
        $response->setCacheHeaders();
        $response->getHeaders()->set('Content-Type', 'image/png');

        return $this->asRaw($image);
    }


    // Private Methods
    // =========================================================================

    private function _createOptionSourceFieldFromPayload(string $fieldType, array $fieldSettings): OptionSourceFieldInterface
    {
        $fieldType = trim($fieldType);

        if ($fieldType === '' || !class_exists($fieldType) || !is_a($fieldType, OptionSourceFieldInterface::class, true)) {
            throw new BadRequestHttpException('Invalid option source field type.');
        }

        /** @var OptionSourceFieldInterface $field */
        $field = new $fieldType($fieldSettings);

        return $field;
    }

    private function _resolveFieldAccessContext(?string $accessToken): ?array
    {
        $payload = FieldAccess::resolveAccessToken($accessToken);

        if (!$payload) {
            return null;
        }

        $submission = Submission::find()
            ->uid($payload['submissionUid'])
            ->formId($payload['formId'])
            ->isIncomplete(null)
            ->one();

        if (!$submission) {
            return null;
        }

        $form = $submission->getForm();

        if (!$form || (int)$form->id !== (int)$payload['formId']) {
            return null;
        }

        $field = $form->getFieldById((int)$payload['fieldId']);

        if (!$field) {
            return null;
        }

        return [
            'submission' => $submission,
            'form' => $form,
            'field' => $field,
        ];
    }

    private function _sanitizeCalculationValidationMessage(string $message, array $variableMeta = []): string
    {
        if (!$variableMeta) {
            return $message;
        }

        // Replace parser-internal variable aliases with readable token/label identifiers.
        $keys = array_keys($variableMeta);
        usort($keys, function($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        foreach ($keys as $variableKey) {
            $meta = $variableMeta[$variableKey] ?? [];
            $token = (string)($meta['token'] ?? '');
            $label = (string)($meta['label'] ?? '');

            $replacement = $label ?: $token ?: $variableKey;

            $message = str_replace($variableKey, $replacement, $message);
        }

        return $message;
    }

    private function _getFriendlyCalculationValidationMessage(string $technicalMessage, string $formula, array $variableMeta = [], array $tokenLabels = []): string
    {
        if (preg_match('/Unexpected token\s+"([^"]+)"\s+of value\s+"([^"]+)"/i', $technicalMessage, $tokenMatch)) {
            $value = (string)($tokenMatch[2] ?? '');
            $displayFormula = $this->_replaceFormulaTokensWithLabels($formula, $tokenLabels);
            $excerpt = $this->_getFormulaExcerpt($displayFormula, $value);

            if ($value !== '' && $excerpt !== '') {
                return Craft::t('formie', 'Unexpected value "{value}" near "{excerpt}".', [
                    'value' => $value,
                    'excerpt' => $excerpt,
                ]);
            }

            if ($value !== '') {
                return Craft::t('formie', 'Unexpected value "{value}" in formula.', [
                    'value' => $value,
                ]);
            }
        }

        if (preg_match('/Variable\s+"([^"]+)"/i', $technicalMessage, $variableMatch)) {
            $variableKey = (string)($variableMatch[1] ?? '');
            $meta = $variableMeta[$variableKey] ?? null;
            $label = $meta['label'] ?? '';
            $token = $meta['token'] ?? '';

            if ($label && $token && $label !== $token) {
                return Craft::t('formie', 'Field variable "{label}" is not valid.', [
                    'label' => $label,
                ]);
            }

            if ($label) {
                return Craft::t('formie', 'Field variable "{label}" is not valid.', [
                    'label' => $label,
                ]);
            }
        }

        return Craft::t('formie', 'Formula syntax is invalid. Check your operators, spacing, and values.');
    }

    private function _replaceFormulaTokensWithLabels(string $formula, array $tokenLabels = []): string
    {
        if (!$tokenLabels) {
            return $formula;
        }

        return preg_replace_callback('/\{[^}]+\}/', function($matches) use ($tokenLabels) {
            $token = (string)($matches[0] ?? '');
            $label = (string)($tokenLabels[$token] ?? '');

            return $label ?: $token;
        }, $formula) ?? $formula;
    }

    private function _getFormulaExcerpt(string $formula, string $needle): string
    {
        if ($formula === '' || $needle === '') {
            return '';
        }

        $position = strrpos($formula, $needle);
        if ($position === false) {
            return '';
        }

        $start = max(0, $position - 12);
        $length = strlen($needle) + 24;
        $excerpt = substr($formula, $start, $length);

        return trim($excerpt);
    }

}
